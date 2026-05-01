<?php
/**
 * app/mailer.php — Thin wrapper around PHPMailer.
 *
 * sendMail(array $config, string $to, string $subject, string $body): bool
 *
 * If $config has a non-empty 'smtp_host', PHPMailer is used with SMTP
 * authentication.  Otherwise the function falls back to PHP's mail().
 *
 * Requires: vendor/autoload.php (composer require phpmailer/phpmailer)
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/** Holds the last PHPMailer/mail error string; empty on success. */
$lastMailError = '';

/**
 * Send a plain-text e-mail.
 *
 * @param array  $config  The $config array from config.php
 * @param string $to      Recipient e-mail address
 * @param string $subject UTF-8 subject (NOT already base64-encoded)
 * @param string $body    Plain-text body
 * @return bool           true on success, false on failure
 */
function sendMail(array $config, string $to, string $subject, string $body): bool
{
    global $lastMailError;
    $lastMailError = '';

    $smtpHost = trim((string)($config['smtp_host'] ?? ''));

    // ── SMTP path ─────────────────────────────────────────────────────────────
    if ($smtpHost !== '') {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            // PHPMailer not installed — fall through to mail()
            return sendMailFallback($config, $to, $subject, $body);
        }
        require_once $autoload;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = (string)($config['smtp_user'] ?? '');
            $mail->Password   = (string)($config['smtp_pass'] ?? '');

            $enc = strtolower(trim((string)($config['smtp_encryption'] ?? 'ssl')));
            if ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->Port = (int)($config['smtp_port'] ?? 465);

            $fromAddr = trim((string)($config['smtp_from'] ?? $config['smtp_user'] ?? ''));
            $fromName = trim((string)($config['smtp_from_name'] ?? 'Site'));
            if ($fromAddr !== '') {
                $mail->setFrom($fromAddr, $fromName);
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML(false);

            return $mail->send();
        } catch (PHPMailerException $e) {
            $lastMailError = $mail->ErrorInfo;
            error_log('PHPMailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    // ── Fallback: native mail() ───────────────────────────────────────────────
    return sendMailFallback($config, $to, $subject, $body);
}

/**
 * @internal  Uses PHP's mail() as a last resort.
 */
function sendMailFallback(array $config, string $to, string $subject, string $body): bool
{
    global $lastMailError;
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $from     = trim((string)($config['smtp_from'] ?? ''));
    if ($from === '') {
        $from = 'noreply@' . $host;
    }
    $fromName = trim((string)($config['smtp_from_name'] ?? 'Site'));

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers        = "From: {$fromName} <{$from}>\r\n"
                    . "Reply-To: {$from}\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "X-Mailer: PHP/" . phpversion() . "\r\n";

    $result = (bool)@mail($to, $encodedSubject, $body, $headers);
    if (!$result) {
        $lastMailError = 'PHP mail() returned false. Check server mail configuration.';
    }
    return $result;
}
