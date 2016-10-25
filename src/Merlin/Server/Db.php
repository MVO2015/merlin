<?php

namespace Lmc\Merlin\Server;

use mysqli;

/**
 * Database connectivity and methods
 *
 */
class Db
{
    // path to the images
    const IMG_DIR = "../tmp/";

    // authentication
    const DEFAULT_USER = "teamio";
    const DEFAULT_PASSWORD = "123456";

    /**
     * Db connection object
     * @var mysqli
     */
    public $dbConnection;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @param null|string $user Username (optional), default is given by constant above
     * @param null|string $password Password (optional), default is given by constant above
     */
    public function __construct($user=null, $password=null)
    {
        if (!isset($user)) {
            $user = self::DEFAULT_USER;
        }
        if (!isset($password)) {
            $password = self::DEFAULT_PASSWORD;
        }
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Open database connection
     * @return bool True if connected
     */
    public function open()
    {
        $this->dbConnection = mysqli_connect('localhost', $this->user, $this->password, 'merlin');
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            return false;
        }
        return true;
    }

    /**
     * List of tests in HTML form
     * TODO move somewhere
     */
    public function getTests()
    {
        $result = $this->dbConnection->query(
            "SELECT environment, job, build, testcase, test.name as name, testId, status, start, end, baseline " .
            "FROM test ORDER BY environment, job, build, testcase, testId, test.name, testId, start, end"
        );
        $builds = [];
        while ($sqlRow = $result->fetch_object()) {
            $testRecord = $this->createTestRecord($sqlRow);
            $build = $testRecord->build;
            if ($testRecord->isBaseline()) {
                $build = "baseline";
            }
            $builds[$testRecord->environment][$testRecord->job][$build][$testRecord->testCase][] = $testRecord;
        }
        $result->free();
        print "<ul>\n";
        foreach ($builds as $environment => $jobs) {
            print "<li class='environment' onclick='toggleList(\"$environment\");'>$environment</li>";
            print "<ul class='collapsible expanded' id='$environment'>\n";
            foreach ($jobs as $job => $builds) {
                print "<li class='job' onclick='toggleList(\"$environment-$job\");'>$job</li>";
                print "<ul class='collapsible expanded' id='$environment-$job'>\n";
                foreach ($builds as $buildNum => $testCases) {
                    end($builds);
                    $lastElementKey = key($builds);
                    if ($buildNum == $lastElementKey) {
                        $folder = "expanded";
                    } else {
                        $folder = "collapsed";
                    }
                    print "<li class='build' onclick='toggleList(\"$environment-$job-$buildNum\");'>$buildNum</li>";
                    print "<ul class='collapsible $folder' id='$environment-$job-$buildNum'>\n";
                    foreach ($testCases as $testCase => $testRecords) {
                        print "<li class='testCase' onclick='toggleList(\"$environment-$job-$buildNum-$testCase\");'>$testCase</li>";
                        print "<ul class='testList collapsible collapsed' id='$environment-$job-$buildNum-$testCase'>\n";
                        /** @var TestRecord $oneTestRecord */
                        foreach ($testRecords as $oneTestRecord) {
                            print $oneTestRecord->toHtml();
                        }
                        print "</ul>\n";
                    }
                    print "</ul>\n";
                }
                print "</ul>\n";
            }
            print "</ul>\n";
        }
        print "</ul>\n";
    }

    /**
     * Query Baseline TestRecord
     * Parameters are search criteria
     *
     * @param string $environment
     * @param string $job
     * @param string $testCase
     * @param string $testName
     * @return null|TestRecord Null if there is no baseline record or object of TestRecord
     */
    public function queryBaseTestRecord($environment, $job, $testCase, $testName)
    {
        $result = $this->dbConnection->query(
            "SELECT environment, job, build, testcase, name, status, start, end, baseline, testId " .
            "FROM test WHERE environment='$environment' " .
            "and job='$job' and testcase='$testCase' and name='$testName'");
        $sqlRow = $result->fetch_object();
        $result->free();
        if ($sqlRow) {
            return $this->createTestRecord($sqlRow);
        }
        return null;
    }

    /**
     * Query screenshot by testId and name
     *
     * @param int $testId
     * @param string $name
     * @return Screenshot|null Screenshot if it is found or null
     */
    public function queryScreenshotByName($testId, $name)
    {
        $result = $this->dbConnection->query(
            "SELECT `screenshotId`, `testId`, `timestamp`, `image`, `thumbnail`, `name`, status " .
            " FROM screenshot WHERE testId=$testId and name='$name'"
        );
        $sqlRow = $result->fetch_object();
        $result->free();
        if ($sqlRow) {
            $screenshot = $this->createScreenshot($sqlRow);
            $screenshot->imageString = file_get_contents($screenshot->getImageFilename(self::IMG_DIR));
            return $screenshot;
        }
        return null;
    }

    /**
     * Get thumbnails in HTML form
     * TODO move somewhere
     * @param $testId
     */
    public function getThumbnails($testId)
    {
        $screenshots = $this->queryScreenshots($testId);
        echo "<ul class='thumbnail'>\n";
        /** @var Screenshot $oneScreenshot */
        foreach ($screenshots as $oneScreenshot) {
            $filename = $oneScreenshot->getThumbnailFilename(self::IMG_DIR);
            $status = "status" .$oneScreenshot->statusToString();
            echo "<li class='$status'>\n";
            echo "<span><img src='$filename'></span>\n";
            echo "<span>$oneScreenshot->name</span>";
            echo "</li>\n";
        }
        echo "</ul>\n";
    }

    /**
     * List of screenshots with given testId
     *
     * @param int $testId Test id
     * @return Screenshot[]|null List of Screenshots
     */
    public function queryScreenshots($testId)
    {
        $result = $this->dbConnection->query(
            "SELECT screenshotId, testId, timestamp, image, thumbnail, name, status FROM screenshot " .
            "WHERE testId=$testId"
        );
        if ($result) {
            $screenshots = [];
            while ($sqlRow = $result->fetch_object()) {
                $screenshots[] = $this->createScreenshot($sqlRow);
            }
            return $screenshots;
        }
        return null;
    }

    /**
     * Save screenshot into database
     * Save screenshot as a file
     * Save thumbnail as a file
     *
     * @param Screenshot $screenshot
     * @return null|int Screenshot id or null if insert into db was not successful.
     */
    private function saveScreenshot($screenshot)
    {
        $query = "INSERT INTO screenshot (testId, timestamp, image, name, thumbnail, status) " .
            "VALUES ($screenshot->testId, $screenshot->timestamp, " .
            "null, '$screenshot->name', null, $screenshot->status)";
        $result = $this->dbConnection->query($query);

        if ($result) {
            $screenshot->screenshotId = $this->dbConnection->insert_id;
            $screenshot->screenshotToFile(self::IMG_DIR);
            $screenshot->thumbnailToFile(self::IMG_DIR);
            return $screenshot->screenshotId ;
        }
        return null;
    }

    /**
     * Save TestRecord into database
     *
     * @param TestRecord $testRecord
     * @return null|int  Test id or null if insert into db was not successful.
     */
    public function saveTestRecord($testRecord)
    {
        $query = "INSERT INTO test (environment, job, build, testcase, name, start, status, baseline) " .
            "VALUES ('$testRecord->environment', '$testRecord->job', '$testRecord->build', " .
            "'$testRecord->testCase', '$testRecord->testName', " .
            "$testRecord->start, '$testRecord->status', $testRecord->baseline)";
        $result = $this->dbConnection->query($query);
        if ($result) {
            return $this->dbConnection->insert_id;
        }
        return null;
    }

    /**
     * Update TestRecord
     *
     * @param TestRecord $testRecord
     * @return bool|\mysqli_result
     */
    private function updateTestRecord($testRecord)
    {
        $query = "UPDATE test SET status='{$testRecord->status}', " .
            "end={$testRecord->end} WHERE testId={$testRecord->testId}";
        $result = $this->dbConnection->query($query);
        return $result;
    }

    /**
     * Save object into database
     * Object is recognized by class name
     *
     * @param $object
     * @return bool|int|null
     */
    public function save($object)
    {
        $className = get_class($object);
        if (strpos($className, "Screenshot") > 0) {
            return $this->saveScreenshot($object);
        }
        if (strpos($className, "TestRecord") > 0) {
            return $this->saveTestRecord($object);
        }
        return false;
    }

    /**
     * Update object into database
     * Object is recognized by class name
     *
     * @param $object
     * @return bool|\mysqli_result
     */
    public function update($object)
    {
        $className = get_class($object);
        if (strpos($className, "TestRecord") > 0) {
            return $this->updateTestRecord($object);
        }
        return false;
    }

    /**
     * Create TestRecord from sqlRow
     *
     * @param $sqlRow
     * @return TestRecord
     */
    private function createTestRecord($sqlRow)
    {
        $testRecord = new TestRecord(
            $sqlRow->environment,
            $sqlRow->job,
            $sqlRow->build,
            $sqlRow->testcase,
            $sqlRow->name,
            $sqlRow->status,
            $sqlRow->start,
            $sqlRow->end,
            $sqlRow->baseline,
            $sqlRow->testId
        );
        return $testRecord;
    }

    /**
     * Create Screenshot from sqlRow
     *
     * @param $sqlRow
     * @return Screenshot
     */
    private function createScreenshot($sqlRow)
    {
        $screenshot = new Screenshot(
            $sqlRow->testId,
            $sqlRow->image,
            $sqlRow->thumbnail,
            $sqlRow->name,
            $sqlRow->timestamp,
            $sqlRow->status,
            $sqlRow->screenshotId
        );
        return $screenshot;
    }

    /**
     * Get TestRecord from db by test id.
     * @param $testId
     * @return TestRecord
     */
    public function getTestRecord($testId)
    {
        $result = $this->dbConnection->query(
            "SELECT environment, job, build, testcase, test.name as name, testId, status, start, end, baseline "
            . "FROM test WHERE testId='$testId'");
        $sqlRow = $result->fetch_object();
        return $this->createTestRecord($sqlRow);
    }
}
