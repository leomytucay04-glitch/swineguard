<?php
/**
 * SwineGuard - Email Dispatch Service
 */

// Load PHPMailer classes
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a verification email containing both a 6-digit OTP and a direct 1-click link.
 * 
 * @param string $toEmail Recipient Gmail address
 * @param string $otpCode 6-digit numeric verification code
 * @param string $verifyLink Full clickable verification URL
 * @param string $recipientName Display name of recipient
 * @return array ['success' => bool, 'mode' => 'smtp'|'dev', 'message' => string, 'code' => string]
 */
function sendVerificationEmail($toEmail, $otpCode, $verifyLink, $recipientName = 'User') {
    $configFile = __DIR__ . '/email_config.php';
    $config = file_exists($configFile) ? include($configFile) : [];

    $smtpHost   = $config['smtp_host'] ?? 'smtp.gmail.com';
    $smtpPort   = (int)($config['smtp_port'] ?? 587);
    $smtpSecure = strtolower($config['smtp_secure'] ?? 'tls');
    $smtpUser   = trim($config['smtp_user'] ?? '');
    $smtpPass   = trim($config['smtp_pass'] ?? '');
    $fromEmail  = !empty($config['from_email']) ? trim($config['from_email']) : $smtpUser;
    $fromName   = $config['from_name'] ?? 'SwineGuard Ecosystem';
    $devMode    = !empty($config['dev_mode']) && $config['dev_mode'] !== 'false' && $config['dev_mode'] !== false;
    $expiryMins = (int)($config['otp_expiry_mins'] ?? 10);

    // Build the responsive HTML body
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Gmail - SwineGuard</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .container { max-width: 580px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
        .badge { display: inline-block; background: #10b981; color: #ffffff; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; margin-bottom: 12px; }
        .content { padding: 32px 30px; }
        .greeting { font-size: 16px; font-weight: 600; margin-bottom: 12px; }
        .desc { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
        .otp-box { background: #f8fafc; border: 2px dashed #10b981; border-radius: 10px; text-align: center; padding: 20px; margin: 20px 0; }
        .otp-label { font-size: 12px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 1px; margin-bottom: 6px; }
        .otp-code { font-size: 36px; font-weight: 800; color: #0f172a; letter-spacing: 8px; font-family: 'Courier New', Courier, monospace; }
        .btn-wrap { text-align: center; margin: 26px 0 20px; }
        .verify-btn { display: inline-block; background: #10b981; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 13px 32px; border-radius: 8px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25); }
        .verify-btn:hover { background: #059669; }
        .note { font-size: 12px; color: #94a3b8; line-height: 1.5; border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 24px; }
        .footer { background: #f8fafc; padding: 18px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Livestock Protection Platform</span>
            <h1>SwineGuard Ecosystem</h1>
            <p>Account Security & Incident Notification Verification</p>
        </div>
        <div class="content">
            <div class="greeting">Hello, {$safeName}!</div>
            <p class="desc">
                You recently logged in to SwineGuard. To finalize your account setup, receive vital temperature/humidity alert notices, and secure your access, please verify your Gmail address: <strong>{$safeEmail}</strong>.
            </p>
            
            <div class="otp-box">
                <div class="otp-label">Your 6-Digit Verification Code</div>
                <div class="otp-code">{$otpCode}</div>
            </div>

            <div class="btn-wrap">
                <a href="{$verifyLink}" class="verify-btn" target="_blank">Confirm & Link Gmail Instantly &rarr;</a>
            </div>

            <p class="desc" style="font-size: 13px; text-align: center; margin-top: 10px;">
                Alternatively, enter the 6-digit code above directly on the verification screen.
            </p>

            <div class="note">
                &bull; This code is valid for <strong>{$expiryMins} minutes</strong>.<br>
                &bull; If you did not initiate this request, you can safely disregard this email.
            </div>
        </div>
        <div class="footer">
            &copy; 2026 SwineGuard System. All rights reserved.
        </div>
    </div>
</body>
</html>
HTML;

    $plainBody = "Hello {$safeName},\n\nYour SwineGuard Gmail verification code is: {$otpCode}\n\nAlternatively, visit this link to verify directly:\n{$verifyLink}\n\nThis code expires in {$expiryMins} minutes.\nIf you did not request this, please ignore this email.";

    // If SMTP user and pass are provided, attempt live email delivery
    if (!empty($smtpUser) && !empty($smtpPass) && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = str_replace(' ', '', $smtpPass); // remove any copy-paste spaces in app password
            $mail->SMTPSecure = ($smtpSecure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($fromEmail ?: $smtpUser, $fromName);
            $mail->addAddress($toEmail, $recipientName);

            $mail->isHTML(true);
            $mail->Subject = "Verify Your Gmail Address - SwineGuard ({$otpCode})";
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();
            return [
                'success' => true,
                'mode'    => 'smtp',
                'message' => 'Verification email sent successfully to ' . $toEmail,
                'code'    => $otpCode
            ];
        } catch (Exception $e) {
            $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
            // If live SMTP failed but devMode is enabled, fallback to devMode so user is never locked out
            if ($devMode) {
                return [
                    'success' => true,
                    'mode'    => 'dev',
                    'message' => 'SMTP Delivery notice: ' . $errorMsg . ' (Dev fallback active)',
                    'code'    => $otpCode
                ];
            }
            return [
                'success' => false,
                'mode'    => 'smtp_failed',
                'message' => 'Failed to deliver email: ' . $errorMsg,
                'code'    => $otpCode
            ];
        }
    }

    // If SMTP is not yet configured, use Dev Mode fallback
    if ($devMode) {
        return [
            'success' => true,
            'mode'    => 'dev',
            'message' => 'Development mode active. Verification code generated.',
            'code'    => $otpCode
        ];
    }

    return [
        'success' => false,
        'mode'    => 'unconfigured',
        'message' => 'SMTP is not configured in include/email_config.php. Please add your Gmail and App Password.',
        'code'    => $otpCode
    ];
}
