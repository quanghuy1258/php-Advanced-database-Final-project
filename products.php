<?php
class Products {
  const DB_COLLECTION_NAME = "store.products";

  private $_manager;   // MongoDb Manager instance
  private $_productID; // ID of the product
  private $_detail;    // The detail of the product
  private $_isFetch;   // Return if the detail is fetched or not

  function __construct($mongoManager, $productID) {
    $this->_manager = $mongoManager;
    $this->_productID = $productID;
    $this->_detail = array();
    $this->_isFetch = false;
  }

  // Clean detail
  static function cleanDetail(&$node) {
    if ($node instanceof stdClass)
      $node = (array)$node;
    if (!is_array($node))
      return;
    foreach ($node as &$child)
      self::cleanDetail($child);
  }

  // Fetch the detail of the product
  function fetchDetail() {
    // Check if the product exists
    $ret = self::checkProduct($this->_manager, $this->_productID, true);
    if (is_null($ret) or !$ret) {
      $this->_detail = array();
      $this->_isFetch = false;
      return false;
    }

    // Check if the product is outdated
    if ($this->_isFetch) {
      try {
        $query = new MongoDB\Driver\Query(["productID" => $this->_productID],
                                          ["projection" => ["updatedTime" => 1]]);
        $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
        $detail = $cursor->toArray()[0];
        self::cleanDetail($detail);
        if ($this->_detail["updatedTime"] == $detail["updatedTime"])
          return true;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        $this->_detail = array();
        $this->_isFetch = false;
        return false;
      }
    }

    // Fetch the new/updated product
    try {
      $query = new MongoDB\Driver\Query(["productID" => $this->_productID]);
      $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $this->_detail = $cursor->toArray()[0];
      self::cleanDetail($this->_detail);
      $this->_isFetch = true;
      return true;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      $this->_detail = array();
      $this->_isFetch = false;
      return false;
    }
  }

  function getProductID() {
    return $this->_productID;
  }
  function getDetail() {
    return $this->_detail;
  }
  function getIsFetch() {
    return $this->_isFetch;
  }

  // Add a new property
  function addProperty($arrayPropertiesAsPath, $name, $hint, $value) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_detail["detail"];
    $path = "detail";
    foreach ($arrayPropertiesAsPath as $property) {
      if (!array_key_exists($property, $current))
        return false;
      if ($current[$property]["isRemoved"])
        return false;
      $path .= "." . $property . ".subproperties";
      $current = &$current[$property]["subproperties"];
    }

    $newLog = ["value" => $value, "time" => time()];
    if (array_key_exists($name, $current)) {
      if (!$current[$name]["isRemoved"])
        return false;

      // Recreate a deleted property
      try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(["productID" => $this->_productID],
                      ['$set' => [$path . "." . $name . ".hint" => $hint,
                                  $path . "." . $name . ".value" => $value,
                                  $path . "." . $name . ".isRemoved" => false,
                                  "updatedTime" => time()],
                       '$push' => [$path . "." . $name . ".log" => $newLog]],
                      ["multi" => true]);
        $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
        if ($result->getModifiedCount() > 0) {
          $current[$name]["hint"] = $hint;
          $current[$name]["value"] = $value;
          $current[$name]["isRemoved"] = false;
          array_push($current[$name]["log"], $newLog);
          return true;
        } else return false;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        return false;
      }
    } else {
      // Add a new property
      try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(["productID" => $this->_productID],
                      ['$set' => [$path . "." . $name . ".subproperties" => new \stdClass,
                                  $path . "." . $name . ".hint" => $hint,
                                  $path . "." . $name . ".value" => $value,
                                  $path . "." . $name . ".isRemoved" => false,
                                  $path . "." . $name . ".log" => [$newLog],
                                  "updatedTime" => time()]],
                      ["multi" => true]);
        $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
        if ($result->getModifiedCount() > 0) {
          $current[$name]["subproperties"] = array();
          $current[$name]["hint"] = $hint;
          $current[$name]["value"] = $value;
          $current[$name]["isRemoved"] = false;
          $current[$name]["log"] = [$newLog];
          return true;
        } else return false;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        return false;
      }
    }
  }
  // Edit an existing property by adding its new version
  function editProperty($arrayPropertiesAsPath, $name, $hint, $value) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_detail["detail"];
    $path = "detail";
    foreach ($arrayPropertiesAsPath as $property) {
      if (!array_key_exists($property, $current))
        return false;
      if ($current[$property]["isRemoved"])
        return false;
      $path .= "." . $property . ".subproperties";
      $current = &$current[$property]["subproperties"];
    }

    // Check if the property exists
    if (!array_key_exists($name, $current) or $current[$name]["isRemoved"])
      return false;

    // Edit the property
    try {
      $newLog = ["value" => $value, "time" => time()];
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["productID" => $this->_productID],
                    ['$set' => [$path . "." . $name . ".hint" => $hint,
                                $path . "." . $name . ".value" => $value,
                                "updatedTime" => time()],
                     '$push' => [$path . "." . $name . ".log" => $newLog]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $current[$name]["hint"] = $hint;
        $current[$name]["value"] = $value;
        array_push($current[$name]["log"], $newLog);
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }
  // Remove a property by marking it as deleted
  static function removeHelper(&$current, $currentPath, &$setArray, &$pushArray) {
    $newLog = ["state" => "removed", "time" => time()];
    $current["isRemoved"] = true;
    array_push($current["log"], $newLog);
    $setArray[$currentPath . ".isRemoved"] = true;
    $pushArray[$currentPath . ".log"] = $newLog;
    foreach (array_keys($current["subproperties"]) as $key)
      self::removeHelper($current["subproperties"][$key],
                         $currentPath . ".subproperties." . $key,
                         $setArray, $pushArray);
  }
  function removeProperty($arrayPropertiesAsPath, $name) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_detail["detail"];
    $path = "detail";
    foreach ($arrayPropertiesAsPath as $property) {
      if (array_key_exists($property, $current)) {
        $path .= "." . $property . ".subproperties";
        $current = &$current[$property]["subproperties"];
      } else return false;
    }

    // Check if the property exists
    if (!array_key_exists($name, $current) or $current[$name]["isRemoved"])
      return true;

    // Remove the property
    $cloneCurrent = $current[$name];
    $cloneCurrentPath = $path . "." . $name;
    $setArray = [];
    $pushArray = [];
    self::removeHelper($cloneCurrent, $cloneCurrentPath, $setArray, $pushArray);
    $setArray["updatedTime"] = time();
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["productID" => $this->_productID],
                    ['$set' => $setArray,
                     '$push' => $pushArray],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $current[$name] = $cloneCurrent;
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Edit hint
  function editHint($hint) {
    // Try editing the hint of the product
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["productID" => $this->_productID, "isRemoved" => false],
                    ['$set' => ["hint" => $hint, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($this->_isFetch and $result->getModifiedCount() > 0)
        $this->_detail["hint"] = $hint;
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Create a new product
  static function newProduct($mongoManager, $productID, $hint) {
    // Check if the product exists
    $ret = self::checkProduct($mongoManager, $productID, false);
    if (is_null($ret) or $ret)
      return null;

    // Create a new product if not existing
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->insert(["productID" => $productID,
                     "hint" => $hint,
                     "detail" => new \stdClass,
                     "isRemoved" => false,
                     "updatedTime" => time()]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getInsertedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Remove a product by marking it as deleted
  static function deleteProduct($mongoManager, $productID) {
    // Check if the product exists
    if (!self::checkProduct($mongoManager, $productID, true))
      return true;

    // Remove a product
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["productID" => $productID, "isRemoved" => false],
                    ['$set' => ["isRemoved" => true, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // List all products: return (productID, hint, isRemoved) tuples
  static function listProduct($mongoManager) {
    try {
      $query = new MongoDB\Driver\Query([],
                                        ["projection" => ["productID" => 1,
                                                          "hint" => 1,
                                                          "isRemoved" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $detail = $cursor->toArray();
      self::cleanDetail($detail);
      return $detail;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Check if productID exists or not
  static function checkProduct($mongoManager, $productID, $forceExists) {
    try {
      $query = new MongoDB\Driver\Query(["productID" => $productID],
                                        ["projection" => ["isRemoved" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      if ($forceExists) {
        $result = $cursor->toArray();
        if (sizeof($result) == 0)
          return false;
        $result = (array)$result[0];
        return !$result["isRemoved"];
      }
      return sizeof($cursor->toArray()) > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
}
?>

