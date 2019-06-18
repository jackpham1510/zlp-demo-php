<?php

require_once "utils/json.php";
require_once "repository/order_repository.php";
require_once "zalopay/helper.php";

$data = $_GET;

# Kiểm tra xem redirect có hợp lệ hay không
$isValidRedirect = ZaloPayHelper::verifyRedirect($data);

if ($isValidRedirect) {
  $apptransid = $data["apptransid"];
  
  # Kiểm tra xem đã nhận được callback chưa
  # - ở đây nếu đã nhận được callback thì trường "status" != 0
  $order = OrderRepository::getByApptransId($apptransid);
  
  if ($order["status"] === "0") {
    # Nếu chưa nhận được callback thì gọi API truy vấn trạng thái đơn hàng 
    $order_status = ZaloPayHelper::getOrderStatus($apptransid);

    # Cập nhật trạng thái đơn hàng
    $status = $order_status["returncode"] === 1 ? 1 : -1;
    OrderRepository::update([
      "apptransid" => $apptransid,
      "zptransid" => $order_status["zptransid"],
      "channel" => $order_status["pmcid"],
      "status" => $status,
    ]);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include "components/head.php" ?>
<body>
  <?php include "components/navbar.php" ?>
  <div class="container mt-5">
    <?php if ($isValidRedirect) { ?>
      <h4 class="text-center text-primary font-weight-bold mb-5">
        Kết quả giao dịch
      </h4>
      <table class="table">
        <?php foreach($data as $key => $value) { ?>
        <tr>
          <td><b><?php echo $key ?></b></td>
          <td><?php echo $value ?></td>
        </tr>
        <?php } ?>
      </table>
    <?php } else { ?>
      <div class="alert alert-danger">
        <strong>Lỗi: </strong> Redirect không hợp lệ
      </div>
    <?php } ?>
  </div>
</body>