<?php

use Lmc\Merlin\Merlin;

require __DIR__ . '/../vendor/autoload.php';

$serverUrl = "http://localhost:8080/merlin/";
$merlin = new Merlin($serverUrl);

$environment = "jenkinsqa.devel5.lmc.cz:8080";  // e.g. jenkins url
$job = "Teamio RED";    // e.g. jenkins job
$build = 110;   // e.g. jenkins build
$testCase = "MujTestCase60";    // e.g. name of the test case class
$testName = "LoginPageTest";    // e.g. name of the test method
$webDriver = "driver";  // selenium remote web driver reference, but here is just a string

$merlin->open($environment, $job, $build, $testCase, $testName);
$merlin->checkScreen($webDriver, "Login");   // screen is faked
$merlin->checkScreen($webDriver, "Menu");    // screen is faked
$merlin->close();
