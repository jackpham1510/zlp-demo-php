<?php

require_once "json.php";

class Http {
  static function PostForm($url, $params) {
    $context = stream_context_create([
      "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        "method" => "POST",
        "content" => http_build_query($params)
      ]
    ]);
    
    return JSON::decode(file_get_contents($url, false, $context));
  }

  static function Get($url) {
    return JSON::decode(file_get_contents($url));
  }
}