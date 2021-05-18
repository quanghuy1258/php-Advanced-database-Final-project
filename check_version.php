<?php

include "mongodb.php";

$manager = createMongoManagerInstance();
$query = new MongoDB\Driver\Query([]);
$cursor = $manager->executeQuery("admin.system.version", $query);
print_r($cursor->toArray());

?>
