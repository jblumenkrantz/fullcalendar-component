<?php
require_once ('vendor/autoload.php');

$pinsqli = DistributedMySQLConnection:: readInstance();
$resulti = $pinsqli->query("select * from users");

print_r($resulti);
?>
