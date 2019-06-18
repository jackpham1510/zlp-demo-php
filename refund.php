<p>Giao dịch đang được xử lý...</p>

<?php 

require_once "zalopay/helper.php";
require_once "repository/refund_repository.php";

session_start();

$isPostMethod = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPostMethod) {
  $refundData = ZaloPayHelper::newRefundData($_POST);
  $result = ZaloPayHelper::refund($refundData);

  # returncode == 1: thành công
  # returncode > 1: đang xử lý
  # returncode < 1: thất bại
  if ($result["returncode"] >= 1) {
    # Lấy trạng thái cuối cùng của refund
    while (1) {
      $refundStatus = ZaloPayHelper::getRefundStatus($result["mrefundid"]);
      $c = $refundStatus["returncode"];

      if ($c < 2) {
        # Refund đã hoàn tất
        $status = $c === 1 ? 1 : -1; # returncode === 1: thành công, < 0: thất bại
        if ($status === 1) {
          RefundRepository::add($refundData);
        }

        $result = $refundStatus;
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