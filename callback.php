<?php

require_once "utils/json.php";
require_once "repository/order_repository.php";
require_once "zalopay/helper.php";

$isPostMethod = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPostMethod) {
  try {
    $params = JSON::decode(file_get_contents('php://input'));

    # Kiểm tra xem callback có hợp lệ không
    $result = ZaloPayHelper::verifyCallback($params);
    
    if ($result['returncode'] === 1) {
      # Giao dịch thành công, tiền hành xử lý đơn hàng
      $data = JSON::decode($params["data"]);
      OrderRepository::update([
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