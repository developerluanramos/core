<?php

namespace Biigle\Support\Testing\Fakes;

use Biigle\Image;
use Illuminate\Filesystem\Filesystem;
use Biigle\Contracts\ImageCache as ImageCacheContract;

class ImageCacheFake implements ImageCacheContract
{
    public function __construct()
    {
        (new Filesystem)->cleanDirectory(
            $root = storage_path('framework/testing/disks/image-cache')
        );

        $this->path = $root;
    }

    /**
     * {@inheritdoc}
     */
    public function get(Image $image, $callback)
    {
        return $callback($image, "{$this->path}/{$image->id}");
    }

    /**
     * {@inheritdoc}
     */
    public function getOnce(Image $image, $callback)
    {
        return $this->get($image, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(Image $image)
    {
        return [
            'stream' => null,
            'size' => 0,
            'mime' => 'inode/x-empty',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prune()
    {
        //
    }
}
