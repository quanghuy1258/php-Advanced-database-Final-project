<?php
class Properties {
  const DB_COLLECTION_NAME = "store.properties";

  private $_manager;    // MongoDB manager instance
  private $_propertyID; // ID of the property tree
  private $_tree;       // The property tree
  private $_isFetch;    // Return if the tree is fetched or not

  function __construct($mongoManager, $propertyID) {
    $this->_manager = $mongoManager;
    $this->_propertyID = $propertyID;
    $this->_tree = array();
    $this->_isFetch = false;
  }

  // Clean tree
  static function cleanTree(&$node) {
    if ($node instanceof stdClass)
      $node = (array)$node;
    if (!is_array($node))
      return;
    foreach ($node as &$child)
      self::cleanTree($child);
  }

  // Fetch the property tree
  function fetchTree() {
    // Check if the property tree exists
    $ret = self::checkPropertyTree($this->_manager, $this->_propertyID);
    if (is_null($ret) or !$ret) {
      $this->_tree = array();
      $this->_isFetch = false;
      return false;
    }

    // Check if the property tree is outdated
    if ($this->_isFetch) {
      try {
        $query = new MongoDB\Driver\Query(["propertyID" => $this->_propertyID, "isRemoved" => false],
                                          ["projection" => ["updatedTime" => 1]]);
        $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
        $tree = $cursor->toArray()[0];
        self::cleanTree($tree);
        if ($this->_tree["updatedTime"] == $tree["updatedTime"])
          return true;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        $this->_tree = array();
        $this->_isFetch = false;
        return false;
      }
    }

    // Fetch the new/updated property tree
    try {
      $query = new MongoDB\Driver\Query(["propertyID" => $this->_propertyID, "isRemoved" => false]);
      $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $this->_tree = $cursor->toArray()[0];
      self::cleanTree($this->_tree);
      $this->_isFetch = true;
      return true;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      $this->_tree = array();
      $this->_isFetch = false;
      return false;
    }
  }

  function getPropertyID() {
    return $this->_propertyID;
  }
  function getTree() {
    return $this->_tree;
  }
  function getIsFetch() {
    return $this->_isFetch;
  }

  // Add a new property
  function addProperty($arrayPropertiesAsPath, $name, $hint) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_tree["tree"];
    $path = "tree";
    foreach ($arrayPropertiesAsPath as $property) {
      if (array_key_exists($property, $current)) {
        $path .= "." . $property . ".subproperties";
        $current = &$current[$property]["subproperties"];
      } else return false;
    }

    // Check if the property exists
    if (array_key_exists($name, $current))
      return false;

    // Add a new property
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["propertyID" => $this->_propertyID, "isRemoved" => false],
                    ['$set' => [$path . "." . $name . ".subproperties" => new \stdClass,
                                $path . "." . $name . ".hint" => $hint,
                                "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $current[$name]["subproperties"] = array();
        $current[$name]["hint"] = $hint;
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }
  // Edit the hint of the property
  function editProperty($arrayPropertiesAsPath, $name, $hint) {
    // Try creating the path
    if (!$this->_isFetch)
      return false;
    $current = &$this->_tree["tree"];
    $path = "tree";
    foreach ($arrayPropertiesAsPath as $property) {
      if (array_key_exists($property, $current)) {
        $path .= "." . $property . ".subproperties";
        $current = &$current[$property]["subproperties"];
      } else return false;
    }

    // Check if the property exists
    if (!array_key_exists($name, $current))
      return false;

    // Edit the hint of the property
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["propertyID" => $this->_propertyID, "isRemoved" => false],
                    ['$set' => [$path . "." . $name . ".hint" => $hint,
                                "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $current[$name]["hint"] = $hint;
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Edit hint
  function editHint($hint) {
    // Try editing the hint of the property tree
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["propertyID" => $this->_propertyID, "isRemoved" => false],
                    ['$set' => ["hint" => $hint, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($this->_isFetch and $result->getModifiedCount() > 0)
        $this->_tree["hint"] = $hint;
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Create a new property tree
  static function newPropertyTree($mongoManager, $propertyID, $hint) {
    // Check if the property tree exists
    $ret = self::checkPropertyTree($mongoManager, $propertyID);
    if (is_null($ret) or $ret)
      return null;

    // Create a new property tree if not existing
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->insert(["propertyID" => $propertyID,
                     "hint" => $hint,
                     "tree" => new \stdClass,
                     "isRemoved" => false,
                     "updatedTime" => time()]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getInsertedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Remove a property tree by marking it as deleted
  static function deletePropertyTree($mongoManager, $propertyID) {
    // Check if the property tree exists
    if (!self::checkPropertyTree($mongoManager, $propertyID))
      return true;

    // Remove a property tree
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["propertyID" => $propertyID, "isRemoved" => false],
                    ['$set' => ["isRemoved" => true, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // List all property trees: return (propertyID, hint) pairs
  static function listPropertyTree($mongoManager) {
    try {
      $query = new MongoDB\Driver\Query(["isRemoved" => false],
                                        ["projection" => ["propertyID" => 1, "hint" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $tree = $cursor->toArray();
      self::cleanTree($tree);
      return $tree;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Check if propertyID exists or not
  static function checkPropertyTree($mongoManager, $propertyID) {
    try {
      $query = new MongoDB\Driver\Query(["propertyID" => $propertyID, "isRemoved" => false],
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

