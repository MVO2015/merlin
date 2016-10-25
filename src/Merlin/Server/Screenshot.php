<?php

namespace Lmc\Merlin\Server;

/**
 * Screenshot data structure
 *
 */
class Screenshot
{
    // screenshot statuses
    const SCR_STATUS_NEW = 0;
    const SCR_STATUS_PASS = 1;
    const SCR_STATUS_FAIL = -1;

    public $screenshotId;
    public $testId;
    public $timestamp;
    public $imageString;
    public $thumbnailString;
    public $name;
    public $status;

    // TODO use it later(?)
    public $diffImageString;
    public $diffThmString;

    /**
     * @param int $testId Test id
     * @param string $imageString Screenshot in form of string
     * @param string$thumbnailString Thumbnail in form of string
     * @param string $name Unique name of this screenshot in this test
     * @param int $timestamp Current timestamp
     * @param int $status Screenshot status (see constants above)
     * @param null|int $screenshotId Screenshot id
     */
    public function __construct(
        $testId,
        $imageString,
        $thumbnailString,
        $name,
        $timestamp,
        $status = 0,
        $screenshotId = null
    )
    {
        $this->testId = $testId;
        $this->timestamp = $timestamp;
        $this->imageString = $imageString;
        $this->thumbnailString = $thumbnailString;
        $this->name = $name;
        $this->status = $status;
        $this->screenshotId = $screenshotId;
    }

    public function getImageFilename($dir="")
    {
        return $dir . "img$this->screenshotId.png";
    }

    public function getThumbnailFilename($dir="")
    {
        return $dir . "thm$this->screenshotId.png";
    }

    public function getDiffFilename($dir="")
    {
        return $dir . "diff$this->screenshotId.png";
    }

    public function getDiffThmFilename($dir="")
    {
        return $dir . "diff_thm$this->screenshotId.png";
    }

    /**
     * Put the screenshot as a file
     * @param string $dir Path to the file
     */
    public function screenshotToFile($dir)
    {
        $filename = $this->getImageFilename($dir);
        if (!file_exists($filename)) {
            file_put_contents($filename, $this->imageString);
        }
    }

    /**
     * Put a thumbnail as a file
     * @param string $dir Path to the file
     */
    public function thumbnailToFile($dir)
    {
        $filename = $this->getThumbnailFilename($dir);
        if (!file_exists($filename)) {
            file_put_contents($filename, $this->thumbnailString);
        }
    }

    /**
     * Convert inner status to string
     * @return string
     */
    public function statusToString()
    {
        switch($this->status) {
            case -1: return "Fail";
            case 0: return "New";
            case 1: return "Pass";
        }
    }
}
