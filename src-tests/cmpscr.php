<?php

use Lmc\Merlin\Merlin;

require __DIR__ . '/../vendor/autoload.php';

$baseLineScreen = "screenshot1a.png";
$actualScreen =   "screenshot1b.png";

$baseLineScreen = __DIR__ . "\\" . $baseLineScreen;
$actualScreen = __DIR__ . "\\" . $actualScreen;
$merlin = new Merlin();
$merlin->debug = 1;
$merlin->open("teamio", "123456", 3, "MyTest");
$result = $merlin->compareScreenshots($baseLineScreen, $actualScreen, "Name X");
$result = $merlin->compareScreenshots($baseLineScreen, __DIR__ . "\\" . "screenshot2.png", "Name ABC");
$merlin->close();
echo $result ? "DIFFERENT" : "EQUAL";
exit($result ? -1 : 0);

