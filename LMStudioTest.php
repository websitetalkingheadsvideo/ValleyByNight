<?php
header('Content-Type: application/json; charset=utf-8');

$payload = [
  "model" => "PASTE_MODEL_ID_HERE",
  "messages" => [
    ["role" => "user", "content" => "Say hello in one short sentence."]
  ],
  "temperature" => 0.3
];

$ch = curl_init("http://192.168.0.217:1234/v1/chat/completions");
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 60
]);

$res = curl_exec($ch);
if ($res === false) {
  http_response_code(500);
  echo json_encode(["error" => "curl_error", "message" => curl_error($ch)]);
  exit;
}

echo $res;
