<?php
function createMongoManagerInstance() {
  $uri = "mongodb://localhost:27017";
  return new MongoDB\Driver\Manager($uri);
}
?>
