<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

class PhpMailerService
{
    private function makeMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) env('MAIL_HOST', '127.0.0.1');
        $mail->Port = (int) env('MAIL_PORT', 1025);
        $username = (string) env('MAIL_USERNAME', '');
        $password = (string) env('MAIL_PASSWORD', '');
        $mail->SMTPAuth = $username !== '';
        $mail->Username = $username;
        $mail->Password = $password;

        $encryption = strtolower((string) env('MAIL_ENCRYPTION', ''));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            (string) env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            (string) env('MAIL_FROM_NAME', 'PWD Portal')
        );

        return $mail;
    }

    public function sendPlainText(string $to, string $subject, string $body): bool
    {
        try {
            $mail = $this->makeMailer();
            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();

            return true;
        } catch (\Throwable $e) {
            Log::warning('PHPMailer send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendOtpEmail(string $to, string $otp, string $recipientLabel = 'Applicant'): bool
    {
        $subject = 'PWD Portal OTP Verification';
        $plainText = "Hi {$recipientLabel}, your PWD Portal verification code is {$otp}. It expires in 10 minutes.";

        try {
            $mail = $this->makeMailer();
            $mail->addAddress($to);

            $logoPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'PWD_frontend' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'whitelogo.png';
            $logoCid = 'pwd_logo_white';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, $logoCid, 'whitelogo.png');
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#111;">'
                . '<div style="margin-bottom:12px;">'
                . (is_file($logoPath)
                    ? '<img src="cid:' . $logoCid . '" alt="PWD Portal" style="max-width:170px;height:auto;" />'
                    : '')
                . '</div>'
                . '<h2 style="margin:0 0 10px 0;color:#0f172a;">PWD Portal OTP Verification</h2>'
                . '<p style="margin:0 0 10px 0;">Hi <strong>' . htmlspecialchars($recipientLabel, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p style="margin:0 0 14px 0;">Your one-time verification code is:</p>'
                . '<div style="display:inline-block;padding:10px 16px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;font-size:24px;font-weight:700;letter-spacing:2px;">'
                . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8')
                . '</div>'
                . '<p style="margin:14px 0 0 0;">It expires in <strong>10 minutes</strong>.</p>'
                . '<p style="margin:10px 0 0 0;color:#475569;font-size:13px;">If you did not request this code, you can ignore this email.</p>'
                . '</div>';
            $mail->AltBody = $plainText;
            $mail->send();

            return true;
        } catch (\Throwable $e) {
            Log::warning('PHPMailer OTP send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
