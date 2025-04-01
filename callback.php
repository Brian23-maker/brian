<?php
$callbackResponse = file_get_contents("php://input");
$logFile = "stk_callback_response.json";
file_put_contents($logFile, $callbackResponse);
?>