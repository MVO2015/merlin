<?php

namespace Lmc\Merlin;

use mysqli;

class Db
{
    /**
     * Db connection object
     * @var mysqli
     */
    public $dbConnection;
    public function open($user, $password)
    {
        $this->dbConnection = mysqli_connect('localhost', $user, $password, 'merlin');
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            return false;
        }
        return true;
    }

    public function getTests()
    {
        $result = $this->dbConnection->query(
            "SELECT test.batchId AS batchId, build, testId, test.name as name, status, start, end FROM test, batch " .
            "WHERE batch.batchId = test.batchId ORDER BY test.batchId, testId, test.name, start, end");
        $batch = [];
        $build = [];
        /** @var array $record */
        while ($record = $result->fetch_object()) {
            $batch[$record->batchId][] = $record;
            $build[$record->batchId] = $record->build;
        }
        $result->free();
        print "<ul>\n";
        foreach ($batch as $batchId => $tests) {
            print "<li class='build'>$build[$batchId]</li>";
            print "<ul>\n";
            foreach ($tests as $record) {
                print $this->renderTest($record);
            }
            print "</ul>\n";
        }
        print "</ul>\n";
    }

    public function renderTest($testItem)
    {
        $output = "<li class='testItem'>\n";
        $onclick = "toggleImages(\"$testItem->testId\");";
        $output .= "<table><tr><td class= 'testName {$testItem->status}' onclick='$onclick'>{$testItem->name}</td>\n";
        $output .= "<td class='testStatus {$testItem->status}'>{$testItem->status}</td>\n";
        $start = date("Y-m-d H:i:s", ($testItem->start));
        $output .= "<td class='testStart'>{$start}</td>\n";
        if ($testItem->end > 0) {
            $end = date("Y-m-d H:i:s", ($testItem->end));
        } else {
            $end = "";
        }
        $output .= "<td class='testEnd'>{$end}</td></tr></table>\n";
        $output .= "<div id='testid$testItem->testId'></div>";
        $output .= "</li>\n";
        return $output;
    }

    private function screenshotToFile($id)
    {
        $filename = $this->getImageFilename($id);
        if (!file_exists("../tmp/$filename")) {
            $result = $this->dbConnection->query("SELECT image FROM screenshot WHERE screenshotId=$id");
            $screenshot = $result->fetch_object();
            $result->free();
            file_put_contents("../tmp/$filename", $screenshot->image);
        }
    }

    private function thumbnailToFile($id)
    {
        $filename = $this->getThumbnailFilename($id);
        if (!file_exists("../tmp/$filename")) {
            $result = $this->dbConnection->query("SELECT thumbnail FROM screenshot WHERE screenshotId=$id");
            $screenshot = $result->fetch_object();
            $result->free();
            file_put_contents("../tmp/$filename", $screenshot->thumbnail);
        }
    }

    private function getScreenshotName($id)
    {
        $result = $this->dbConnection->query("SELECT name FROM screenshot WHERE screenshotId=$id");
        $name = $result->fetch_object()->name;
        $result->free();
        return $name;
    }

    public function getScreenshots($testId)
    {
        $testRecord = new TestRecord($this->dbConnection, $testId);
        $screenshotIds = $testRecord->queryScreenshotIds();

        echo "<ul class='thumbnail'>\n";
        foreach ($screenshotIds as $oneScreenshotId) {
            $filename = $this->getThumbnailFilename($oneScreenshotId);
            $this->thumbnailToFile($oneScreenshotId);
            echo "<li>\n";
            $name = $this->getScreenshotName($oneScreenshotId);
            echo "<span><img src='../tmp/$filename'></span>\n";
            echo "<span>$name</span>";
            echo "</li>\n";
        }
        echo "</ul>\n";
    }

    public function deleteScreenshots($testId)
    {
        $result = $this->dbConnection->query("SELECT screenshotId FROM screenshot WHERE testId=$testId");
        if ($result) {
            $screenshots = $result->fetch_all();
            $result->free();

            foreach ($screenshots as $oneScreenshot) {
                $screenshotId = $oneScreenshot[0];
                $filename = $this->getThumbnailFilename($screenshotId);
                unlink("../tmp/$filename");
            }
        }
    }

    private function getImageFilename($id)
    {
        return "img$id.png";
    }

    private function getThumbnailFilename($id)
    {
        return "thm$id.png";
    }


}
