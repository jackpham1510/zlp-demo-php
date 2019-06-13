<?php

require_once "config/config.php";
require_once "json.php";
require_once "http.php";

/*
 * Lấy public url của ngrok
 * Xem thêm: README.md
 * */
class Ngrok {
  static $PUBLIC_URL;

  static function init()
  {
    $ngrokTunnels = Config::get()["ngrok"]["tunnels"];
    $res = Http::getJSON($ngrokTunnels);
    self::$PUBLIC_URL = $res["tunnels"][0]["public_url"];
  }
}

Ngrok::init();