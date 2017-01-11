<?php

namespace Biigle\Tests\Modules\Export\Http\Controllers\Api\Transects\Annotations;

use ApiTestCase;
use Biigle\Modules\Export\Jobs\GenerateReportJob;
use Biigle\Modules\Export\Support\Reports\Transects\Annotations\FullReport;

class FullReportControllerTest extends ApiTestCase
{
    public function testStore()
    {
        $id = $this->transect()->id;

        $this->post("api/v1/transects/{$id}/reports/annotations/full")
            ->assertResponseStatus(401);

        $this->expectsJobs(GenerateReportJob::class);
        $this->beGuest();
        $this->post("api/v1/transects/{$id}/reports/annotations/full")
            ->assertResponseOk();

        $job = $this->dispatchedJobs[0];
        $report = $job->report;
        $this->assertInstanceOf(FullReport::class, $report);
        $this->assertEquals($id, $report->transect->id);
        $this->assertEquals(false, $report->options['exportArea']);

        $this->post("api/v1/transects/{$id}/reports/annotations/full", [
                'exportArea' => true
            ])
            ->assertResponseOk();

        $job = $this->dispatchedJobs[1];
        $report = $job->report;
        $this->assertInstanceOf(FullReport::class, $report);
        $this->assertEquals($id, $report->transect->id);
        $this->assertEquals(true, $report->options['exportArea']);
    }
}
