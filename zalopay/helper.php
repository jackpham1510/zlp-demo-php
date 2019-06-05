<?php

require_once "config/config.php";
require_once "utils/utils.php";
require_once "utils/http.php";
require_once "utils/ngrok.php";
require_once "mac.php";

class ZaloPayHelper {
  private static $publickey;
  private static $uid;

  static function init() {
    # Public key nhận được khi đăng ký ứng dụng với zalopay
    self::$publickey = file_get_contents('publickey.pem');
    self::$uid = GetTimestamp();
  }

  /*
   * Kiểm callback có hợp lệ hay không 
   * 
   * Tham số: Array { data: String, mac: String }
   * Trả về: Array { returncode: int, returnmessage: String }
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Callback
   * */
  static function VerifyCallback(Array $params) {
    $data = $params["data"];
    $requestMac = $params["mac"];

    $result = [];
    $mac = hash_hmac("sha256", $data, Config::get()['key2']);

    if ($mac != $requestMac) {
      $result['returncode'] = -1;
      $result['returnmessage'] = 'mac not equal';
    } else {
      $result['returncode'] = 1;
      $result['returnmessage'] = 'success';
    }

    return $result;
  }

  /*
   * Kiểm callback có hợp lệ hay không 
   * 
   * Tham số: Array - là query string mà zalopay truyền vào redirect link ($_GET)
   * Trả về: bool
   *  - true: hợp lệ
   *  - false: không hợp lệ
   * Xem thêm: https://docs.zalopay.vn/docs/gateway/api.html#Redirect
   * */
  static function VerifyRedirect(Array $data) {
    $req_checksum = $data["checksum"];
    $mac_data = $data['appid'].'|'.$data['apptransid'].'|'.$data['pmcid'].'|'.$data['bankcode'].'|'.$data['amount'].'|'.$data['discountamount']."|".$data["status"];
    $checksum = hash_hmac("sha256", $mac_data, Config::get()['key2']);

    return $req_checksum === $checksum;
  }

  /*
   * Generate apptransid hoặc mrefundid
   * - apptransid có dạng yyMMddxxxxx, xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Thông-tin-đơn-hàng
   * - mrefundid có dạng yyMMdd_appid_xxxxx, xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Dữ-liệu-truyền-vào-api-2
   * */
  static function GenTransID() {
    return date("ymd")."_".Config::get()['appid']."_".(++self::$uid);
  }

  /*
   * Tạo Array chứa các tham số cần thiết để truyền vào API "tạo đơn hàng"
   * 
   * Tham số: Array {
   *   amount: long,
   *   description: String,
   *   bankcode: String (optional - default "zalopayapp")
   *   appsuer: String (optional - default "demo")
   *   item: String (optional - default "")
   * }
   * Trả về: Array
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Thông-tin-đơn-hàng
   * */
  static function NewOrder(Array $params) {
    $embedata = [];

    if (!empty(Ngrok::$public_url)) {
      $embedata["forward_callback"] = Ngrok::$public_url . '/callback.php';
    }

    $order = [
      "appid" => Config::get()["appid"],
      "apptime" => GetTimeStamp(),
      "apptransid" => self::GenTransID(),
      "appuser" => isset($params["appuser"]) ? $params["appuser"] : "demo",
      "item" => isset($params["item"]) ? $params["item"] : "",
      "embeddata" => JSON::encode($embedata),
      "amount" => $params['amount'],
      "description" => $params['description'],
      "bankcode" =>  isset($params["bankcode"]) ? $params["bankcode"] : "zalopayapp"
    ];

    return $order;
  }

  /*
   * Nhận vào thông tin đơn hàng và tạo đơn hàng thông qua API "tạo đơn hàng"
   * 
   * Tham số: Array - Thông tin đơn hàng
   * Trả về: Array - Kết quả tạo đơn hàng
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Tạo-đơn-hàng
   * */
  static function CreateOrder(Array $order) {
    $order['mac'] = ZaloPayMacGenerator::CreateOrder($order);
    $result = Http::PostForm(Config::get()['api']['createorder'], $order);
    return $result;
  }

  /*
   * Nhận vào thông tin đơn hàng và tạo url để thanh toán cổng
   * 
   * Tham số: Array - Thông tin đơn hàng
   * Trả về: String - là url để thanh toán cổng
   * Xem thêm: https://docs.zalopay.vn/docs/gateway/api.html
   * */
  static function Gateway(Array $order) {
    $order['mac'] = ZaloPayMacGenerator::CreateOrder($order);
    $orderJSON = JSON::encode($order);

    return Config::get()['api']['gateway'] . urlencode(base64_encode($orderJSON));
  }

  /*
   * Tạo một Array chứa các thâm số cần thiết để truyền vào API "Quickpay"
   * 
   * Tham số: Array - như hàm NewOrder, nhưng có thêm tham số paymentcodeRaw
   * Trả về: Array
   * Xem thêm: https://docs.zalopay.vn/docs/quickpay/api.html#Dữ-liệu-đầu-vào-api
   * */
  static function NewQuickPayOrder(Array $params) {
    $order = self::NewOrder($params);
    $order['userip'] = isset($params['userip']) ? $params['userip'] : "127.0.0.1";
    openssl_public_encrypt($params['paymentcodeRaw'], $encrypted, self::$publickey);
    $order['paymentcode'] = base64_encode($encrypted);
    $order['mac'] = ZaloPayMacGenerator::QuickPay($order, $params['paymentcodeRaw']);
    return $order;
  }

  /*
   * Nhận vào thông tin đơn hàng và tiến hành thanh toán thông qua API "Quickpay"
   * 
   * Tham số: Array - Thông tin đơn hàng
   * Trả về: Array - Kết quả giao dịch
   * Xem thêm: https://docs.zalopay.vn/docs/quickpay/api.html
   * */
  static function QuickPay(Array $order) {
    $result = Http::PostForm(Config::get()['api']['quickpay'], $order);
    return $result;
  }

  /*
   * Nhận vào apptransid của đơn hàng và tiến hành truy vấn thông tin đơn hàng thông qua API "Truy vấn đơn hàng"
   * 
   * Tham số: String - apptransid của đơn hàng
   * Trả về: Array - Trạng thái đơn hàng
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Truy-vấn-trạng-thái-thanh-toán-của-đơn-hàng
   * */
  static function GetOrderStatus(string $apptransid) {
    $params = [
      "appid" => Config::get()['appid'],
      "apptransid" => $apptransid
    ];
    $params["mac"] = ZaloPayMacGenerator::GetOrderStatus($params);
    return Http::PostForm(Config::get()['api']['getorderstatus'], $params);
  }

  /*
   * Tạo một Array chứa các thâm số cần thiết để truyền vào API "Refund"
   * 
   * Tham số: Array - xem thêm ở link dưới
   * Trả về: Array
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.htmll#Hoàn-tiền-giao-dịch
   * */
  static function NewRefund(Array $params) {
    $refundReq = [
      "appid" => Config::get()['appid'],
      "zptransid" => $params['zptransid'],
      "amount" => $params['amount'],
      "description" => $params['description'],
      "timestamp" => GetTimestamp(),
      "mrefundid" => self::GenTransID()
    ];

    $refundReq['mac'] = ZaloPayMacGenerator::Refund($refundReq);
    return $refundReq;
  }

  /*
   * Nhận vào thông tin hoàn tiền và tiến hành hoàn tiền thông qua API "Hoàn tiền"
   * 
   * Tham số: Array - Thông tin hoàn tiền
   * Trả về: Array - Kết quả hoàn tiền
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.htmll#Hoàn-tiền-giao-dịch
   * */
  static function Refund(Array $refundReq) {
    $result = Http::PostForm(Config::get()['api']['refund'], $refundReq);
    $result['mrefundid'] = $refundReq['mrefundid'];

    return $result;
  }

  /*
   * Nhận vào mrefundid của yêu cầu hoàn tiền và tiến hành truy vấn thông tin hoàn tiền thông qua API "GetRefundStatus"
   * 
   * Tham số: String - mrefundid của yêu cầu hoàn tiền
   * Trả về: Array - Trạng thái hoàn tiền
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Truy-vấn-trạng-thái-hoàn-tiền---GetRefundStatus
   * */
  static function GetRefundStatus(String $mrefundid) {
    $params = [
      "appid" => Config::get()['appid'],
      "mrefundid" => $mrefundid,
      "timestamp" => GetTimestamp()
    ];

    $params['mac'] = ZaloPayMacGenerator::GetRefundStatus($params);
    return Http::PostForm(Config::get()['api']['getrefundstatus'], $params);
  }

  /*
   * Lấy danh sách ngân hàng được hỗ trợ thông qua API "Getbanklist"
   * 
   * Trả về: Array - Danh sách ngân hàng, xem thêm ở link dưới
   * Xem thêm: https://docs.zalopay.vn/docs/general/overview.html#Lấy-danh-sách-các-ngân-hàng-được-hỗ-trợ
   * */
  static function GetBankList() {
    $params = [
      "appid" => Config::get()['appid'],
      "reqtime" => GetTimestamp()
    ];

    $params['mac'] = ZaloPayMacGenerator::GetBankList($params);
    return Http::PostForm(Config::get()['api']['getbanklist'], $params);
  }
}

ZaloPayHelper::init();