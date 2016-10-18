<?php

namespace Lmc\Merlin;

use mysqli;

class TestRecord
{
    /**
     * @var mysqli;
     */
    public $dbConnection;
    public $batchId;
    public $testId;
    public $testName;
    public $baseline;

    public function __Construct($dbConnection, $testId)
    {
        $this->dbConnection = $dbConnection;
        $this->testId = $testId;
    }

    public function set($dbConnection, $testId, $batchId, $testName, $baseline=false)
    {
        $this->dbConnection = $dbConnection;
        $this->testId = $testId;
        $this->batchId = $batchId;
        $this->testName = $testName;
        $this->baseline = $baseline;
    }

    public function save()
    {
        $timestamp = time();
        $baseline = $this->baseline ? 1 : 0;
        $query = "INSERT INTO test (batchId, name, start, baseline) " .
            "VALUES ('$this->batchId', '$this->testName', $timestamp, $baseline)";
        $result = $this->dbConnection->query($query);
        if ($result) {
            $this->testId = $this->dbConnection->insert_id;
        }
    }

    public function queryScreenshotIds()
    {
        $result = $this->dbConnection->query("SELECT screenshotId FROM screenshot WHERE testId=$this->testId");
        if ($result) {
            $fetchedScreenshotIds = $result->fetch_all();
            $result->free();
            $screenshotIds = [];
            foreach ($fetchedScreenshotIds as $OneScreenshotId) {
                $screenshotIds[] = $OneScreenshotId[0];
            }
            return $screenshotIds;
        }
    }
}