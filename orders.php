<?php
class Orders {
  const DB_COLLECTION_NAME = "store.orders";

  private $_manager;     // MongoDB Manager instance
  private $_orderID;     // ID of the order
  private $_information; // Information of the order
  private $_isFetch;     // Return if the information is fetched or not

  function __construct($mongoManager, $orderID) {
    $this->_manager = $mongoManager;
    $this->_orderID = $orderID;
    $this->_information = array();
    $this->_isFetch = false;
  }

  // Clean information
  static function cleanInformation(&$node) {
    if ($node instanceof stdClass)
      $node = (array)$node;
    if (!is_array($node))
      return;
    foreach ($node as &$child)
      self::cleanInformation($child);
  }

  // Fetch the information of the order
  function fetchInformation() {
    // Check if the order exists
    $ret = self::checkOrder($this->_manager, $this->_orderID, true);
    if (is_null($ret) or !$ret) {
      $this->_information = array();
      $this->_isFetch = false;
      return false;
    }

    // Check if the order is outdated
    if ($this->_isFetch) {
      try {
        $query = new MongoDB\Driver\Query(["orderID" => $this->_orderID],
                                          ["projection" => ["updatedTime" => 1]]);
        $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
        $information = $cursor->toArray()[0];
        self::cleanInformation($information);
        if ($this->_information["updatedTime"] == $information["updatedTime"])
          return true;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        $this->_information = array();
        $this->_isFetch = false;
        return false;
      }
    }

    // Fetch the new/updated order
    try {
      $query = new MongoDB\Driver\Query(["orderID" => $this->_orderID]);
      $cursor = $this->_manager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $this->_information = $cursor->toArray()[0];
      self::cleanInformation($this->_information);
      $this->_isFetch = true;
      return true;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      $this->_information = array();
      $this->_isFetch = false;
      return false;
    }
  }

  function getOrderID() {
    return $this->_orderID;
  }
  function getInformation() {
    return $this->_information;
  }
  function getIsFetch() {
    return $this->_isFetch;
  }

  // Set a property
  function setProperty($key, $value) {
    // Check if the information is fetched
    if (!$this->_isFetch)
      return false;

    // Check if the property is set
    if (array_key_exists($key, $this->_information["information"]) and
        $this->_information["information"][$key]["value"] == $value and
        !$this->_information["information"][$key]["isRemoved"])
      return true;

    // Set a property
    try {
      $newValue = ["value" => $value, "isRemoved" => false];
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["orderID" => $this->_orderID],
                    ['$set' => ["information." . $key => $newValue,
                                "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $this->_information["information"][$key] = $newValue;
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }
  // Remove a property
  function removeProperty($key) {
    // Check if the information is fetched
    if (!$this->_isFetch)
      return false;

    // Check if the property is removed
    if (array_key_exists($key, $this->_information["information"]) and
        $this->_information["information"][$key]["isRemoved"])
      return true;

    // Set a property
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["orderID" => $this->_orderID],
                    ['$set' => ["information." . $key . ".isRemoved" => true,
                                "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $this->_information["information"][$key]["isRemoved"] = true;
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }
  // Add a product
  function addProduct($productID, $value) {
    // Check the information is fetched
    if (!$this->_isFetch)
      return false;

    if (array_key_exists($productID, $this->_information["hasMany"])) {
      // Add a value to an existing product
      try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(["orderID" => $this->_orderID],
                      ['$set' => ["updatedTime" => time()],
                       '$push' => ["hasMany." . $productID => $value]],
                      ["multi" => true]);
        $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
        if ($result->getModifiedCount() > 0) {
          array_push($this->_information["hasMany"][$productID], $value);
          return true;
        } else return false;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        return false;
      }
    } else {
      // Add a product with its value
      try {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(["orderID" => $this->_orderID],
                      ['$set' => ["hasMany." . $productID => [$value],
                                  "updatedTime" => time()]],
                      ["multi" => true]);
        $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
        if ($result->getModifiedCount() > 0) {
          $this->_information["hasMany"][$productID] = [$value];
          return true;
        } else return false;
      } catch (Exception $e) {
        echo "<exception>\n"; print_r($e); echo "</exception>\n";
        return false;
      }
    }
  }

  // Create a new order
  static function newOrder($mongoManager, $orderID, $byUserID) {
    // Check if the order exists
    $ret = self::checkOrder($mongoManager, $orderID, false);
    if (is_null($ret) or $ret)
      return null;

    // Create a new order if not existing
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->insert(["orderID" => $orderID,
                     "belongsTo" => $byUserID,
                     "information" => new \stdClass,
                     "hasMany" => new \stdClass,
                     "isRemoved" => false,
                     "updatedTime" => time()]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getInsertedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Remove an order by marking it as deleted
  static function deleteOrder($mongoManager, $orderID) {
    // Check if the order exists
    if (!self::checkOrder($mongoManager, $orderID, true))
      return true;

    // Remove an order
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["orderID" => $orderID, "isRemoved" => false],
                    ['$set' => ["isRemoved" => true, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // List all orders: return (orderID, isRemoved) pairs
  static function listOrder($mongoManager) {
    try {
      $query = new MongoDB\Driver\Query([],
                                        ["projection" => ["orderID" => 1, "isRemoved" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $information = $cursor->toArray();
      self::cleanInformation($information);
      return $information;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Check if orderID exists or not
  static function checkOrder($mongoManager, $orderID, $forceExists) {
    try {
      $query = new MongoDB\Driver\Query(["orderID" => $orderID],
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

