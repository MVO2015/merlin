<?php

namespace Lmc\Merlin;

/**
 * Merlin client
 *
 */
class Merlin
{
    /**
     * URL of Merlin remote server
     * @var string
     */
    public $remoteServerUrl;

    /**
     * URL of Merlin remote server
     * @param string $remoteServer
     */
    public function __construct($remoteServer)
    {
        $this->remoteServerUrl = $remoteServer;
    }

    /**
     * @param string $webDriver Should be Selenium RemoteWebDriver reference
     * @param string $name Unique name of the screen in this test
     * @return mixed True if screenshot has no difference
     */
    public function checkScreen($webDriver, $name) {
        // $actualImageResource = $webDriver->takeScreenshot();
        // mock:
        $actualScreenshotFileName = "screenshot1b.png";
        $actualImageResource = imagecreatefrompng($actualScreenshotFileName);
        $imageString = $this->resourceToString($actualImageResource);
        $url = $this->remoteServerUrl . "app/checkscreenshot.php";
        $data = 'imageString=' . urlencode($imageString);
        $data .= '&name=' . urlencode($name);
        return $this->sendDataToServer($url, $data);
    }

    /**
     * Open session
     *
     * @param string $environment
     * @param string $job
     * @param string $build
     * @param string $testCase
     * @param string $testName
     * @return mixed True if session is successfully opened
     */
    public function open($environment, $job, $build, $testCase, $testName)
    {

        $url = $this->remoteServerUrl . "app/opensession.php";
        $data = 'environment=' . urlencode($environment);
        $data .= '&job=' . urlencode($job);
        $data .= '&build=' .urlencode($build);
        $data .= '&testCase=' . urlencode($testCase);
        $data .= '&testName=' . urlencode($testName);
        return $this->sendDataToServer($url, $data);
    }

    /**
     * Close session
     * Test will be finished
     *
     */
    public function close()
    {
        $url = $this->remoteServerUrl . "app/closesession.php";
        $this->sendDataToServer($url, "");
    }

    /**
     * Send data via curl
     *
     * @param string $url
     * @param string $data
     * @return mixed Answer
     */
    private function sendDataToServer($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

        //Tell cUrl about the cookie file
        curl_setopt($ch,CURLOPT_COOKIEJAR, "cookie");  //tell cUrl where to write cookie data
        curl_setopt($ch,CURLOPT_COOKIEFILE, "cookie"); //tell cUrl where to read cookie data from

        return curl_exec( $ch );
    }

    /**
     * TODO not duplicate this code
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
}
