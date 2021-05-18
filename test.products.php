<?php

include "mongodb.php";
include "products.php";

function testTheSame($x, $y) {
  if ($x["_id"] != $y["_id"])
    return false;
  if ($x["productID"] != $y["productID"])
    return false;
  if ($x["hint"] != $y["hint"])
    return false;
  if ($x["detail"] != $y["detail"])
    return false;
  if ($x["isRemoved"] != $y["isRemoved"])
    return false;
  if ($x["updatedTime"] != $y["updatedTime"])
    return false;
  return true;
}

$manager = createMongoManagerInstance();

$ret = Products::deleteProduct($manager, "product test");
echo "Products::deleteProduct => Expect: 1 or nothing ~ Return: ";
print_r($ret); echo "\n";

$ret = Products::newProduct($manager, "product test", "product hint");
echo "Products::newProduct => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Products::checkProduct($manager, "product test");
echo "Products::checkProduct => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Products::deleteProduct($manager, "product test");
echo "Products::deleteProduct => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Products::newProduct($manager, "product test", "product hint");
echo "Products::newProduct => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Products::listProduct($manager);
echo "Products::listProduct => Expect: All products ~ Return:\n";
print_r($ret);

$x = new Products($manager, "product test");
echo "fetchTree => Expect: 1 ~ Return: ";
print_r($x->fetchDetail()); echo "\n";
echo "getProductID, getDetail, getIsFetch => Expect: All information ~ Return:\n";
print_r($x->getProductID()); echo "\n";
print_r($x->getDetail());
print_r($x->getIsFetch()); echo "\n";

echo "editHint => Expect: 1 ~ Return: ";
print_r($x->editHint("product hint (edited)")); echo "\n";
print_r($x->getDetail()["hint"]); echo "\n";

echo "addProperty => Expect: 11111 ~ Return: ";
print_r($x->addProperty([], "level_1", "this is level 1", "value 1"));
print_r($x->addProperty([], "level_2", "this is level 2", "value 2"));
print_r($x->addProperty(["level_2"], "level_2_1", "this is level 2_1", "value 2_1"));
print_r($x->addProperty(["level_2"], "level_2_2", "this is level 2_2", "value 2_2"));
print_r($x->addProperty(["level_2", "level_2_1"], "level_2_1_1", "this is level 2_1_1", "value 2_1_1"));
echo "\n";

echo "getDetail, fetchDetail, testTheSame => Expect: 1 ~ Return: ";
$y = new Products($manager, "product test");
$y->fetchDetail();
print_r(testTheSame($x->getDetail(), $y->getDetail())); echo "\n";
print_r($y->getDetail());

echo "editProperty => Expect: 1 ~ Return: ";
print_r($x->editProperty(["level_2", "level_2_1"], "level_2_1_1", "this is level 2_1_1 (edited)", "value 2_1_1 (edited)"));
echo "\n";

echo "getDetail, fetchDetail, testTheSame => Expect: 1 ~ Return: ";
$z = new Products($manager, "product test");
$z->fetchDetail();
print_r(testTheSame($x->getDetail(), $z->getDetail())); echo "\n";
print_r($z->getDetail());

echo "removeProperty => Expect: 1 ~ Return: ";
print_r($x->removeProperty([], "level_2"));
echo "\n";

echo "getDetail, fetchDetail, testTheSame => Expect: 1 ~ Return: ";
$t = new Products($manager, "product test");
$t->fetchDetail();
print_r(testTheSame($x->getDetail(), $t->getDetail())); echo "\n";
print_r($t->getDetail());

?>

