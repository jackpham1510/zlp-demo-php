<p>Giao dịch đang được xử lý...</p>

<?php 

require_once "zalopay/helper.php";
require_once "respository/refund_respository.php";

$is_post_method = $_SERVER['REQUEST_METHOD'] === 'POST';

session_start();

if ($is_post_method) {
  $refundReq = ZaloPayHelper::NewRefund($_POST);
  $result = ZaloPayHelper::Refund($refundReq);

  # returncode == 1: thành công
  # returncode > 1: đang xử lý
  # returncode < 1: thất bại
  if ($result["returncode"] >= 1) {
    # Lấy trạng thái cuối cùng của refund
    while (1) {
      $refund_status = ZaloPayHelper::GetRefundStatus($result["mrefundid"]);
      $c = $refund_status["returncode"];

      if ($c < 2) {
        # Refund đã hoàn tất
        $status = $c === 1 ? 1 : -1; # returncode === 1: thành công, < 0: thất bại
        if ($status === 1) {
          RefundRespository::New($refundReq);
        }

        $result = $refund_status;
        break;
      }

      sleep(1);
    };
  }

  $_SESSION["refundResult"] = $result;
  header("Location: /history.php");
} else {
  http_response_code(405);
}