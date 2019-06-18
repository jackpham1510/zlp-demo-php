<?php 
  require "zalopay/helper.php";
  require "repository/order_repository.php";

  $isPostMethod = $_SERVER['REQUEST_METHOD'] === 'POST';
  $result = NULL;
  $error = NULL;

  if ($isPostMethod) {
    $amount = (int)$_POST["amount"];
    if ($amount < 1000) {
      $error = "Số tiền không hợp lệ";
    } else {
      $orderData = ZaloPayHelper::newQuickPayOrderData($_POST);
      $result = ZaloPayHelper::quickpay($orderData);

      # returncode > 1: đơn hàng đang được thanh toán
      if ($result["returncode"] > 0) {
        OrderRepository::add($orderData);
      }
    }
  }
?>

<?php if (isset($error)) { ?>
  <script>
    alert('<?php echo $error; ?>');
  </script>
<?php } ?>

<!DOCTYPE html>
<html lang="en">
<?php include "components/head.php"; ?>
<body>
  <?php include "components/navbar.php"; ?>
  <h3 class="text-center text-primary my-4 font-weight-bold">Quickpay</h3>
  <form class="container mt-5" action="quickpay.php" method="POST">
    <?php if (isset($result)) { ?>
      <div class="alert alert-<?php echo $result["returncode"] > 0 ? "info": "danger" ?>">
        <table>
          <tr>
            <td><b>returncode</b></td>
            <td class="pl-2"><?php echo $result["returncode"] ?></td>
          </tr>
          <tr>
            <td><b>returnmessage</b></td>
            <td class="pl-2"><?php echo $result["returnmessage"] ?></td>
          </tr>
        </table>
      </div>
    <?php } ?>
    <div class="form-group">
      <label for="paymentcodeRaw">Mã thanh toán <span class="text-danger">*</span></label>
      <input type="text" class="form-control" name="paymentcodeRaw" placeholder="Nhập mã thanh toán">
      <small class="text-muted">Bạn có thể quét mã thanh toán <a href="https://mep.zpapps.vn/docs/quickpay/demo.html" target="_blank">ở đây</a> 
        (Click <img src="/static/images/scanicon.png" width="60" /> ở trường "Mã thanh toán")</small>
    </div>
    <div class="form-group">
      <label for="description">Mô tả</label>
      <textarea type="text" class="form-control" name="description" placeholder="Nhập mô tả"></textarea>
    </div>
    <div class="form-group">
      <label for="exampleInputPassword1">Số tiền <span class="text-danger">*</span></label>
      <input type="number" class="form-control" name="amount" placeholder="Nhập số tiền" value="1000">
      <small class="form-text text-muted">Số tiền tối thiểu là 1000 VNĐ</small>
    </div>
    <button type="submit" class="btn btn-primary">Thanh toán</button>
  </form>
</body>