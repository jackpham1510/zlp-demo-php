<?php

function GetTimestamp() {
  return round(microtime(true) * 1000);
}