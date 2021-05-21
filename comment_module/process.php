<?php
session_start();

include_once "../mongodb/mongodb.php";
include_once "../mongodb/comments.php";

$mongoManager = createMongoManagerInstance();

if(isset($_GET["id"], $_POST["comment_text"], $_SESSION["hoten"])) {
  $post = new Comments($mongoManager, $_GET['id']);
  $post->fetchContent();
  if (isset($_GET["path"])) {
    if (is_array($_GET["path"]))
       $post->addComment($_GET["path"], $_POST["comment_text"], $_SESSION["hoten"]);
  }
  else $post->addComment([], $_POST["comment_text"], $_SESSION["hoten"]);
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
?>
