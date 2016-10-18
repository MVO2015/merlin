<?php
use Lmc\Merlin\Db;

require __DIR__ . '/../vendor/autoload.php';

$db = new Db();
$db->open("teamio", "123456");
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="testview.css">
    <title>List of tests</title>
</head>
<body>

<?php
$db->getTests();
?>

</body>
<script src="loadimages.js"></script>
</html>
