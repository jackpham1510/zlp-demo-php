<?php

require_once "config/config.php";
require_once "utils/utils.php";
require_once "utils/http.php";
require_once "utils/ngrok.php";
require_once "mac.php";

class ZaloPayHelper
{
  private static $PUBLIC_KEY;
  private static $UID;

  static function init()
  {
    # Public key nhận được khi đăng ký ứng dụng với zalopay
    self::$PUBLIC_KEY = file_get_contents('publickey.pem');
    self::$UID = getTimestamp();
  }

  /**
   * Kiểm callback có hợp lệ hay không 
   * 
   * @param Array $params ["data" => string, "mac" => string]
   * @return Array ["returncode" => int, "returnmessage" => string]
   */
  static function verifyCallback(Array $params)
  {
    $data = $params["data"];
    $requestMac = $params["mac"];

    $result = [];
    $mac = ZaloPayMacGenerator::compute($data, Config::get()['key2']);

    if ($mac != $requestMac) {
      $result['returncode'] = -1;
      $result['returnmessage'] = 'mac not equal';
    } else {
      $result['returncode'] = 1;
      $result['returnmessage'] = 'success';
    }

    return $result;
  }

  /**
   * Kiểm callback có hợp lệ hay không 
   * 
   * @param Array $data - là query string mà zalopay truyền vào redirect link ($_GET)
   * @return bool
   *  - true: hợp lệ
   *  - false: không hợp lệ
   */
  static function verifyRedirect(Array $data)
  {
    $reqChecksum = $data["checksum"];
    $checksum = ZaloPayMacGenerator::redirect($data);

    return $reqChecksum === $checksum;
  }

  /**
   * Generate apptransid hoặc mrefundid
   * 
   * @return string
   *  - apptransid có dạng yyMMddxxxxx
   *  - mrefundid có dạng yyMMdd_appid_xxxxx
   */
  static function genTransID()
  {
    return date("ymd")."_".Config::get()['appid']."_".(++self::$UID);
  }

  /**
   * Tạo Array chứa các tham số cần thiết để truyền vào API "tạo đơn hàng"
   * 
   * @param Array $params [
   *  "amount" => long,
   *  "description" => string (optional),
   *  "bankcode" => string (optional - default "zalopayapp"),
   *  "appuser" => string (optional - default "demo"),
   *  "item" => string (optional - default "")
   * ]
   * @return Array
   */
  static function newCreateOrderData(Array $params)
  {
    $embeddata = "";
    
    if (array_key_exists("embeddata", $params)) {
      $embeddata = $params["embeddata"];
    } else {
      if (isset(Ngrok::$PUBLIC_URL)) {
        $embeddata = JSON::encode([
          "forward_callback" => Ngrok::$PUBLIC_URL . "/callback.php"
        ]);
      }
    }

    $order = [
      "appid" => Config::get()["appid"],
      "apptime" => getTimeStamp(),
      "apptransid" => self::GenTransID(),
      "appuser" => array_key_exists("appuser", $params) ? $params["appuser"] : "demo",
      "item" => array_key_exists("item", $params) ? $params["item"] : "",
      "embeddata" => $embeddata,      
      "bankcode" =>  array_key_exists("bankcode", $params) ? $params["bankcode"] : "zalopayapp",
      "description" => array_key_exists("description", $params) ? $params['description'] : "",
      "amount" => $params['amount'],
    ];

    return $order;
  }

  /**
   * Nhận vào thông tin đơn hàng và tạo đơn hàng thông qua API "tạo đơn hàng"
   * 
   * @param Array $order - Thông tin đơn hàng
   * @return Array - Kết quả tạo đơn hàng
   */
  static function createOrder(Array $order) {
    $order['mac'] = ZaloPayMacGenerator::createOrder($order);
    $result = Http::postForm(Config::get()['api']['createorder'], $order);
    return $result;
  }

  /**
   * Tạo một Array chứa các thâm số cần thiết để truyền vào API "Quickpay"
   * 
   * @param Array $params - như hàm NewOrder, nhưng có thêm tham số paymentcodeRaw
   * @return Array
   */
  static function newQuickPayOrderData(Array $params) {
    $order = self::newCreateOrderData($params);
    $order['userip'] = array_key_exists('userip', $params) ? $params['userip'] : "127.0.0.1";
    openssl_public_encrypt($params['paymentcodeRaw'], $encrypted, self::$PUBLIC_KEY);
    $order['paymentcode'] = base64_encode($encrypted);
    $order['mac'] = ZaloPayMacGenerator::quickPay($order, $params['paymentcodeRaw']);
    return $order;
  }

  /**
   * Nhận vào thông tin đơn hàng và tiến hành thanh toán thông qua API "Quickpay"
   * 
   * @param Array $order - Thông tin đơn hàng
   * @return Array - Kết quả giao dịch
   */
  static function quickPay(Array $order) {
    $result = Http::postForm(Config::get()['api']['quickpay'], $order);
    return $result;
  }

  /**
   * Nhận vào apptransid của đơn hàng và tiến hành truy vấn thông tin đơn hàng thông qua API "Truy vấn đơn hàng"
   * 
   * @param String $apptransid - apptransid của đơn hàng
   * @return Array - Trạng thái đơn hàng
   */
  static function getOrderStatus(string $apptransid) {
    $params = [
      "appid" => Config::get()['appid'],
      "apptransid" => $apptransid
    ];
    $params["mac"] = ZaloPayMacGenerator::getOrderStatus($params);
    return Http::postForm(Config::get()['api']['getorderstatus'], $params);
  }

  /**
   * Tạo một Array chứa các thâm số cần thiết để truyền vào API "Refund"
   * 
   * @param Array $params [
   *  "zptransid" => string,
   *  "amount" => long,
   *  "description" => string
   * ]
   * @return Array
   */
  static function newRefundData(Array $params) {
    $refundData = [
      "appid" => Config::get()['appid'],
      "timestamp" => getTimestamp(),
      "mrefundid" => self::genTransID(),
      "zptransid" => $params['zptransid'],
      "amount" => $params['amount'],
      "description" => $params['description']
    ];

    $refundData['mac'] = ZaloPayMacGenerator::refund($refundData);
    return $refundData;
  }

  /**
   * Nhận vào thông tin hoàn tiền và tiến hành hoàn tiền thông qua API "Hoàn tiền"
   * 
   * @param Array $refundData - Thông tin hoàn tiền
   * @return Array - Kết quả hoàn tiền
   */
  static function refund(Array $refundData) {
    $result = Http::postForm(Config::get()['api']['refund'], $refundData);
    $result['mrefundid'] = $refundData['mrefundid'];

    return $result;
  }

  /**
   * Nhận vào mrefundid của yêu cầu hoàn tiền và tiến hành truy vấn thông tin hoàn tiền thông qua API "GetRefundStatus"
   * 
   * @param String - mrefundid của yêu cầu hoàn tiền
   * @return Array - Trạng thái hoàn tiền
   */
  static function GetRefundStatus(String $mrefundid)
  {
    $params = [
      "appid" => Config::get()['appid'],
      "mrefundid" => $mrefundid,
      "timestamp" => getTimestamp()
    ];

    $params['mac'] = ZaloPayMacGenerator::getRefundStatus($params);
    return Http::postForm(Config::get()['api']['getrefundstatus'], $params);
  }

  /**
   * Lấy danh sách ngân hàng được hỗ trợ thông qua API "Getbanklist"
   * 
   * @return Array - Danh sách ngân hàng, xem thêm ở link dưới
   */
  static function getBankList()
  {
    $params = [
      "appid" => Config::get()['appid'],
      "reqtime" => getTimestamp()
    ];

    $params['mac'] = ZaloPayMacGenerator::getBankList($params);
    return Http::postForm(Config::get()['api']['getbanklist'], $params);
  }
}

ZaloPayHelper::init();