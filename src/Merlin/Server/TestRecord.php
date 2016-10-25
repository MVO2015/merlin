<?php

namespace Lmc\Merlin\Server;

/**
 * TestRecord structure
 *
 */
class TestRecord
{
    public $batchId;
    public $testId;
    public $testName;
    public $start;
    public $end;
    public $status;
    public $baseline;

    /**
     * @param string $environment
     * @param string $job
     * @param string $build
     * @param string $testCase
     * @param string $testName
     * @param string $status
     * @param int $start
     * @param int $end
     * @param int $baseline
     * @param int $testId
     */
    public function __construct(
        $environment,
        $job,
        $build,
        $testCase,
        $testName,
        $status,
        $start,
        $end,
        $baseline,
        $testId
    ) {
        $this->environment = $environment;
        $this->job = $job;
        $this->build = $build;
        $this->testCase = $testCase;
        $this->testName = $testName;
        $this->status = $status;
        $this->start = $start;
        $this->end = $end;
        $this->baseline = $baseline;
        $this->testId = $testId;
    }

    /**
     * HTML representation
     *
     * @return string HTML
     */
    public function toHtml()
    {
        $output = "<li class='testItem'>\n";
        $onclick = "toggleImages(\"$this->testId\");";
        $output .= "<table><tr><td class= 'testName {$this->status}' onclick='$onclick'>{$this->testName}</td>\n";
        $output .= "<td class='testStatus {$this->status}'>{$this->status}</td>\n";
        $start = date("Y-m-d H:i:s", ($this->start));
        $output .= "<td class='testStart'>{$start}</td>\n";
        if ($this->end > 0) {
            $end = date("Y-m-d H:i:s", ($this->end));
        } else {
            $end = "";
        }
        $output .= "<td class='testEnd'>{$end}</td></tr></table>\n";
        $output .= "<div id='testid$this->testId'></div>";
        $output .= "</li>\n";
        return $output;
    }

    /**
     * Is this instance of TestRecord baseline test record?
     * @return bool True if it is baseline test record.
     */
    public function isBaseline()
    {
        return $this->baseline == 0;
    }
}
