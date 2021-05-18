<?php

include "mongodb.php";
include "orders.php";

function testTheSame($x, $y) {
  if ($x["_id"] != $y["_id"])
    return false;
  if ($x["orderID"] != $y["orderID"])
    return false;
  if ($x["belongsTo"] != $y["belongsTo"])
    return false;
  if ($x["information"] != $y["information"])
    return false;
  if ($x["hasMany"] != $y["hasMany"])
    return false;
  if ($x["isRemoved"] != $y["isRemoved"])
    return false;
  return true;
}

$manager = createMongoManagerInstance();

$ret = Orders::deleteOrder($manager, "order 0");
echo "Orders::deleteOrder => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Orders::newOrder($manager, "order 1", "user 1");
echo "Orders::newOrder => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Orders::checkOrder($manager, "order 1", false);
echo "Orders::checkOrder => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Orders::checkOrder($manager, "order 1", true);
echo "Orders::checkOrder + forceExists => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Orders::deleteOrder($manager, "order 1");
echo "Orders::deleteOrder => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Orders::newOrder($manager, "order 2", "user 2");
echo "Orders::newOrder => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Orders::listOrder($manager);
echo "Orders::listOrder => Expect: All orders ~ Return:\n";
print_r($ret);

$x = new Orders($manager, "order 2");
echo "fetchInformation => Expect: 1 ~ Return: ";
print_r($x->fetchInformation()); echo "\n";
echo "getOrderID, getInformation, getIsFetch => Expect: All information ~ Return:\n";
print_r($x->getOrderID()); echo "\n";
print_r($x->getInformation());
print_r($x->getIsFetch()); echo "\n";

echo "setProperty => Expect: 11 ~ Return: ";
print_r($x->setProperty("date", "02/09/2000"));
print_r($x->setProperty("sum", "1000000"));
echo "\n";

echo "getInformation, fetchInformation, testTheSame => Expect: 1 ~ Return: ";
$y = new Orders($manager, "order 2");
$y->fetchInformation();
print_r(testTheSame($x->getInformation(), $y->getInformation())); echo "\n";
print_r($y->getInformation());

echo "removeProperty => Expect: 1 ~ Return: ";
print_r($x->removeProperty("date"));
echo "\n";

echo "getInformation, fetchInformation, testTheSame => Expect: 1 ~ Return: ";
$z = new Orders($manager, "order 2");
$z->fetchInformation();
print_r(testTheSame($x->getInformation(), $z->getInformation())); echo "\n";
print_r($z->getInformation());

echo "addProduct => Expect: 111 ~ Return: ";
print_r($x->addProduct("product 1", "value 1_1"));
print_r($x->addProduct("product 1", "value 1_2"));
print_r($x->addProduct("product 2", "value 2"));
echo "\n";

echo "getInformation, fetchInformation, testTheSame => Expect: 1 ~ Return: ";
$t = new Orders($manager, "order 2");
$t->fetchInformation();
print_r(testTheSame($x->getInformation(), $t->getInformation())); echo "\n";
print_r($t->getInformation());

?>

