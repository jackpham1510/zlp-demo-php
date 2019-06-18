<?php 
  require "zalopay/helper.php";
  require "repository/order_repository.php";

  $isPostMethod = $_SERVER['REQUEST_METHOD'] === 'POST';
  $banklist = [];
  $error = null;

  if ($isPostMethod) {
    $amount = (int)$_POST["amount"];
    if ($amount < 1000) {
      $error = "Số tiền không hợp lệ";
    } else {
      $data = $_POST;
      if ($data["bankcode"] === "ATM") {
        $data["bankcode"] = "";
        $data["embeddata"] = ["bankgroup" => "ATM"];
      }
      $orderData = ZaloPayHelper::newCreateOrderData($data);
      $order = ZaloPayHelper::createOrder($orderData);

      if ($order["returncode"] === 1) {
        OrderRepository::add($orderData);
        $orderUrl = $order["orderurl"];
        header("Location: ". $orderUrl);
      }
      else {
        $error = "Tạo đơn hàng thất bại";
      }
      
      # Chuyển hướng sang trang cổng thanh toán
    }
  } else {
    $banklist = ZaloPayHelper::getBankList();
  }
?>

<?php if (isset($error)) { ?>
  <script>
    alert('<?php echo $error; ?>');
  </script>
<?php } ?>

<!DOCTYPE html>
<html lang="en">
<?php  include "components/head.php"; ?>
<body>
  <?php include "components/navbar.php"; ?>
  <h3 class="text-center text-primary my-4 font-weight-bold">Gateway</h3>
  <form class="container mt-5" action="/gateway.php" method="POST">
    <label>Thông tin thẻ test</label>
    <ul>
      <li><b>Số thẻ:</b> 4111111111111111</li>
      <li><b>Tên:</b> NGUYEN VAN A</li>
      <li><b>Ngày hết hạn:</b> 01/21</li>
      <li><b>Mã CVV:</b> 123</li>
    </ul>
    <div class="form-group">
      <label for="bankcode">Ngân hàng</label>
      <select name="bankcode" class="form-control">
        <option value="ATM">ATM</option>
        <?php
        foreach ($banklist["banks"] as $pmcid => $bankDTOs) {
          foreach ($bankDTOs as $bankDTO) {
        ?>
          <option value="<?php echo $bankDTO["bankcode"]; ?>">
            <?php echo $bankDTO["name"]; ?>
          </option>
        <?php
          }
        }
        ?>
      </select>
    </div>
    <div class="form-group">
      <label for="description">Mô tả</label>
      <textarea type="text" class="form-control" name="description" placeholder="Nhập mô tả"></textarea>
    </div>
    <div class="form-group">
      <label for="exampleInputPassword1">Số tiền <span class="text-danger">*</span></label>
      <input type="number" class="form-control" name="amount" placeholder="Nhập số tiền" value="10000">
      <small class="form-text text-muted">Số tiền tối thiểu là 1000 VNĐ</small>
    </div>
    <button type="submit" class="btn btn-primary">Thanh toán</button>
  </form>
</body>