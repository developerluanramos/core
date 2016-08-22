<?php

use Dias\Modules\Export\Jobs\GenerateFullReport;
use Dias\Modules\Export\Jobs\GenerateBasicReport;
use Dias\Modules\Export\Jobs\GenerateExtendedReport;

class ExportModuleHttpControllersApiReportsControllerTest extends ApiTestCase {

    public function testBasic() {
        $id = $this->project()->id;

        $this->get("api/v1/projects/{$id}/reports/basic")
            ->assertResponseStatus(401);

        $this->expectsJobs(GenerateBasicReport::class);
        $this->beGuest();
        $this->get("api/v1/projects/{$id}/reports/basic")
            ->assertResponseOk();
    }

    public function testExtended() {
        $id = $this->project()->id;

        $this->get("api/v1/projects/{$id}/reports/extended")
            ->assertResponseStatus(401);

        $this->expectsJobs(GenerateExtendedReport::class);
        $this->beGuest();
        $this->get("api/v1/projects/{$id}/reports/extended")
            ->assertResponseOk();
    }

    public function testFull() {
        $id = $this->project()->id;

        $this->get("api/v1/projects/{$id}/reports/full")
            ->assertResponseStatus(401);

        $this->expectsJobs(GenerateFullReport::class);
        $this->beGuest();
        $this->get("api/v1/projects/{$id}/reports/full")
            ->assertResponseOk();
    }
}
