<?php

namespace Biigle\Modules\Reports\Support\Reports\Volumes;

use Biigle\Label;
use Biigle\Modules\Reports\Support\Reports\ReportGenerator;
use Biigle\Volume;
use Exception;
use File;

class VolumeReportGenerator extends ReportGenerator
{
    /**
     * Object that runs the Python script to generate a report.
     *
     * @var PythonScriptRunner
     */
    protected $pythonScriptRunner;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->pythonScriptRunner = new PythonScriptRunner;
    }

    /**
     * Set the Python script runner object.
     *
     * @param mixed $runner
     */
    public function setPythonScriptRunner($runner)
    {
        $this->pythonScriptRunner = $runner;
    }

    /**
     * Constructs a label name from the names of all parent labels and the label itself.
     *
     * Example: `Animalia > Annelida > Polychaeta > Buskiella sp`
     *
     * @param int  $id  Label ID
     * @return string
     */
    public function expandLabelName($id)
    {
        if (is_null($this->labels)) {
            // We expect most of the used labels to belong to a label tree currently
            // attached to the volume (through its projects).
            $this->labels = $this->getVolumeLabels()->keyBy('id');
        }

        return parent::expandLabelName($id);
    }

    /**
     * Get all labels that are attached to the volume of this report (through project label trees).
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getVolumeLabels()
    {
        return Label::select('id', 'name', 'parent_id')
            ->whereIn('label_tree_id', function ($query) {
                $query->select('label_tree_id')
                    ->from('label_tree_project')
                    ->whereIn('project_id', function ($query) {
                        $query->select('project_id')
                            ->from('project_volume')
                            ->where('volume_id', $this->source->id);
                    });
            })
            ->get();
    }

    /**
     * Execute the external report parsing Python script.
     *
     * @param string $scriptName Name of the script to execute (in the `reports.scripts` config namespace)
     * @param string $path Path to the file to store the generated report to
     * @throws Exception If the script returned an error code.
     */
    protected function executeScript($scriptName, $path)
    {
        $this->pythonScriptRunner->run($scriptName, $this->source->name, $path, $this->tmpFiles);
    }
}
