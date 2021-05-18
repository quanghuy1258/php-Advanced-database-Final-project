<?php

include "mongodb.php";
include "properties.php";

function testTheSame($x, $y) {
  if ($x["_id"] != $y["_id"])
    return false;
  if ($x["propertyID"] != $y["propertyID"])
    return false;
  if ($x["hint"] != $y["hint"])
    return false;
  if ($x["tree"] != $y["tree"])
    return false;
  if ($x["isRemoved"] != $y["isRemoved"])
    return false;
  return true;
}

$manager = createMongoManagerInstance();

$ret = Properties::deletePropertyTree($manager, "test");
echo "Properties::deletePropertyTree => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Properties::newPropertyTree($manager, "test", "test hint");
echo "Properties::newPropertyTree => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Properties::checkPropertyTree($manager, "test");
echo "Properties::checkPropertyTree => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Properties::deletePropertyTree($manager, "test");
echo "Properties::deletePropertyTree => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Properties::newPropertyTree($manager, "test", "test hint");
echo "Properties::newPropertyTree => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Properties::listPropertyTree($manager);
echo "Properties::listPropertyTree => Expect: All property trees ~ Return:\n";
print_r($ret);

$x = new Properties($manager, "test");
echo "fetchTree => Expect: 1 ~ Return: ";
print_r($x->fetchTree()); echo "\n";
echo "getPropertyID, getTree, getIsFetch => Expect: All information ~ Return:\n";
print_r($x->getPropertyID()); echo "\n";
print_r($x->getTree());
print_r($x->getIsFetch()); echo "\n";

echo "editHint => Expect: 1 ~ Return: ";
print_r($x->editHint("edited hint")); echo "\n";
print_r($x->getTree()["hint"]); echo "\n";

echo "addProperty => Expect: 11 ~ Return: ";
print_r($x->addProperty([], "level_1", "this is level 1"));
print_r($x->addProperty(["level_1"], "level_2", "this is level 2"));
echo "\n";

echo "getTree, fetchTree, testTheSame => Expect: 1 ~ Return: ";
$y = new Properties($manager, "test");
$y->fetchTree();
print_r(testTheSame($x->getTree(), $y->getTree())); echo "\n";
print_r($y->getTree());

echo "editProperty => Expect: 1 ~ Return: ";
print_r($x->editProperty([], "level_1", "this is level 1 (edited)"));
echo "\n";

echo "getTree, fetchTree, testTheSame => Expect: 1 ~ Return: ";
$z = new Properties($manager, "test");
$z->fetchTree();
print_r(testTheSame($x->getTree(), $z->getTree())); echo "\n";
print_r($z->getTree());

?>

