<?php

namespace Lmc\Merlin;

use mysqli;

class Screenshot
{
    /**
     * @var mysqli
     */
    public $dbConnection;
    public $screenshotId;
    public $testId;
    public $timestamp;
    public $imageString;
    public $thumbnailString;
    public $name;

    public function __construct($dbConnection, $testId, $imageString, $thumbnailString, $name)
    {
        $this->dbConnection = $dbConnection;
        $this->testId = $testId;
        $this->timestamp = time();
        $this->imageString = $imageString;
        $this->thumbnailString = $thumbnailString;
        $this->name = $name;
    }

    public function save()
    {
        $imageEscaped = $this->dbConnection->real_escape_string($this->imageString);
        $thumbnailEscaped = $this->dbConnection->real_escape_string($this->thumbnailString);
        $query = "INSERT INTO screenshot (testId, timestamp, image, name, thumbnail) " .
            "VALUES ($this->testId, $this->timestamp, '$imageEscaped', '$this->name', '$thumbnailEscaped')";
        $result = $this->dbConnection->query($query);
        if ($result) {
            $this->screenshotId = $this->dbConnection->insert_id;
        }
    }
}