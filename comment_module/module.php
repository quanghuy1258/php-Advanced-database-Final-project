<!-- if user is not signed in, tell them to sign in. If signed in, present them with comment form -->
<?php if (isset($_SESSION['id'])): ?>
<form name="fcomment" action="comment_module/process.php?id=<?php echo $_GET['idDT']; ?>" method="post"><ul>
	<li><textarea id="comment_text" name="comment_text" cols="50" rows="5" class="required"></textarea></li>
	<li><a href="#" class="simplebtn" onClick="fcomment.submit();"><span>Bình luận</span></a></li>
</ul></form>
<?php else: ?>
<h4><a href="login.php">Đăng nhập</a> để bình luận</h4>
<?php endif ?>

<?php
include_once "mongodb/mongodb.php";
include_once "mongodb/comments.php";

$mongoManager = createMongoManagerInstance();
Comments::newPostComment($mongoManager, $_GET["idDT"], "");
$post = new Comments($mongoManager, $_GET["idDT"]);
$post->fetchContent();
$content = $post->getContent()["content"];

$totalComment = sizeof($content);
?>

<!-- Display total number of comments on this post  -->
<h2><span id="comments_count"><?php echo $totalComment; ?></span> Bình luận</h2>
<hr>
<?php if ($totalComment > 0): ?>
<?php foreach ($content as $comment): ?>
<ul style="list-style: none; margin-left: 20px;">
<li class="bold" ><?php echo $comment["author"]; ?> (<?php echo date("d-m-Y H:i:s", end($comment["log"])["time"]); ?>)</li>
<li><?php echo $comment["comment"]; ?></li>
</ul>
<?php endforeach ?>
<?php else: ?>
	<h2>Hãy là người đầu tiên bình luận sản phẩm này.</h2>
<?php endif ?>
