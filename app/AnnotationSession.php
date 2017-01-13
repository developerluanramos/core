<?php

namespace Biigle;

use DB;
use Carbon\Carbon;
use Biigle\Annotation;
use Illuminate\Database\Eloquent\Model;

/**
 * An annotation session groups multiple annotations of a volume based on their
 * creation date.
 */
class AnnotationSession extends Model
{
    /**
     * Validation rules for updating an annotation session.
     *
     * @var array
     */
    public static $storeRules = [
        'name' => 'required',
        'starts_at' => 'required|date',
        'ends_at' => 'required|date|after:starts_at',
        'hide_other_users_annotations' => 'filled|boolean',
        'hide_own_annotations' => 'filled|boolean',
        'users' => 'required|array',
        'users.*' => 'distinct|exists:users,id',
    ];

    /**
     * Validation rules for updating an annotation session.
     *
     * @var array
     */
    public static $updateRules = [
        'name' => 'filled',
        'starts_at' => 'filled|date',
        'ends_at' => 'filled|date',
        'hide_other_users_annotations' => 'filled|boolean',
        'hide_own_annotations' => 'filled|boolean',
        'users' => 'filled|array',
        'users.*' => 'distinct|exists:users,id',
        'force' => 'filled|boolean',
    ];

    /**
     * Validation rules for destroying an annotation session.
     *
     * @var array
     */
    public static $destroyRules = [
        'force' => 'filled|boolean',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'hide_other_users_annotations' => 'boolean',
        'hide_own_annotations' => 'boolean',
        'volume_id' => 'int',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'starts_at_iso8601',
        'ends_at_iso8601',
    ];

    /**
     * The volume, this annotation session belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function volume()
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * The users, this annotation session is restricted to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->select('id', 'firstname', 'lastname', 'email');
    }

    /**
     * Get the annotations of the image (with labels), filtered by the restrictions of this annotation session.
     *
     * @param Image $image The image to get the annotations from
     * @param User $user The user to whom the restrictions should apply ('own' user)
     *
     * @return Illuminate\Support\Collection
     */
    public function getImageAnnotations(Image $image, User $user)
    {
        $query = Annotation::allowedBySession($this, $user)
            ->where('annotations.image_id', $image->id);

        /*
         * If both hide_other_users_annotations and hide_own_annotations is true,
         * allowedBySession already filters out all old annotations and only those
         * annotations are kept, that belong to this session. We therefore only need
         * to perform the hide_other_users_annotations filtering.
         * This is the reason why there is no special case for both true in the following
         * if else block.
         */
        if ($this->hide_other_users_annotations) {

            // hide all annotation labels of other users
            $query->with(['labels' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }]);

        } else if ($this->hide_own_annotations) {

            // take only labels of this session or any labels of other users
            $query->with(['labels' => function ($query) use ($user) {
                // wrap this in a where because the default query already has a where
                $query->where(function ($query) use ($user) {
                    $query->where(function ($query) {
                        $query->where('created_at', '>=', $this->starts_at)
                            ->where('created_at', '<', $this->ends_at);
                    })
                    ->orWhere('user_id', '!=', $user->id);
                });
            }]);

        } else {
            $query->with('labels');
        }

        return $query->get();
    }

    /**
     * Get a query for all annotations that belong to this session.
     *
     * This is **not** an Eloquent relation!
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function annotations()
    {
        return Annotation::where(function ($query) {
            // all annotations of the associated volume
            return $query->whereIn('image_id', function ($query) {
                    $query->select('id')
                        ->from('images')
                        ->where('volume_id', $this->volume_id);
                })
            // that were created between the start and end date
                ->where('created_at', '>=', $this->starts_at)
                ->where('created_at', '<', $this->ends_at)
            // and have a label by one of the members of this session
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('annotation_labels')
                        ->whereRaw('annotation_labels.annotation_id = annotations.id')
                        ->whereIn('annotation_labels.user_id', function ($query) {
                            $query->select('user_id')
                                ->from('annotation_session_user')
                                ->where('annotation_session_id', $this->id);
                        });
                });
        });
    }

    /**
     * Check if the given user is allowed to access the annotation if this annotation session is active
     *
     * @param Annotation $annotation
     * @param User $user
     * @return bool
     */
    public function allowsAccess(Annotation $annotation, User $user)
    {
        if ($this->hide_own_annotations && $this->hide_other_users_annotations) {

            return $annotation->created_at >= $this->starts_at &&
                $annotation->created_at < $this->ends_at &&
                $annotation->labels()->where('user_id', $user->id)->exists();

        } else if ($this->hide_own_annotations) {

            return ($annotation->created_at >= $this->starts_at && $annotation->created_at < $this->ends_at) ||
                $annotation->labels()->where('user_id', '!=', $user->id)->exists();

        } else if ($this->hide_other_users_annotations) {

            return $annotation->labels()->where('user_id', $user->id)->exists();

        }

        return true;
    }

    /**
     * Set the start date
     *
     * @param mixed $value The date (must be parseable by Carbon)
     */
    public function setStartsAtAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['starts_at'] = null;
        } else {
            $this->attributes['starts_at'] = Carbon::parse($value)->tz(config('app.timezone'));
        }
    }

    /**
     * Set the end date
     *
     * @param mixed $value The date (must be parseable by Carbon)
     */
    public function setEndsAtAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['ends_at'] = null;
        } else {
            $this->attributes['ends_at'] = Carbon::parse($value)->tz(config('app.timezone'));
        }
    }

    /**
     * Get the start date formatted as ISO8601 string
     *
     * @return string
     */
    public function getStartsAtIso8601Attribute()
    {
        return $this->starts_at->toIso8601String();
    }

    /**
     * Get the end date formatted as ISO8601 string
     *
     * @return string
     */
    public function getEndsAtIso8601Attribute()
    {
        return $this->ends_at->toIso8601String();
    }
}
