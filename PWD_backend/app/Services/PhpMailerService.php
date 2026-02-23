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

        $host = (string) (config('mail.mailers.smtp.host') ?: env('MAIL_HOST', '127.0.0.1'));
        $port = (int) (config('mail.mailers.smtp.port') ?: env('MAIL_PORT', 1025));
        $username = trim((string) (config('mail.mailers.smtp.username') ?: env('MAIL_USERNAME', '')));
        $password = trim((string) (config('mail.mailers.smtp.password') ?: env('MAIL_PASSWORD', '')));
        $sendgridApiKey = trim((string) env('SENDGRID_API_KEY', ''));
        $encryption = strtolower((string) (config('mail.mailers.smtp.encryption') ?: env('MAIL_ENCRYPTION', '')));
        $isSendgridHost = str_contains(strtolower($host), 'sendgrid.net');

        if ($isSendgridHost && $username === '') {
            $username = 'apikey';
        }
        if ($isSendgridHost && $password === '' && $sendgridApiKey !== '') {
            $password = $sendgridApiKey;
        }

        $mail->Host = $host;
        $mail->Port = $port;
        $mail->Timeout = (int) env('MAIL_TIMEOUT', 10);
        $mail->SMTPConnectTimeout = (int) env('MAIL_CONNECT_TIMEOUT', 5);
        $mail->Timelimit = (int) env('MAIL_TIME_LIMIT', 10);
        $mail->SMTPKeepAlive = false;
        $mail->SMTPAuth = $username !== '' || ($isSendgridHost && $password !== '');
        $mail->Username = $username;
        $mail->Password = $password;

        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $fromAddress = (string) (config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', 'no-reply@example.com'));
        $fromName = (string) (config('mail.from.name') ?: env('MAIL_FROM_NAME', 'PWD Portal'));

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromAddress, $fromName);

        return $mail;
    }

    private function normalizeRecipients(string|array $to): array
    {
        $items = is_array($to) ? $to : explode(',', (string) $to);
        $normalized = [];

        foreach ($items as $entry) {
            $email = trim((string) $entry);
            if ($email !== '') {
                $normalized[] = $email;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function addRecipients(PHPMailer $mail, string $method, string|array|null $value): void
    {
        if ($value === null) {
            return;
        }

        foreach ($this->normalizeRecipients($value) as $email) {
            $mail->{$method}($email);
        }
    }

    public function send(array $payload): array
    {
        $to = $this->normalizeRecipients($payload['to'] ?? []);
        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = (string) ($payload['body'] ?? '');

        if (empty($to) || $subject === '' || $body === '') {
            return [
                'ok' => false,
                'message' => 'Missing email recipient, subject, or body.',
                'error' => 'invalid_payload',
            ];
        }

        $isHtml = (bool) ($payload['isHtml'] ?? false);
        $altBody = isset($payload['altBody']) ? (string) $payload['altBody'] : '';

        try {
            $mail = $this->makeMailer();

            foreach ($to as $address) {
                $mail->addAddress($address);
            }

            $this->addRecipients($mail, 'addCC', $payload['cc'] ?? null);
            $this->addRecipients($mail, 'addBCC', $payload['bcc'] ?? null);

            if (!empty($payload['replyTo'])) {
                $replyTo = trim((string) $payload['replyTo']);
                if ($replyTo !== '') {
                    $mail->addReplyTo($replyTo);
                }
            }

            $attachments = $payload['attachments'] ?? [];
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_string($attachment) && is_file($attachment)) {
                        $mail->addAttachment($attachment);
                        continue;
                    }

                    if (is_array($attachment)) {
                        $path = (string) ($attachment['path'] ?? '');
                        $name = (string) ($attachment['name'] ?? '');
                        if ($path !== '' && is_file($path)) {
                            $mail->addAttachment($path, $name ?: '');
                        }
                    }
                }
            }

            $embeddedImages = $payload['embeddedImages'] ?? [];
            if (is_array($embeddedImages)) {
                foreach ($embeddedImages as $image) {
                    if (!is_array($image)) {
                        continue;
                    }

                    $path = (string) ($image['path'] ?? '');
                    $cid = (string) ($image['cid'] ?? '');
                    $name = (string) ($image['name'] ?? '');
                    if ($path !== '' && $cid !== '' && is_file($path)) {
                        $mail->addEmbeddedImage($path, $cid, $name ?: basename($path));
                    }
                }
            }

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;
            if ($altBody !== '') {
                $mail->AltBody = $altBody;
            }

            $mail->send();

            return [
                'ok' => true,
                'message' => 'Email sent successfully.',
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('PHPMailer send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Failed to send email.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendPlainText(string $to, string $subject, string $body): bool
    {
        $result = $this->send([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'isHtml' => false,
        ]);

        return (bool) ($result['ok'] ?? false);
    }

    public function sendOtpEmail(string $to, string $otp, string $recipientLabel = 'Applicant'): bool
    {
        $subject = 'PWD Portal OTP Verification';
        $plainText = "Hi {$recipientLabel}, your PWD Portal verification code is {$otp}. It expires in 10 minutes.";

        $logoPath = dirname(base_path())
            . DIRECTORY_SEPARATOR . 'PWD_frontend'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'whitelogo.png';

        $logoCid = 'pwd_logo_white';
        $hasLogo = is_file($logoPath);

        $body = '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#111;">'
            . '<div style="margin-bottom:12px;">'
            . ($hasLogo ? '<img src="cid:' . $logoCid . '" alt="PWD Portal" style="max-width:170px;height:auto;" />' : '')
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

        $result = $this->send([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'altBody' => $plainText,
            'isHtml' => true,
            'embeddedImages' => $hasLogo
                ? [[
                    'path' => $logoPath,
                    'cid' => $logoCid,
                    'name' => 'whitelogo.png',
                ]]
                : [],
        ]);

        return (bool) ($result['ok'] ?? false);
    }

    public function sendJobApprovalEmail(
        string $to,
        string $jobTitle,
        string $decision,
        string $reviewerName = 'Finance Team',
        string $note = ''
    ): bool {
        $normalizedDecision = strtolower(trim($decision)) === 'approved' ? 'approved' : 'rejected';
        $statusLabel = strtoupper($normalizedDecision);
        $subject = "Job Finance Review: {$jobTitle}";

        $plainText = "Your job posting '{$jobTitle}' was {$statusLabel} by {$reviewerName}.";
        if ($note !== '') {
            $plainText .= "\n\nFinance note: {$note}";
        }

        $body = '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#111;">'
            . '<h2 style="margin:0 0 10px 0;color:#0f172a;">Job Finance Review</h2>'
            . '<p style="margin:0 0 10px 0;">Job: <strong>' . htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:0 0 10px 0;">Decision: <strong>' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:0 0 10px 0;">Reviewed by: <strong>' . htmlspecialchars($reviewerName, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . ($note !== ''
                ? '<p style="margin:0 0 10px 0;">Finance note: ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</p>'
                : '')
            . '</div>';

        $result = $this->send([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'altBody' => $plainText,
            'isHtml' => true,
        ]);

        return (bool) ($result['ok'] ?? false);
    }
}
