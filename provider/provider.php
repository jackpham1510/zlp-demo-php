<?php

require_once "config/config.php";

class Provider {
  private static $db;

  static function Connect() {
    $conn = new mysqli(
      Config::get()['db']['host'] . ':' . Config::get()['db']['port'],
      Config::get()['db']['user'],
      Config::get()['db']['password'],
      Config::get()['db']['dbname']
    );

    $conn->set_charset('utf8mb4');

    if ($conn->connect_error) {
      die("[DB] connect fail: ". $conn->connect_error);
    }
    
    return $conn;
  }


  static function Select($sql) {
    $conn = self::Connect();
    $result = [];
    $resultRaw = $conn->query($sql);

    if ($resultRaw->num_rows > 0) {
      while($row = $resultRaw->fetch_assoc()) {
        array_push($result, $row);
      }
    }

    $conn->close();

    return $result;
  }
}