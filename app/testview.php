<?php
use Lmc\Merlin\Server\Db;

require __DIR__ . '/../vendor/autoload.php';

$db = new Db();
$db->open("teamio", "123456");
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/testview.css">
    <title>List of tests</title>
</head>
<body>

<?php
$db->getTests();
?>

</body>
<script src="js/loadimages.js"></script>
<script src="js/testview.js"></script>
</html>
