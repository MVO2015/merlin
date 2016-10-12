<?php


namespace Lmc\Merlin;

use Exception;

/**
 * Compare two screenshots and find (and mark) difference.
 *
 */
class Merlin
{
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
     * @param string $fileName1 File name of the screenshot 1
     * @param string $fileName2 File name of the screenshot 2
     * @return bool|string False if there are no differences or - Filename of diff screen.
     * @throws Exception if file(s) not exist(s)
     */
    public function compareScreenshots($fileName1, $fileName2)
    {
        $startTimestamp = microtime(true);
        $result = false;
        $image1 = imagecreatefrompng($fileName1);
        $image2 = imagecreatefrompng($fileName2);
        if (!($image1 && $image2)) {
            throw(new Exception("Cannot load screenshot(s)."));
        }
        $image1Width = imagesx($image1);
        $image1Height = imagesy($image1);
//        if (!$this->compareImageInfo($image1, $image2)) {
//            return false;
//        };
        if (!$this->areImagesEqual($image1, $image2)) {
            $this->diffImage = imagecreatetruecolor($image1Width, $image1Height);
            imagealphablending($this->diffImage, true);
            $this->markerColor = imagecolorallocatealpha($this->diffImage, 0 , 0xFF, 0x88, 0x50);
            imagecopy($this->diffImage, $image2, 0, 0, 0, 0, $image1Width, $image1Height);
            $this->findDiffRecursively($image1, $image2, 0, 0, $image1Width, $image1Height, 0);
            $screenDiffsFileName = "scr$startTimestamp.png";
            imagepng($this->diffImage, $screenDiffsFileName);
            $result = $screenDiffsFileName;
        };
        $endTimestamp = microtime(true);
        $consumedTime = round($endTimestamp - $startTimestamp, 3);
        $this->debugMessage("Screenshots compared in $consumedTime s.\n");
        $mem = memory_get_peak_usage(true) / 1024;
        $this->debugMessage("Used memory: $mem kB\n");
        return $result;
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
        $this->resultMessage("Info:", $result);
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
        $this->resultMessage("Content:", $result);
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

    private function resultMessage($label, $result)
    {
        $this->debugMessage($label . ($result ? "EQUAL" : "DIFFERENT") . "\n", 2);
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
        $this->debugMessage($imageParameters =  "$level: $left:$top $width x $height\n", 2);
        if ($this->areImagesEqual($rect1, $rect2)) {
            return;
        };
        if ($width <= 16 && $height <= 16) {
            imagefilledrectangle($this->diffImage, $left, $top, $left + $width - 1, $top + $height - 1, $this->markerColor);
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
        $cropA= ['x' => 0, 'y' => 0, 'width' => $widthA, 'height' => $heightA];
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
    private function debugMessage($message, $level=1)
    {
        if ($this->debug == $level) {
            echo $message;
        }
    }
}
