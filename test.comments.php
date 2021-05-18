<?php

include "mongodb.php";
include "comments.php";

function testTheSame($x, $y) {
  if ($x["_id"] != $y["_id"])
    return false;
  if ($x["postID"] != $y["postID"])
    return false;
  if ($x["hint"] != $y["hint"])
    return false;
  if ($x["content"] != $y["content"])
    return false;
  if ($x["isRemoved"] != $y["isRemoved"])
    return false;
  if ($x["updatedTime"] != $y["updatedTime"])
    return false;
  return true;
}

$manager = createMongoManagerInstance();

$ret = Comments::deletePostComment($manager, "post test");
echo "Comments::deletePostComment => Expect: 1 or nothing ~ Return: ";
print_r($ret); echo "\n";

$ret = Comments::newPostComment($manager, "post test", "post hint");
echo "Comments::newPostComment => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Comments::checkPostComment($manager, "post test");
echo "Comments::checkPostComment => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Comments::deletePostComment($manager, "post test");
echo "Comments::deletePostComment => Expect: 1 ~ Return:";
print_r($ret); echo "\n";

$ret = Comments::newPostComment($manager, "post test", "post hint");
echo "Comments::newPostComment => Expect: 1 ~ Return: ";
print_r($ret); echo "\n";

$ret = Comments::listPostComment($manager);
echo "Comments::listPostComment => Expect: All posts ~ Return:\n";
print_r($ret);

$x = new Comments($manager, "post test");
echo "fetchContent => Expect: 1 ~ Return: ";
print_r($x->fetchContent()); echo "\n";
echo "getPostID, getContent, getIsFetch => Expect: All information ~ Return:\n";
print_r($x->getPostID()); echo "\n";
print_r($x->getContent());
print_r($x->getIsFetch()); echo "\n";

echo "editHint => Expect: 1 ~ Return: ";
print_r($x->editHint("post hint (edited)")); echo "\n";
print_r($x->getContent()["hint"]); echo "\n";

echo "addComment => Expect: 111111 ~ Return: ";
print_r($x->addComment([], "Good", "Customer 1"));
print_r($x->addComment([], "Need improvement", "Customer 2"));
print_r($x->addComment([1], "Can you explain more?", "Customer 3"));
print_r($x->addComment([1, 0], "I waited for a long time to get the product", "Customer 2"));
print_r($x->addComment([1, 0, 0], "Thank you", "Customer 3"));
print_r($x->addComment([1], "Thank you for your feedback", "Admin"));
echo "\n";

echo "getContent, fetchContent, testTheSame => Expect: 1 ~ Return: ";
$y = new Comments($manager, "post test");
$y->fetchContent();
print_r(testTheSame($x->getContent(), $y->getContent())); echo "\n";
print_r($y->getContent());

echo "editComment => Expect: 1 ~ Return: ";
print_r($x->editComment([], 1, "Need improvement.\nUpdate: I just received the product yesterday with a small cute gift. Nice service.", "Customer 2"));
echo "\n";

echo "getContent, fetchContent, testTheSame => Expect: 1 ~ Return: ";
$z = new Comments($manager, "post test");
$z->fetchContent();
print_r(testTheSame($x->getContent(), $z->getContent())); echo "\n";
print_r($z->getContent());

echo "removeComment => Expect: 1 ~ Return: ";
print_r($x->removeComment([1], 0, "Customer 3"));
echo "\n";

echo "getContent, fetchContent, testTheSame => Expect: 1 ~ Return: ";
$t = new Comments($manager, "post test");
$t->fetchContent();
print_r(testTheSame($x->getContent(), $t->getContent())); echo "\n";
print_r($t->getContent());

?>

