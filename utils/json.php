<?php

class JSON {
  static function encode(Array $data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE);
  }

  static function decode(string $data) {
    return json_decode($data, true);
  }

  static function parseFile(string $path) {
    $jsonStr = file_get_contents($path);
    return JSON::decode($jsonStr);
  }
}