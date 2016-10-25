<?php

namespace Lmc\Merlin\Server;

use Exception;

class Server
{
    // statuses
    const TST_STATUS_RUN = "RUN";
    const TST_STATUS_NEW = "NEW";
    const TST_STATUS_PASS = "PASS";
    const TST_STATUS_FAIL = "FAIL";

    /**
     * @var int A color identifier
     */
    private $markerColor;

    /**
     * @var resource Image as a result of comparing
     */
    private $diffImage;

    /**
     * @var int Debug level
     *  0 .. nothing
     *  1 .. summary
     *  2 .. details
     */
    public $debug = 0;

    /**
     * Actual test record
     * @var TestRecord
     */
    private $testRecord;

    /**
     * Create test record
     * Try to find baseline, if exists and join.
     *
     * @param Db $db
     * @param $environment
     * @param $job
     * @param $build
     * @param $testCase
     * @param $testName
     * @return bool
     */
    public function createTestRecord($db, $environment, $job, $build, $testCase, $testName)
    {
        if ($db) {
            $baseTestRecord = $db->queryBaseTestRecord($environment, $job, $testCase, $testName);
            $baseline = $baseTestRecord ? $baseTestRecord->testId : 0;
                $testRecord = new TestRecord(
                $environment,
                $job,
                $build,
                $testCase,
                $testName,
                self::TST_STATUS_RUN,
                time(),
                0,
                $baseline,
                null
            );
            $testRecord->testId = $db->save($testRecord);
            return $testRecord;
        }
        return false;
    }

    /**
     * Process screenshot
     * Create thumbnail
     * Save it to a file
     * Create the record in the database
     * Return status of the check
     *
     * @param Db $db
     * @param int $testId
     * @param string $imageString
     * @param string $name
     * @param int $baseline
     * @return bool result of the checkScreen (see below)
     */
    public function processScreenshot($db, $testId, $imageString, $name, $baseline)
    {
        if ($db) {
            $thumbnail = $this->createThumbnail(imagecreatefromstring($imageString));
            $thumbnailString = $this->resourceToString($thumbnail);
            $screenshot = new Screenshot($testId, $imageString, $thumbnailString, $name, time());
            if ($baseline > 0) {
                $result =  $this->checkScreen($db, $screenshot, $name, $baseline);
                $screenshot->status = $result ? Screenshot::SCR_STATUS_PASS : Screenshot::SCR_STATUS_FAIL;
            } else {
                $result = true;
                $screenshot->status = Screenshot::SCR_STATUS_NEW;
            }
            $screenshot->screenshotId = $db->save($screenshot);   // save actual screenshot into database
            return $result;
        }
        return false;
    }

    /**
     * Finish test record
     * Resolve end time and status.
     * Save it into database.
     *
     * @param Db $db
     * @param int $testId
     */
    public function finishTestRecord($db, $testId)
    {
        $testRecord = $db->getTestRecord($testId);
        $testRecord->end = time();
        $screenshots = $db->queryScreenshots($testId);
        foreach($screenshots as $oneScreenshot) {
            if ($oneScreenshot->status == Screenshot::SCR_STATUS_FAIL) {
                $testRecord->status = self::TST_STATUS_FAIL;
                break;
            }
        }
        if ($testRecord->status == self::TST_STATUS_RUN) {    // there were no errors
            $testRecord->status = self::TST_STATUS_PASS;
        }
        if ($testRecord->isBaseline()) {
            $testRecord->status = self::TST_STATUS_NEW;
        }
        $db->update($testRecord);
    }

    /**
     * Compare screenshot with baseline
     *
     * @param Db $db
     * @param Screenshot $screenshot
     * @param string $name
     * @param int $baseline
     * @return bool True if check is correct - no difference between screenshots
     */
    public function checkScreen($db, $screenshot, $name, $baseline)
    {
        $result = true;
        $actualImageResource = imagecreatefromstring($screenshot->imageString);
        $baseScreenshot = $db->queryScreenshotByName($baseline, $name);
        $resultOfComparison = $this->compareScreenshots(
            imagecreatefromstring($baseScreenshot->imageString),
            $actualImageResource
        );
        if ($resultOfComparison) {
            $screenshot->status = 1;
            $result = false;
            // TODO save result to db
        }
        return $result;
    }

    /**
     * Compare two image resources
     *
     * @param resource $image1 Image resource 1
     * @param resource $image2 Image resource 2
     * @return bool
     */
    public function areImageResourcesEqual($image1, $image2)
    {
        return $this->resourceToString($image1) == $this->resourceToString($image2);
    }

    /**
     * Compare two screenshots.
     *
     * @param resource $image1
     * @param resource $image2
     * @return null|resource Null if there are no differences or - diff image.
     * @throws Exception
     */
    public function compareScreenshots($image1, $image2)
    {
        $startTimestamp = microtime(true);
        $result = null;
        $image1Width = imagesx($image1);
        $image1Height = imagesy($image1);
        if (!$this->areImagesEqual($image1, $image2)) {
            $this->diffImage = imagecreatetruecolor($image1Width, $image1Height);
            imagealphablending($this->diffImage, true);
            $this->markerColor = imagecolorallocatealpha($this->diffImage, 0, 0xFF, 0x88, 0x50);
            imagecopy($this->diffImage, $image2, 0, 0, 0, 0, $image1Width, $image1Height);
            $this->findDiffRecursively($image1, $image2, 0, 0, $image1Width, $image1Height, 0);
            $result = $this->diffImage;
        };
        $endTimestamp = microtime(true);
        $consumedTime = round($endTimestamp - $startTimestamp, 3);
        $this->debugMessage("Screenshots compared in $consumedTime s.\n");
        $mem = memory_get_peak_usage(true) / 1024;
        $this->debugMessage("Used memory: $mem kB\n");
        if ($result) {
            $this->testRecord->status = self::TST_STATUS_FAIL;

        }
        return $result;
    }

    /**
     * Create the thumbnail of the screenshot
     * @param resource $image Screenshot
     * @return resource Thumbnail
     */
    private function createThumbnail($image)
    {
        $newWidth = 100;
        $width = imagesx($image);
        $height = imagesy($image);

        $newHeight = ($height / $width) * $newWidth;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmp, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return $tmp;
    }

    /**
     * Compare image info of two images.
     *
     * @param resource $image1 Image resource 1
     * @param resource $image2 Image resource 2
     * @return bool True if images are equal
     */
    public function areImagesInfoEqual($image1, $image2)
    {
        $result = $this->imageInfo($image1) == $this->imageInfo($image2);
        return $result;
    }

    /**
     * Compare two images (by resource) and compare them.
     *
     * @param resource $image1 Resource of image 1
     * @param resource $image2 Resource of image 2
     * @return bool True if images are equal
     */
    public function areImagesEqual($image1, $image2)
    {
        $result = $this->areImageResourcesEqual($image1, $image2);
        return $result;
    }

    /**
     * Get image info
     *
     * @param resource $image Image resource
     * @return string Image info
     */
    public function imageInfo($image)
    {
        $sx = imagesx($image);
        $sy = imagesy($image);
        $result = "Size: $sx x $sy\n";
        return $result;
    }

    /**
     * Convert resource of image into string.
     *
     * Taken from: http://www.php.net/manual/en/book.image.php#93393
     * @param resource $image Image resource
     * @return string Image content string
     */
    private function resourceToString($image)
    {
        ob_start();
        imagepng($image);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * Find difference between two rectangles.
     * Colorize this difference into this->diffImage.
     * Algorithm based on dividing rectangle into two parts and processing them recursivly.
     * The smallest rectangle is 16 pixels (or less) in both axises.
     *
     * @param resource $rect1 Image rectangle 1
     * @param resource $rect2 Image rectangle 2
     * @param int $left Left position (pixels in X axis)
     * @param int $top Top position (pixels in Y axis)
     * @param int $width Rectangle width
     * @param int $height Rectangle height
     * @param int $level Level of the recursion (can be used for controlling of the depth level).
     */
    private function findDiffRecursively($rect1, $rect2, $left, $top, $width, $height, $level)
    {
        $this->debugMessage($imageParameters = "$level: $left:$top $width x $height\n", 2);
        if ($this->areImagesEqual($rect1, $rect2)) {
            return;
        };
        if ($width <= 16 && $height <= 16) {
            imagefilledrectangle($this->diffImage, $left, $top, $left + $width - 1, $top + $height - 1,
                $this->markerColor);
            return;
        }
        $leftA = $left;
        $leftB = $left;
        $topA = $top;
        $topB = $top;
        $widthA = $width;
        $widthB = $width;
        $heightA = $height;
        $heightB = $height;
        $xB = 0;
        $yB = 0;
        if ($width > $height) { // divide in width
            $widthA = (int) floor($width / 2);
            $widthB = $width - $widthA;
            $leftB = $left + $widthA;
            $xB = $widthA;
        } else {                // divide in height
            $heightA = (int) floor($height / 2);
            $heightB = $height - $heightA;
            $topB = $top + $heightA;
            $yB = $heightA;
        }
        $cropA = ['x' => 0, 'y' => 0, 'width' => $widthA, 'height' => $heightA];
        $cropB = ['x' => $xB, 'y' => $yB, 'width' => $widthB, 'height' => $heightB];
        $rect1A = imagecrop($rect1, $cropA);
        $rect2A = imagecrop($rect2, $cropA);
        $rect1B = imagecrop($rect1, $cropB);
        $rect2B = imagecrop($rect2, $cropB);
        $this->findDiffRecursively($rect1A, $rect2A, $leftA, $topA, $widthA, $heightA, $level + 1);
        $this->findDiffRecursively($rect1B, $rect2B, $leftB, $topB, $widthB, $heightB, $level + 1);
    }

    /**
     * Print the debug message depending on the debug level.
     *
     * @param string $message Debug message
     * @param int $level Debug level (default is 1)
     */
    private function debugMessage($message, $level = 1)
    {
        if ($this->debug == $level) {
            echo $message;
        }
    }
}
