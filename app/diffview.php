<?php
    use Lmc\Merlin\Merlin;

    require __DIR__ . '/../vendor/autoload.php';

    $baseLineScreen = "../src-tests/screenshot1a.png";
    $actualScreen = "../src-tests/screenshot1b.png";

    $merlin = new Merlin();
    $merlin->open("teamio", "123456", 1, "Web test");
    $result = $merlin->compareScreenshots($baseLineScreen, $actualScreen);
?>

<!DOCTYPE HTML>
<html>
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <link rel="stylesheet" type="text/css" href="css/merlin.css">
    <link rel="stylesheet" type="text/css" href="css/magnify.css">
    <title>Merlin</title>
</head>
<body>
<h1>Merlin</h1>
<h2 class="inset">Screenshots differ:</h2>
<div id="container">
    <div class="screenPanel" id="leftScreenshot">
        <h3>Baseline screenshot</h3>
        <div class="magnify">
            <?php
            echo '
                <div class="large" style="background: url(\'' . $baseLineScreen . '\') no-repeat;"></div>
                <img class="screenshot small" src="' . $baseLineScreen . '">
            ';
            ?>
        </div>
    </div>
    <div class="screenPanel" id="rightScreenshot">
        <h3>Actual screenshot</h3>
        <div class="magnify">
            <?php
            echo '
                <div class="large" style="background: url(\'' . $result . '\') no-repeat;"></div>
                <img class="screenshot small" src="' . $result . '">
            ';
            ?>
        </div>
    </div>
</div>

<!-- Lets load up prefixfree to handle CSS3 vendor prefixes -->
<script src="http://thecodeplayer.com/uploads/js/prefixfree.js" type="text/javascript"></script>
<!-- You can download it from http://leaverou.github.com/prefixfree/ -->

<!-- Time for jquery action -->
<script src="http://thecodeplayer.com/uploads/js/jquery-1.7.1.min.js" type="text/javascript"></script>

<script src="js/magnify.js" type="text/javascript"></script>

</body>
</html>
