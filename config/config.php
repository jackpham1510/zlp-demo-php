<?php

require_once "utils/json.php";

class Config {
  private static $config;

  static function init() {
    self::$config = JSON::parseFile("config.json");
  }

  static function get() {
    return self::$config;
  }
}

Config::init();