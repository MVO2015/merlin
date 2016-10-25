<?php
use Lmc\Merlin\Server\Db;
use Lmc\Merlin\Server\Server;
use Lmc\Merlin\Server\TestRecord;

require __DIR__ . '/../vendor/autoload.php';
session_start();

$db = new Db();
$db->open();
$server = new Server();
$_SESSION['environment'] = $_POST['environment'];
$_SESSION['job'] = $_POST['job'];
$_SESSION['build'] = $_POST['build'];
$_SESSION['testCase'] = $_POST['testCase'];
$_SESSION['testName'] = $_POST['testName'];

/** @var TestRecord $testRecord */
$testRecord = $server->createTestRecord(
    $db,
    $_SESSION['environment'],
    $_SESSION['job'],
    $_SESSION['build'],
    $_SESSION['testCase'],
    $_SESSION['testName']
);
$_SESSION['testId'] = $testRecord->testId;
$_SESSION['baseline'] = $testRecord->baseline;

$db->dbConnection->close();

return $testRecord ? true : false;
