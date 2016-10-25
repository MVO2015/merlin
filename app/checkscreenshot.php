<?php
use Lmc\Merlin\Server\Db;
use Lmc\Merlin\Server\Server;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$server = new Server();
$db = new Db();
$db->open();
$result = $server->processScreenshot(
    $db,
    $_SESSION["testId"],
    $_POST["imageString"],
    $_POST["name"],
    $_SESSION["baseline"]
    );
$db->dbConnection->close();
echo $result;

