<?php

namespace Biigle\Http\Controllers\Views\Volumes;

use Arr;
use Biigle\Image;
use Biigle\Http\Controllers\Views\Controller;

class ImageController extends Controller
{
    /**
     * Shows the image index page.
     *
     * @param int $id volume ID
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $image = Image::with('volume')->findOrFail($id);

        $this->authorize('access', $image);

        $metadataMap = [
            'gps_altitude' => 'GPS Altitude',
            'distance_to_ground' => 'Distance to ground',
            'yaw' => 'Yaw/Heading',
        ];

        return view('volumes.images.index', [
            'image' => $image,
            'volume' => $image->volume,
            'metadata' => Arr::only($image->metadata, array_keys($metadataMap)),
            'metadataMap' => $metadataMap,
        ]);
    }
}
