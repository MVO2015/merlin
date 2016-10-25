<?php

use Lmc\Merlin\Server\Db;
use Lmc\Merlin\Server\Server;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$server = new Server();
$db = new Db();
$db->open();
$server->finishTestRecord($db, $_SESSION['testId']);
$db->dbConnection->close();

session_destroy();
