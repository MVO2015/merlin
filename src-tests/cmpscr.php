<?php

use Lmc\Merlin\Merlin;

require __DIR__ . '/../vendor/autoload.php';

$baseLineScreen = "screenshot1a.png";
$actualScreen =   "screenshot1b.png";

$baseLineScreen = __DIR__ . "\\" . $baseLineScreen;
$actualScreen = __DIR__ . "\\" . $actualScreen;
$merlin = new Merlin();
$merlin->debug = 1;
$result = $merlin->compareScreenshots($baseLineScreen, $actualScreen);
echo $result ? "DIFFERENT" : "EQUAL";
exit($result ? -1 : 0);

