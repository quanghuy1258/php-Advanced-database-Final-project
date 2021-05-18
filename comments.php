<?php
class Comments {
  const DB_COLLECTION_NAME = "store.comments";

  private $_manager; // MongoDB Manager instance
  private $_postID;  // ID of the post
  private $_content; // Content of the post: All comments and replies
  private $_isFetch; // Return if the content is fetched or not

  function __construct($mongoManager, $postID) {
    $this->_manager = $mongoManager;
    $this->_postID = $postID;
    $this->_content = array();
    $this->_isFetch = false;
  }

  // Clean content
  static function cleanContent(&$node) {
    if ($node instanceof stdClass)
      $node = (array)$node;
    if (!is_array($node))
      return;
    foreach ($node as &$child)
      self::cleanContent($child);
  }

  // Fetch the content of the post
  function fetchContent() {
    // Check if the post exists
    $ret = self::checkPostComment($this->_manager, $this->_postID);
    if (is_null($ret) or !$ret) {
      $this->_content = array();
      $this->_isFetch = false;
      return false;
    }

    // Check if the post is outdated
    if ($this->_isFetch) {
      try {
        $query = new MongoDB\Driver\Query(["postID" => $this->_postID, "isRemoved" => false],
                                          ["projection" => ["updatedTime" => 1]]);
        $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
        $content = $cursor->toArray()[0];
        self::cleanContent($content);
        if ($this->_content["updatedTime"] == $content["updatedTime"])
          return true;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        $this->_content = array();
        $this->_isFetch = false;
        return false;
      }
    }

    // Fetch the new/updated post
    try {
      $query = new MongoDB\Driver\Query(["postID" => $this->_postID, "isRemoved" => false]);
      $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $this->_content = $cursor->toArray()[0];
      self::cleanContent($this->_content);
      $this->_isFetch = true;
      return true;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      $this->_content = array();
      $this->_isFetch = false;
      return false;
    }
  }

  function getPostID() {
    return $this->_postID;
  }
  function getContent() {
    return $this->_content;
  }
  function getIsFetch() {
    return $this->_isFetch;
  }

  // Create a new comment
  function addComment($arrayIndicesAsPath, $comment, $author) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_content["content"];
    $path = "content";
    foreach ($arrayIndicesAsPath as $index) {
      if (!array_key_exists($index, $current))
        return false;
      if ($current[$index]["isRemoved"])
        return false;
      $path .= "." . $index . ".replies";
      $current = &$current[$index]["replies"];
    }

    // Add a new comment
    try {
      $newComment = ["comment" => $comment,
                     "author" => $author,
                     "replies" => array(),
                     "isRemoved" => false,
                     "log" => [["comment" => $comment, "author" => $author, "time" => time()]]];
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["postID" => $this->_postID, "isRemoved" => false],
                    ['$set' => ["updatedTime" => time()],
                     '$push' => [$path => $newComment]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        array_push($current, $newComment);
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }
  // Edit an existing comment by adding its new version
  function editComment($arrayIndicesAsPath, $finalIndex, $comment, $author) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_content["content"];
    $path = "content";
    foreach ($arrayIndicesAsPath as $index) {
      if (!array_key_exists($index, $current))
        return false;
      if ($current[$index]["isRemoved"])
        return false;
      $path .= "." . $index . ".replies";
      $current = &$current[$index]["replies"];
    }

    // Check if the comment exists
    if (!array_key_exists($finalIndex, $current) or $current[$finalIndex]["isRemoved"])
      return false;

    // Edit a comment
    try {
      $newLog = ["comment" => $comment, "author" => $author, "time" => time()];
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["postID" => $this->_postID, "isRemoved" => false],
                    ['$set' => [$path . "." . $finalIndex . ".comment" => $comment,
                                $path . "." . $finalIndex . ".author" => $author,
                                "updatedTime" => time()],
                     '$push' => [$path . "." . $finalIndex . ".log" => $newLog]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $current[$finalIndex]["comment"] = $comment;
        $current[$finalIndex]["author"] = $author;
        array_push($current[$finalIndex]["log"], $newLog);
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }
  // Remove a comment by marking it as deleted
  function removeComment($arrayIndicesAsPath, $finalIndex, $by) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_content["content"];
    $path = "content";
    foreach ($arrayIndicesAsPath as $index) {
      if (!array_key_exists($index, $current))
        return false;
      if ($current[$index]["isRemoved"])
        return false;
      $path .= "." . $index . ".replies";
      $current = &$current[$index]["replies"];
    }

    // Check if the comment exists
    if (!array_key_exists($finalIndex, $current) or $current[$finalIndex]["isRemoved"])
      return true;

    // Remove a comment
    try {
      $newLog = ["state" => "removed", "by" => $by, "time" => time()];
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["postID" => $this->_postID, "isRemoved" => false],
                    ['$set' => [$path . "." . $finalIndex . ".isRemoved" => true,
                                "updatedTime" => time()],
                     '$push' => [$path . "." . $finalIndex . ".log" => $newLog]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $current[$finalIndex]["isRemoved"] = true;
        array_push($current[$finalIndex]["log"], $newLog);
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Edit hint
  function editHint($hint) {
    // Try editing the hint of the post
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["postID" => $this->_postID, "isRemoved" => false],
                    ['$set' => ["hint" => $hint, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($this->_isFetch and $result->getModifiedCount() > 0)
        $this->_content["hint"] = $hint;
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Create a new comment log for a post
  static function newPostComment($mongoManager, $postID, $hint) {
    // Check if the post exists
    $ret = self::checkPostComment($mongoManager, $postID);
    if (is_null($ret) or $ret)
      return null;

    // Create a new post if not existing
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->insert(["postID" => $postID,
                     "hint" => $hint,
                     "content" => array(),
                     "isRemoved" => false,
                     "updatedTime" => time()]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getInsertedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Remove a comment log by marking it as deleted
  static function deletePostComment($mongoManager, $postID) {
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["postID" => $postID, "isRemoved" => false],
                    ['$set' => ["isRemoved" => true, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // List all comment logs/ post IDs: return (postID, hint) pairs
  static function listPostComment($mongoManager) {
    try {
      $query = new MongoDB\Driver\Query(["isRemoved" => false],
                                        ["projection" => ["postID" => 1, "hint" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $content = $cursor->toArray();
      self::cleanContent($content);
      return $content;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Check if postID exists or not
  static function checkPostComment($mongoManager, $postID) {
    try {
      $query = new MongoDB\Driver\Query(["postID" => $postID, "isRemoved" => false],
                                        ["projection" => ["_id" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      return sizeof($cursor->toArray()) > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
}
?>

