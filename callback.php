<?php

require_once "utils/json.php";
require_once "respository/order_respository.php";
require_once "zalopay/helper.php";

$is_post_method = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($is_post_method) {
  try {
    $params = JSON::decode(file_get_contents('php://input'));

    # Kiểm tra xem callback có hợp lệ không
    $result = ZaloPayHelper::VerifyCallback($params);
    
    if ($result['returncode'] === 1) {
      # Giao dịch thành công, tiền hành xử lý đơn hàng
      $data = JSON::decode($params["data"]);
      OrderRespository::Update([
        "apptransid" => $data["apptransid"],
        "zptransid" => $data["zptransid"],
        "channel" => $data["channel"],
        "status" => 1
      ]);
    }
    
    echo JSON::encode($result);
  } catch (Exception $e) {
    echo JSON::encode([
      "returncode" => 0, # ZaloPay Server sẽ callback lại tối đa 3 lần
      "returnmessage" => "exception"
    ]);
  }
}
else {
  http_response_code(405);
}