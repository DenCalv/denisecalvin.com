<?php
// Contact form handler — emails submissions to the address below.
// SMTP settings live in config.local.php (gitignored); see config.local.php.example.

const RECIPIENT = 'nattyden@hotmail.com';

function redirect_back(string $status): void {
    header('Location: /?' . $status . '=1#contact');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /');
    exit;
}

// Honeypot: bots fill hidden fields; pretend success and drop it.
if (!empty($_POST['website'])) {
    redirect_back('sent');
}

function clean(string $key): string {
    // Strip CR/LF to prevent header injection.
    return trim(str_replace(["\r", "\n"], ' ', $_POST[$key] ?? ''));
}

$name = clean('name');
$email = clean('email');
$phone = clean('phone');
$description = trim($_POST['description'] ?? ''); // newlines allowed in the body

if ($name === '' || $description === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_back('error');
}

$subject = 'Website inquiry from ' . $name;
$body = "New inquiry from denisecalvin.com\n\n"
      . "Name: $name\n"
      . "Email: $email\n"
      . "Phone: " . ($phone !== '' ? $phone : '(not provided)') . "\n\n"
      . "Description:\n$description\n";

$config = [];
$configFile = __DIR__ . '/config.local.php';
if (is_file($configFile)) {
    $config = require $configFile;
}

$sent = false;
if (!empty($config['smtp_host'])) {
    $sent = smtp_send($config, RECIPIENT, $email, $name, $subject, $body);
} else {
    // No SMTP configured — fall back to PHP mail().
    $headers = "From: noreply@denisecalvin.com\r\nReply-To: $email\r\n";
    $sent = @mail(RECIPIENT, $subject, $body, $headers);
}

redirect_back($sent ? 'sent' : 'error');

/**
 * Minimal dependency-free SMTP client (AUTH LOGIN, SSL or STARTTLS).
 */
function smtp_send(array $c, string $to, string $replyTo, string $replyName, string $subject, string $body): bool {
    $host = $c['smtp_host'];
    $port = (int)($c['smtp_port'] ?: 587);
    $encryption = $c['smtp_encryption'] ?? 'tls'; // 'ssl' (465), 'tls' (587 STARTTLS), or 'none'
    $from = $c['smtp_from_email'] ?: $c['smtp_username'];
    $fromName = $c['smtp_from_name'] ?: 'denisecalvin.com';

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
    $fp = @stream_socket_client("$remote:$port", $errno, $errstr, 15);
    if (!$fp) {
        error_log("contact.php SMTP connect failed: $errstr ($errno)");
        return false;
    }
    stream_set_timeout($fp, 15);

    $expect = function (string $codes) use ($fp): bool {
        do {
            $line = fgets($fp, 1024);
            if ($line === false) return false;
        } while (isset($line[3]) && $line[3] === '-'); // skip multi-line responses
        return in_array(substr($line, 0, 3), explode(',', $codes), true);
    };
    $send = function (string $cmd, string $codes) use ($fp, $expect): bool {
        fwrite($fp, $cmd . "\r\n");
        return $expect($codes);
    };

    $ok = $expect('220')
        && $send('EHLO denisecalvin.com', '250');

    if ($ok && $encryption === 'tls') {
        $ok = $send('STARTTLS', '220')
            && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
            && $send('EHLO denisecalvin.com', '250');
    }

    if ($ok && !empty($c['smtp_username'])) {
        $ok = $send('AUTH LOGIN', '334')
            && $send(base64_encode($c['smtp_username']), '334')
            && $send(base64_encode($c['smtp_password']), '235');
    }

    $message = "From: $fromName <$from>\r\n"
             . "To: $to\r\n"
             . "Reply-To: $replyName <$replyTo>\r\n"
             . "Subject: $subject\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "\r\n"
             . str_replace("\n.", "\n..", $body); // dot-stuffing

    $ok = $ok
        && $send("MAIL FROM:<$from>", '250')
        && $send("RCPT TO:<$to>", '250,251')
        && $send('DATA', '354')
        && $send($message . "\r\n.", '250');

    fwrite($fp, "QUIT\r\n");
    fclose($fp);

    if (!$ok) {
        error_log('contact.php SMTP send failed');
    }
    return $ok;
}
