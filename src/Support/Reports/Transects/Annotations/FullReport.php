<?php

namespace Dias\Modules\Export\Support\Reports\Transects\Annotations;

use DB;
use Dias\LabelTree;
use Dias\Modules\Export\Support\CsvFile;

class FullReport extends Report
{
    /**
     * Name of the report for use in text.
     *
     * @var string
     */
    protected $name = 'full annotation report';

    /**
     * Name of the report for use as (part of) a filename.
     *
     * @var string
     */
    protected $filename = 'full_annotation_report';

    /**
     * File extension of the report file.
     *
     * @var string
     */
    protected $extension = 'xlsx';

    /**
     * Generate the report.
     *
     * @return void
     */
    public function generateReport()
    {
        $rows = $this->query()->get();

        if ($this->shouldSeparateLabelTrees()) {
            $rows = $rows->groupBy('label_tree_id');
            $trees = LabelTree::whereIn('id', $rows->keys())->pluck('name', 'id');

            foreach ($trees as $id => $name) {
                $this->tmpFiles[] = $this->createCsv($rows->get($id), $name);
            }

        } else {
            $this->tmpFiles[] = $this->createCsv($rows, $this->transect->name);
        }

        $this->executeScript('full_report');
    }

    /**
     * Assemble a new DB query for the transect of this report.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function query()
    {
        $query = $this->initQuery([
                'images.filename',
                'annotations.id as annotation_id',
                'annotation_labels.label_id',
                'shapes.name as shape_name',
                'annotations.points',
                'images.attrs',
            ])
            ->join('shapes', 'annotations.shape_id', '=', 'shapes.id')
            ->orderBy('annotations.id');

        return $query;
    }

    /**
     * Create a CSV file for a single sheet of the spreadsheet of this report
     *
     * @param \Illuminate\Support\Collection $rows The rows for the CSV
     * @param string $title The title to put in the first row of the CSV
     * @return CsvFile
     */
    protected function createCsv($rows, $title = '')
    {
        $csv = CsvFile::makeTmp();
        $csv->put([$title]);
        $csv->put(['image filename', 'annotation id', 'annotation shape', 'x/radius', 'y', 'labels', 'image area in m²']);

        foreach ($rows as $row) {
            $csv->put([
                $row->filename,
                $row->annotation_id,
                $this->expandLabelName($row->label_id),
                $row->shape_name,
                $row->points,
                $this->getArea($row->attrs)
            ]);
        }

        $csv->close();

        return $csv;
    }

    /**
     * Parses the image attrs JSON object to retrieve the computed area of the laserpoint detection.
     *
     * @param  string $attrs Image attrs JSON as string
     * @return mixed The number or `null`
     */
    protected function getArea($attrs)
    {
        $attrs = json_decode($attrs, true);
        if (is_array($attrs)) {
            return array_get($attrs, 'laserpoints.area');
        }

        return null;
    }
}
