<?php

include "mongodb.php";
include "users.php";

function testTheSame($x, $y) {
  if ($x["_id"] != $y["_id"])
    return false;
  if ($x["userID"] != $y["userID"])
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

$ret = Users::deleteUser($manager, "user 0");
echo "Users::deleteUser => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Users::newUser($manager, "user 1");
echo "Users::newUser => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Users::checkUser($manager, "user 1", false);
echo "Users::checkUser => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Users::checkUser($manager, "user 1", true);
echo "Users::checkUser + forceExists => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Users::deleteUser($manager, "user 1");
echo "Users::deleteUser => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Users::newUser($manager, "user 2");
echo "Users::newUser => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Users::listUser($manager);
echo "Users::listUser => Expect: All users ~ Return:\n";
print_r($ret);

$x = new Users($manager, "user 2");
echo "fetchInformation => Expect: 1 ~ Return: ";
print_r($x->fetchInformation()); echo "\n";
echo "getUserID, getInformation, getIsFetch => Expect: All information ~ Return:\n";
print_r($x->getUserID()); echo "\n";
print_r($x->getInformation());
print_r($x->getIsFetch()); echo "\n";

echo "setProperty => Expect: 11 ~ Return: ";
print_r($x->setProperty("name", "Mike"));
print_r($x->setProperty("sex", "male"));
echo "\n";

echo "getInformation, fetchInformation, testTheSame => Expect: 1 ~ Return: ";
$y = new Users($manager, "user 2");
$y->fetchInformation();
print_r(testTheSame($x->getInformation(), $y->getInformation())); echo "\n";
print_r($y->getInformation());

echo "removeProperty => Expect: 1 ~ Return: ";
print_r($x->removeProperty("sex"));
echo "\n";

echo "getInformation, fetchInformation, testTheSame => Expect: 1 ~ Return: ";
$z = new Users($manager, "user 2");
$z->fetchInformation();
print_r(testTheSame($x->getInformation(), $z->getInformation())); echo "\n";
print_r($z->getInformation());

echo "addOrder => Expect: 11 ~ Return: ";
print_r($x->addOrder("order 1"));
print_r($x->addOrder("order 2"));
echo "\n";

echo "getInformation, fetchInformation, testTheSame => Expect: 1 ~ Return: ";
$t = new Users($manager, "user 2");
$t->fetchInformation();
print_r(testTheSame($x->getInformation(), $t->getInformation())); echo "\n";
print_r($t->getInformation());

?>

