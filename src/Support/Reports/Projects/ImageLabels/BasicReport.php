<?php

namespace Biigle\Modules\Export\Support\Reports\Projects\ImageLabels;


use Biigle\Modules\Export\Support\Reports\Projects\Report;
use Biigle\Modules\Export\Support\Reports\Volumes\ImageLabels\BasicReport as VolumeReport;

class BasicReport extends Report
{
    /**
     * The class of the volume report to use for this project report.
     *
     * @var string
     */
    protected $volumeReportClass = VolumeReport::class;

    /**
     * Name of the report for use in text.
     *
     * @var string
     */
    protected $name = 'basic image label report';

    /**
     * Name of the report for use as (part of) a filename.
     *
     * @var string
     */
    protected $filename = 'basic_image_label_report';
}
