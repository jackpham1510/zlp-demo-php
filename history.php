<?php 
  require "repository/order_repository.php";
  require "repository/refund_repository.php";

  function statusHTML($status) {
    switch ($status) { 
      case -1: return "<span class='text-danger'>Thất bại</span>";
      case 1: return "<span class='text-success'>Thành công</span>";
      default: return "<span class='text-info'>Đang xử lý</span>";
    }
  }

  session_start();

  $page = 1;
  if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = (int) $_GET['page'];
  }

  $data = OrderRepository::paginate($page);
?>

<!DOCTYPE html>
<html lang="en">
<?php include "components/head.php" ?>
<body>
  <?php include "components/navbar.php" ?>
  <div class="container-fluid mt-4">
    <?php if (isset($_SESSION["refundResult"])) { 
      $refundResult = $_SESSION["refundResult"];  
    ?>
      <div class="alert alert-<?php echo $refundResult["returncode"] >= 1 ? "success" : "danger" ?> mb-4">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <p class="mb-1"><b>Kết quả hoàn tiền</b></p>
        <ul class="mb-0 pl-3">
          <?php foreach($refundResult as $key => $value) { ?>
            <li><?php echo "<b>$key</b>: $value"; ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php }
      unset($_SESSION["refundResult"]);
    ?>
    <h3 class="text-center text-primary my-4 font-weight-bold">Lịch sử giao dịch</h3>
    <table id="historyTable" class="table table-hover">
      <thead>
        <tr>
          <th>Apptransid</th>
          <th>Zptransid</th>
          <th>Kênh thanh toán</th>
          <th>Mô tả</th>
          <th>Thời gian</th>
          <th>Số tiền</th>
          <th>Số tiền đã hoàn</th>
          <th>Trạng thái</th>
          <th>Tùy chọn</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($data["orders"] as $order) { ?>
        <tr data-row id="<?php echo $order["zptransid"] ?>">
          <td width="300"><?php echo $order["apptransid"] ?></td>
          <td width="150"><?php echo $order["zptransid"] ?></td>
          <td width="200"><?php echo $order["channel"] ?></td>
          <td><?php echo $order["description"] ?></td>
          <td width="200"><?php echo date("d/m/Y H:i:s", $order["timestamp"] / 1000) ?></td>
          <td width="100"><?php echo $order["amount"] ?></td>
          <td width="200"><?php echo $order["total_refund_amount"] ?></td>
          <td width="150"><?php echo statusHTML($order["status"]) ?></td>
          <td width="150">
            <?php if ($order["status"] === "1") { ?>
              <button class="btn btn-primary refund"
                data-zptransid="<?php echo $order["zptransid"] ?>"
                data-max-amount="<?php echo $order["amount"] ?>">Hoàn tiền</button><br/>
              <button class="btn btn-primary d-none getrefundstatus mt-2">GetRefundStatus</button>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
    <nav id="pagination" class="d-flex justify-content-center my-4"></nav>
  </div>

  <form class="modal fade" id="modal" tabindex="-1" role="dialog" action="/refund.php" method="POST">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Hoàn tiền giao dịch</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <input type="hidden" id="refundZptransid" name="zptransid" />
        <input type="hidden" name="currentPage" value="<?php echo $page ?>" />
        <div class="modal-body">
          <div class="form-group">
            <label for="description">Lý do hoàn tiền</label>
            <textarea type="text" class="form-control" name="description" placeholder="Nhập lý do hoàn tiền"></textarea>
          </div>
          <div class="form-group">
            <label for="amount">Số tiền</label>
            <input type="number" class="form-control" id="amount" name="amount" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy bỏ</button>
          <button type="submit" class="btn btn-primary">Hoàn tiền</button>
        </div>
      </div>
    </div>
  </form>
  <script>
    $('#pagination').pagination({
      items: JSON.parse('<?php echo JSON::encode($data["orders"]) ?>'),
      itemsOnPage: <?php echo $data["orderPerPage"] ?>,
      currentPage: <?php echo $data["currentPage"] ?>,
      cssStyle: 'light-theme',
      nextText: '>>',
      prevText: '<<',
      onPageClick(page) {
        window.location.search = 'page='+page;
      }
    });

    $('[data-row] button.refund').click(function () {
      const { zptransid, maxAmount } = $(this).data();
      $('#modal').modal();
      $('#refundZptransid').val(''+zptransid);
      $('#amount').val(''+maxAmount).attr('max', ''+maxAmount);
    });
  </script>
</body>
</html>