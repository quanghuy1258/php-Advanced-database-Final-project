<?php
class Users {
  const DB_COLLECTION_NAME = "store.users";

  private $_manager;     // MongoDB Manager instance
  private $_userID;      // ID of the user
  private $_information; // Information of the user
  private $_isFetch;     // Return if the information is fetched or not

  function __construct($mongoManager, $userID) {
    $this->_manager = $mongoManager;
    $this->_userID = $userID;
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

  // Fetch the information of the user
  function fetchInformation() {
    // Check if the user exists
    $ret = self::checkUser($this->_manager, $this->_userID, true);
    if (is_null($ret) or !$ret) {
      $this->_information = array();
      $this->_isFetch = false;
      return false;
    }

    // Check if the user is outdated
    if ($this->_isFetch) {
      try {
        $query = new MongoDB\Driver\Query(["userID" => $this->_userID],
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

    // Fetch the new/updated user
    try {
      $query = new MongoDB\Driver\Query(["userID" => $this->_userID]);
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

  function getUserID() {
    return $this->_userID;
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
      $bulk->update(["userID" => $this->_userID],
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
      $bulk->update(["userID" => $this->_userID],
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
  // Add a order
  function addOrder($orderID) {
    // Check the information is fetched
    if (!$this->_isFetch)
      return false;

    // Check if the order exists
    if (array_key_exists($orderID, $this->_information["hasMany"]))
      return true;

    // Add a order
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["userID" => $this->_userID],
                    ['$set' => ["hasMany." . $orderID => true,
                                "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $this->_manager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      if ($result->getModifiedCount() > 0) {
        $this->_information["hasMany"][$orderID] = true;
        return true;
      } else return false;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return false;
    }
  }

  // Create a new user
  static function newUser($mongoManager, $userID) {
    // Check if the user exists
    $ret = self::checkUser($mongoManager, $userID, false);
    if (is_null($ret) or $ret)
      return null;

    // Create a new user if not existing
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->insert(["userID" => $userID,
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
  // Remove a user by marking it as deleted
  static function deleteUser($mongoManager, $userID) {
    // Check if the user exists
    if (!self::checkUser($mongoManager, $userID, true))
      return true;

    // Remove a user
    try {
      $bulk = new MongoDB\Driver\BulkWrite;
      $bulk->update(["userID" => $userID, "isRemoved" => false],
                    ['$set' => ["isRemoved" => true, "updatedTime" => time()]],
                    ["multi" => true]);
      $result = $mongoManager->executeBulkWrite(self::DB_COLLECTION_NAME, $bulk);
      return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // List all users: return (userID, isRemoved) pairs
  static function listUser($mongoManager) {
    try {
      $query = new MongoDB\Driver\Query([],
                                        ["projection" => ["userID" => 1, "isRemoved" => 1]]);
      $cursor = $mongoManager->executeQuery(self::DB_COLLECTION_NAME, $query);
      $information = $cursor->toArray();
      self::cleanInformation($information);
      return $information;
    } catch (Exception $e) {
      echo "<exception>\n"; print_r($e); echo "</exception>\n";
      return null;
    }
  }
  // Check if userID exists or not
  static function checkUser($mongoManager, $userID, $forceExists) {
    try {
      $query = new MongoDB\Driver\Query(["userID" => $userID],
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

