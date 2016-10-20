<?php

namespace Dias\Modules\Export\Http\Controllers\Api\Transects\ImageLabels;

use Dias\Modules\Export\Support\Reports\Transects\ImageLabels\BasicReport;
use Dias\Modules\Export\Http\Controllers\Api\Transects\TransectReportController;

class BasicReportController extends TransectReportController
{
    /**
     * The report classname
     *
     * @var string
     */
    protected $report = BasicReport::class;

    /**
     * @api {post} transects/:id/reports/image-labels/basic Generate a new basic image label report
     * @apiGroup Transects
     * @apiName GenerateBasicTransectImageLabelReport
     * @apiParam (Optional arguments) {Boolean} exportArea If `true`, restrict the report to the export area of the transect.
     * @apiParam (Optional arguments) {Boolean} separateLabelTrees If `true`, separate image labels of different label trees to different sheets of the spreadsheet.
     * @apiPermission projectMember
     *
     * @apiParam {Number} id The transect ID.
     */
}
