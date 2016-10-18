<?php
use Lmc\Merlin\Db;

require __DIR__ . '/../vendor/autoload.php';

$db = new Db();
$db->open("teamio", "123456");
$db->getScreenshots($_GET["testid"]);
