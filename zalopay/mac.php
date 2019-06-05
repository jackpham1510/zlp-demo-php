<?php

require_once "config/config.php";

class ZaloPayMacGenerator {
  static function Compute(string $data) {
    return hash_hmac("sha256", $data, Config::get()['key1']);
  }

  static function _createOrderMacData(Array $order) {
    return $order["appid"]."|".$order["apptransid"]."|".$order["appuser"]."|".$order["amount"]
      ."|".$order["apptime"]."|".$order["embeddata"]."|".$order["item"];
  }

  static function CreateOrder(Array $order) {
    return self::Compute(self::_createOrderMacData($order));
  }

  static function QuickPay(Array $order, String $paymentcodeRaw) {
    return self::Compute(self::_createOrderMacData($order)."|".$paymentcodeRaw);
  }

  static function Refund(Array $params) {
    return self::Compute($params['appid'].'|'.$params['zptransid'].'|'.$params['amount'].'|'.$params['description'].'|'.$params['timestamp']);
  }

  static function GetOrderStatus(Array $params) {
    return self::Compute($params['appid'].'|'.$params['apptransid'].'|'.Config::get()['key1']);
  }

  static function GetRefundStatus(Array $params) {
    return self::Compute($params['appid'].'|'.$params['mrefundid'].'|'.$params['timestamp']);
  }

  static function GetBankList(Array $params) {
    return self::Compute($params['appid'].'|'.$params['reqtime']);
  }
}