<?php

require_once "provider/provider.php";

class OrderRepository {
  private static $ORDER_PER_PAGE = 10;

  static function add(Array $data) {
    $conn = Provider::connect();
    $sql = sprintf("INSERT INTO `orders`(`apptransid`, `zptransid`, `description`, `amount`, `timestamp`, `channel`, `status`) VALUES ('%s',NULL,'%s',%d,%d,NULL,0)",
      $data["apptransid"],
      $data["description"],
      $data["amount"],
      $data["apptime"]);
    
    $ok = $conn->query($sql);
    $conn->close();
    return $ok;
  }

  static function update(Array $data) {
    $conn = Provider::connect();
    $apptransid = $data["apptransid"];
    $zptransid = $data["zptransid"];
    $channel = $data["channel"];
    $status = $data["status"];
    $ok = $conn->query("UPDATE `orders` SET `zptransid`='$zptransid', `channel`=$channel, `status`=$status WHERE `apptransid`='$apptransid'");
    $conn->close();
    return $ok;
  }

  static function paginate(int $page = 1) {
    $offset = ($page - 1) * self::$ORDER_PER_PAGE;
    $orders = Provider::select(
      "SELECT `o`.*, sum(
        CASE 
          WHEN `r`.`amount` IS NULL
          THEN 0 
          ELSE `r`.`amount` 
        END
      ) as `total_refund_amount`
      FROM `orders` as `o` LEFT JOIN `refunds` as `r`
      ON `o`.`zptransid`=`r`.`zptransid`
      GROUP BY `o`.`apptransid`, `o`.`zptransid`, `o`.`description`, `o`.`amount`, `o`.`timestamp`, `o`.`status`, `o`.channel
      ORDER BY `o`.`timestamp` DESC
      LIMIT $offset, ".self::$ORDER_PER_PAGE);
    $totalOrder = Provider::select("SELECT COUNT(*) as totalOrder FROM `orders`")[0]["totalOrder"];

    return [
      "currentPage" => $page,
      "totalOrder" => $totalOrder,
      "orders" => $orders,
      "orderPerPage" => self::$ORDER_PER_PAGE
    ];
  }

  static function getByApptransId(string $apptransid) {
    return Provider::select("SELECT * FROM `orders` WHERE `apptransid`='$apptransid'")[0];
  }
}