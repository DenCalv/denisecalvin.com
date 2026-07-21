<?php

// /var/www/myapp/webhook.php
// Secret lives in config.local.php (gitignored); see config.local.php.example.

$config = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
$secret = $config['github_webhook_secret'] ?? '';

$deployScript = '/home/lillyjane/deploy_scripts/denisecalvin.com.deploy.sh';
$logFile = '/home/lillyjane/deploy_scripts/denisecalvin.com.deploy.log';

if ($secret === '') {
    http_response_code(500);
    echo "Webhook not configured\n";
    exit;
}

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';


function logMessage(string $logFile, string $message): void
{
  $timestamp = date('Y-m-d H:i:s');
  file_put_contents($logFile, "[$timestamp] $message/n", FILE_APPEND);
}

if (!$payload || !$signature) {
    http_response_code(400);
    echo "Missing payload or signature\n";
    logMessage($logFile, "Missing payload or signature");
    exit;
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    echo "Invalid signature\n";
    logMessage($logFile, "Invalid signature");
    exit;
}
if ($event !== 'push') {
    http_response_code(200);
    echo "Ignored non-push event\n";
    exit;
}

// Run deploy script and capture output
$output = [];
$returnVar = 0;
exec($deployScript . ' 2>&1', $output, $returnVar);

if ($returnVar !== 0) {
    http_response_code(500);
    echo "Deploy failed\n";
    echo implode("\n", $output);
    logMessage($logFile, "Deploy failed");
    logMessage($logFile, implode("\n", $output));
    exit;
}

http_response_code(200);
logMessage($logFile, 'Deploy Successful');
echo "Deploy successful\n";
echo implode("\n", $output);

