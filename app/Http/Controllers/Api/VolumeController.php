<?php

namespace Biigle\Http\Controllers\Api;

use Biigle\Annotation;
use Biigle\AnnotationSession;
use Biigle\Http\Requests\UpdateVolume;
use Biigle\Image;
use Biigle\ImageAnnotation;
use Biigle\ImageAnnotationLabel;
use Biigle\ImageLabel;
use Biigle\Jobs\CreateNewImagesOrVideos;
use Biigle\Jobs\ProcessNewVolumeFiles;
use Biigle\MediaType;
use Biigle\Modules\ColorSort\Sequence;
use Biigle\Project;
use Biigle\Video;
use Biigle\VideoAnnotation;
use Biigle\VideoAnnotationLabel;
use Biigle\VideoLabel;
use Biigle\Volume;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use function Amp\Iterator\toArray;
use function PHPUnit\Framework\assertTrue;

class VolumeController extends Controller
{

    /**
     * Shows all volumes the user has access to.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @api {get} volumes Get accessible volumes
     * @apiGroup Volumes
     * @apiName IndexVolumes
     * @apiPermission user
     * @apiDescription Only projects in which the user is a member are listed for each
     * volume.
     *
     * @apiSuccessExample {json} Success response:
     * [
     *    {
     *       "id": 1,
     *       "name": "My Volume",
     *       "media_type_id": 1,
     *       "created_at": "2015-02-10 09:45:30",
     *       "updated_at": "2015-02-10 09:45:30",
     *       "projects": [
     *           {
     *               "id": 11,
     *               "name": "Example project",
     *               "description": "This is an example project"
     *           }
     *       ]
     *    }
     * ]
     *
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return Volume::accessibleBy($user)
            ->with(['projects' => function ($query) use ($user) {
                $query->when(!$user->can('sudo'), function ($query) use ($user) {
                    return $query->join('project_user', 'project_user.project_id', '=', 'projects.id')
                        ->where('project_user.user_id', $user->id);
                })
                    ->select('projects.id', 'projects.name', 'projects.description');
            }])
            ->orderByDesc('id')
            ->select('id', 'name', 'created_at', 'updated_at', 'media_type_id')
            ->get();
    }

    /**
     * Displays the specified volume.
     *
     * @param Request $request
     * @param int $id
     * @return Volume
     * @api {get} volumes/:id Get a volume
     * @apiGroup Volumes
     * @apiName ShowVolumes
     * @apiPermission projectMember
     *
     * @apiParam {Number} id The volume ID.
     *
     * @apiSuccessExample {json} Success response:
     * {
     *    "id": 1,
     *    "name": "volume 1",
     *    "media_type_id": 3,
     *    "creator_id": 7,
     *    "created_at": "2015-02-20 17:51:03",
     *    "updated_at": "2015-02-20 17:51:03",
     *    "url": "local://images/",
     *    "projects": [
     *        {
     *            "id": 11,
     *            "name": "Example project",
     *            "description": "This is an example project"
     *        }
     *    ]
     * }
     *
     */
    public function show(Request $request, $id)
    {
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);
        $volume->load(['projects' => function ($query) use ($request) {
            $query->join('project_user', 'project_user.project_id', '=', 'projects.id')
                ->where('project_user.user_id', $request->user()->id)
                ->select('projects.id', 'projects.name', 'projects.description');
        }]);

        return $volume;
    }

    /**
     * Updates the attributes of the specified volume.
     *
     * @param UpdateVolume $request
     * @return \Illuminate\Http\Response
     * @api {put} volumes/:id Update a volume
     * @apiGroup Volumes
     * @apiName UpdateVolumes
     * @apiPermission projectAdmin
     *
     * @apiParam {Number} id The volume ID.
     *
     * @apiParam (Attributes that can be updated) {String} name Name of the volume.
     * @apiParam (Attributes that can be updated) {String} url The base URL of the files. Can be a path to a storage disk like `local://volumes/1` or a remote path like `https://example.com/volumes/1`. Updating the URL will trigger a re-generation of all volume thumbnails.
     * @apiParam (Attributes that can be updated) {String} handle Handle or DOI of the dataset that is represented by the new volume.
     *
     */
    public function update(UpdateVolume $request)
    {
        $volume = $request->volume;
        $volume->name = $request->input('name', $volume->name);
        $volume->url = $request->input('url', $volume->url);
        $volume->handle = $request->input('handle', $volume->handle);

        $isDirty = $volume->isDirty();
        $shouldReread = !$isDirty && $request->user()->can('sudo');
        $newUrl = $volume->isDirty('url');
        $volume->save();

        // Do this *after* saving.
        if ($newUrl || $shouldReread) {
            ProcessNewVolumeFiles::dispatch($volume);
        }

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect()
                ->with('saved', $isDirty)
                ->with('reread', $shouldReread);
        }
    }

    //TODO: add javadocs
    public function clone($volumeId, $destProjectId, Request $request)
    {
        return DB::transaction(function () use ($volumeId, $destProjectId, $request) {
            $project = Project::findOrFail($destProjectId);
            $this->authorize('update', $project);
            $volume = Volume::findOrFail($volumeId);
            $copy = $volume->replicate();
            $copy->name = $request->input('name', $volume->name);
            $copy->created_at = Carbon::now();
            $copy->save();

            if ($volume->isImageVolume()) {
                $this->copyImages($volume, $copy, $request->input('imageIds', []));
            } else {
                $this->copyVideos($volume, $copy, $request->input('videoIds', []));
            }
            $this->copyAnnotationSessions($volume, $copy);

            //save ifdo-file if exist
            if ($volume->hasIfdo()) {
                $this->copyIfdoFile($volumeId, $copy->id);
            }

            $copy->push();

            $project->addVolumeId($copy->id);
            $project->push();

            return redirect()->back()->with(['copy' => $copy]);
        });

    }

    //TODO: add javadocs
    private function copyImages($volume, $copy, $imageIds)
    {
        // copy image references
        $images = count($imageIds) == 0 ? $volume->images()->get() : $volume->images()->whereIn('id', $imageIds)->get();
        $images = $images->map(function ($image) use ($copy) {
            $original = $image->getRawOriginal();
            $original['volume_id'] = $copy->id;
            $original['uuid'] = (string)Uuid::uuid4();
            unset($original['id']);
            return $original;
        });

        $images->chunk(10000)->map(function ($chunk) {
            Image::insert($chunk->toArray());
        });


        $oldImages = count($imageIds) == 0 ? $volume->images()->get() : $volume->images()->whereIn('id', $imageIds)->get();
        $newImages = $copy->images()->get();
        foreach ($oldImages as $index => $oldImage) {
            $newImage = $newImages[$index];
            $oldImage->annotations()->get()->map(function ($oldAnnotation) use ($newImage) {
                // copy annotation object
                $newAnnotation = $oldAnnotation->replicate();
                $newAnnotation->image_id = $newImage->id;
                $newAnnotation->created_at = Carbon::now();
                $newAnnotation->save();

                // copy label references
                $annotations = $oldAnnotation->labels()->get()->map(function ($oldLabel) use ($newAnnotation) {
                    $original = $oldLabel->getRawOriginal();
                    $original['annotation_id'] = $newAnnotation->id;
                    unset($original['id']);
                    return $original;
                });

                $annotations->chunk(10000)->map(function ($chunk) {
                    ImageAnnotationLabel::insert($chunk->toArray());
                });

            });
        }

        $images = count($imageIds) == 0 ? $volume->images()->get() : $volume->images()->whereIn('id', $imageIds)->get();
        foreach ($images as $imageIdx => $oldImage) {
            $newImage = $copy->images()->get()[$imageIdx];
            $labels = $oldImage->labels()->get()->map(function ($oldLabel) use ($newImage) {
                $origin = $oldLabel->getRawOriginal();
                $origin['image_id'] = $newImage->id;
                unset($origin['id']);
                return $origin;
            });
            $labels->chunk(10000)->map(function ($chunk) {
                ImageLabel::insert($chunk->toArray());
            });

        }

        // annotation_assistance_requests optional
    }

    //TODO: add javadocs
    private function copyVideos($volume, $copy, $videoIds)
    {
        // copy video references
        $videos = count($videoIds) == 0 ? $volume->videos()->get() : $volume->videos()->whereIn('id', $videoIds)->get();
        $videos = $videos->map(function ($video) use ($copy) {
            $origin = $video->getRawOriginal();
            $origin['volume_id'] = $copy->id;
            $origin['uuid'] = (string)Uuid::uuid4();
            unset($origin['id']);
            return $origin;
        });

        $videos->chunk(10000)->map(function ($chunk) {
            Video::insert($chunk->toArray());
        });

        $oldVideos = count($videoIds) == 0 ? $volume->videos()->get() : $volume->videos()->whereIn('id', $videoIds)->get();
        $newVideos = $copy->videos()->get();
        foreach ($oldVideos as $index => $oldVideo) {
            $newVideo = $newVideos[$index];
            $oldVideo->annotations()->get()->map(function ($oldAnnotation) use ($newVideo) {
                // copy annotation object
                $newAnnotation = $oldAnnotation->replicate();
                $newAnnotation->video_id = $newVideo->id;
                $newAnnotation->created_at = Carbon::now();
                $newAnnotation->save();

                // copy label references
                $annotations = $oldAnnotation->labels()->get()->map(function ($oldLabel) use ($newAnnotation) {
                    $original = $oldLabel->getRawOriginal();
                    $original['annotation_id'] = $newAnnotation->id;
                    unset($original['id']);
                    return $original;
                });

                $annotations->chunk(10000)->map(function ($chunk) {
                    VideoAnnotationLabel::insert($chunk->toArray());
                });

            });
        }

        $videos = count($videoIds) == 0 ? $volume->videos()->get() : $volume->videos()->whereIn('id', $videoIds)->get();
        foreach ($videos as $videoIdx => $oldVideo) {
            $newVideo = $copy->videos()->get()[$videoIdx];
            $labels = $oldVideo->labels()->get()->map(function ($oldLabel) use ($newVideo) {
                $origin = $oldLabel->getRawOriginal();
                $origin['video_id'] = $newVideo->id;
                unset($origin['id']);
                return $origin;
            });
            $labels->chunk(10000)->map(function ($chunk) {
                VideoLabel::insert($chunk->toArray());
            });

        }

    }

    //TODO: add javadocs
    private function copyIfdoFile($volume_id, $copy_id)
    {
        $disk = Storage::disk(config('volumes.ifdo_storage_disk'));
        $iFdoFilename = $volume_id . ".yaml";
        $copyIFdoFilename = $copy_id . ".yaml";
        $disk->copy($iFdoFilename, $copyIFdoFilename);
    }

    //TODO: add javadocs
    private function copyAnnotationSessions($volume, $copy)
    {
        DB::transaction(function () use ($volume, $copy) {
            $oldSessions = AnnotationSession::whereIn('volume_id', [$volume->id])->get()
                ->map(function ($oldSession) use ($copy) {
                    $original = $oldSession->getRawOriginal();
                    $original['volume_id'] = $copy->id;
                    unset($original['id']);
                    return $original;
                });
            $oldSessions->chunk(10000)->map(function ($chunk) {
                AnnotationSession::insert($chunk->toArray());
            });

            //copy users references
            $newSessions = AnnotationSession::whereIn('volume_id', [$copy->id])->get();
            $oldSessions = AnnotationSession::whereIn('volume_id', [$volume->id])->get();
            foreach ($newSessions as $idx => $newSession) {
                $newSession->users()->attach($oldSessions[$idx]->users()->get());
                $newSession->push();
            }


        });
    }
}
