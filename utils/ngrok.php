<?php

require_once "config/config.php";
require_once "http.php";

/*
 * Lấy public url của ngrok
 * Xem thêm: README.md
 * */
class Ngrok {
  static $public_url;

  static function init() {
    $ngrok_tunnels = Config::Get()["ngrok"]["tunnels"];
    $res = Http::Get($ngrok_tunnels);
    self::$public_url = $res["tunnels"][0]["public_url"];
  }
}

Ngrok::init();