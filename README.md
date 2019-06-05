# Demo tích hợp ZaloPay cho PHP

Demo tích hợp các API của ZaloPay cho PHP

* Phiên bản: 1.0

## Cài đặt

1. Môi trường chạy PHP
   * Apache / Nginx + PHP 7.0 + Mysql hoặc XAMPP / WAMPP 
2. Tạo database `zalopay-demo` (`utf8_unicode_ci`)
3. Tạo các bảng cần thiết bằng script sau

```sql
CREATE TABLE `orders` (
  `apptransid` varchar(255) NOT NULL,
  `zptransid` varchar(255),
  `description` varchar(255),
  `amount` BIGINT(20),
  `timestamp` BIGINT(20),
  `status` INT(3) DEFAULT 0,
  `channel` INT(11),
  PRIMARY KEY (`apptransid`)
);

CREATE TABLE `refunds` (
  `mrefundid` varchar(255),
  `zptransid` varchar(255),
  `amount` BIGINT(20),
  PRIMARY KEY (`mrefundid`)
);
```

4. Config database trong file [`config.json`](./config.json)

```json
{
  "db": {
    "host": "<db-host>",
    "port": 3306,
    "dbname": "zalopay-demo",
    "user": "<db-username>",
    "password": "<db-password>"
  }
}
```

5. Thay đổi app config trong [`config.json`](./config.json)

```json
{
  "appid": "<appid>",
  "key1": "<key1>",
  "key2": "<key2>"
}
```

6. Thay đổi RSA public key trong [`publickey.pem`](./publickey.pem)

```pem
-----BEGIN PUBLIC KEY-----
xxxxxx (64 chars)
xxxxxx (64 chars)
-----END PUBLIC KEY-----
```

## Các API tích hợp trong Demo

* Xử lý callback
* Xử lý Redirect
* Thanh toán QR
* Cổng ZaloPay
* QuickPay
* Mobile Web to App
* Hoàn tiền
* Lấy trạng thái đơn hàng
* Lấy trạng thái hoàn tiền
* Lấy danh sách ngân hàng

## Nhận callback ở môi trường local thông qua Ngrok

* Xem thêm [ở đây](https://github.com/tiendung1510/zlp-forward-callback-proxy)