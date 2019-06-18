<?php

require_once "provider/provider.php";

class RefundRepository {
  static function add(Array $data) {
    $conn = Provider::connect();
    $mrefundid = $data["mrefundid"];
    $zptransid = $data["zptransid"];
    $amount = $data["amount"];
    $ok = $conn->query("INSERT INTO `refunds`(`mrefundid`, `zptransid`, `amount`) VALUES('$mrefundid', '$zptransid', $amount)");
    $conn->close();
    return $ok;
  }

  static function getByZptransid(string $zptransid) {
    $refunds = Provider::select("SELECT * FROM `refunds` WHERE `zptransid`=$zptransid");
    return $refunds;
  }
}