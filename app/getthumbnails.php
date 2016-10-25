<?php
use Lmc\Merlin\Server\Db;

require __DIR__ . '/../vendor/autoload.php';

$db = new Db();
$db->open("teamio", "123456");
$db->getThumbnails($_GET["testid"]);
