<?php

namespace Biigle\Tests\Jobs;

use File;
use Storage;
use TestCase;
use Exception;
use Biigle\Shape;
use Biigle\VideoAnnotation;
use Biigle\Tests\VideoTest;
use Biigle\Jobs\TrackObject;
use Biigle\Tests\VideoAnnotationTest;

class TrackObjectTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('test');
        Storage::disk('test')->put('my-video.mp4', 'abc');
    }

    public function testHandle()
    {
        config(['videos.keyframe_distance' => 123]);

        $annotation = VideoAnnotationTest::create([
            'shape_id' => Shape::pointId(),
            'frames' => [0.5],
            'points' => [[0, 0]],
            'video_id' => VideoTest::create(['url' => 'test://my-video.mp4']),
        ]);

        $job = new TrackObjectStub($annotation);
        $job->handle();
        $this->assertEquals(2, count($job->files));
        $input = json_decode($job->files[0], true);
        $this->assertEquals(0.5, $input['start_time']);
        $this->assertEquals(123, $input['keyframe_distance']);
        $this->assertArrayHasKey('video_path', $input);
        $this->assertStringContainsString('my-video.mp4', $input['video_path']);

        foreach ($job->paths as $path) {
            $this->assertFalse(File::exists($path));
        }
    }

    public function testHandlePoint()
    {
        config(['videos.tracking_point_padding' => 15]);

        $annotation = VideoAnnotationTest::create([
            'shape_id' => Shape::pointId(),
            'frames' => [0.5],
            'points' => [[0, 0]],
            'video_id' => VideoTest::create(['url' => 'test://my-video.mp4']),
        ]);

        $job = new TrackObjectStub($annotation);
        $job->handle();

        $this->assertEquals([0.5, 1.0, 2.0, 3.0], $annotation->fresh()->frames);
        $expect = [[0, 0], [10, 10], [20, 20], [30, 30]];
        $this->assertEquals($expect, $annotation->fresh()->points);

        $input = json_decode($job->files[0], true);
        $this->assertEquals([-15, -15, 30, 30], $input['start_window']);
    }

    public function testHandleCirlce()
    {
        $annotation = VideoAnnotationTest::create([
            'shape_id' => Shape::circleId(),
            'frames' => [0.5],
            'points' => [[10, 10, 5]],
            'video_id' => VideoTest::create(['url' => 'test://my-video.mp4']),
        ]);

        $job = new TrackObjectStub($annotation);
        $job->handle();

        $this->assertEquals([0.5, 1.0, 2.0, 3.0], $annotation->fresh()->frames);
        $expect = [[10, 10, 5], [10, 10, 5], [20, 20, 6], [30, 30, 7]];
        $this->assertEquals($expect, $annotation->fresh()->points);

        $input = json_decode($job->files[0], true);
        $this->assertEquals([5, 5, 10, 10], $input['start_window']);
    }

    public function testHandleFailure()
    {
        $annotation = VideoAnnotationTest::create();
        $job = new TrackObjectStub($annotation);
        try {
            $job->failed(new Exception);
            $this->assertTrue(false);
        } catch (Exception $e) {
            //
        }

        $this->assertNull($annotation->fresh());
    }

    public function testHandleEmpty()
    {
        $annotation = VideoAnnotationTest::create([
            'shape_id' => Shape::circleId(),
            'frames' => [0.5],
            'points' => [[10, 10, 5]],
            'video_id' => VideoTest::create(['url' => 'test://my-video.mp4']),
        ]);
        $job = new TrackObjectStub($annotation);
        $job->keyframes = '[]';
        $job->handle();
        $this->assertNull($annotation->fresh());
    }
}

class TrackObjectStub extends TrackObject
{
    public $files = [];
    public $paths = [];
    public $keyframes = '[[1.0, 10, 10, 5], [2.0, 20, 20, 6], [3.0, 30, 30, 7]]';

    protected function maybeDeleteFile($path)
    {
        $this->files[] = File::get($path);
        $this->paths[] = $path;

        return parent::maybeDeleteFile($path);
    }

    protected function python($command)
    {
        File::put($this->getOutputJsonPath($this->annotation), $this->keyframes);
    }
}
