<?php

function getTimestamp() {
  return round(microtime(true) * 1000);
}