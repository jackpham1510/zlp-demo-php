<?php

require_once "provider/provider.php";

class RefundRespository {
  static function New(Array $data) {
    $conn = Provider::Connect();
    $mrefundid = $data["mrefundid"];
    $zptransid = $data["zptransid"];
    $amount = $data["amount"];
    $ok = $conn->query("INSERT INTO `refunds`(`mrefundid`, `zptransid`, `amount`) VALUES('$mrefundid', '$zptransid', $amount)");
    $conn->close();
    return $ok;
  }

  static function GetByZptransid(string $zptransid) {
    $refunds = Provider::Select("SELECT * FROM `refunds` WHERE `zptransid`=$zptransid");
    return $refunds;
  }
}