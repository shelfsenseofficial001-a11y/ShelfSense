<?php
// app/core/SmsService.php
//
// Placeholder SMS abstraction. No SMS provider is configured or wired into
// this project -- this class exists so future code (and this task's
// recruitment notifications) can call a single interface, without adding a
// paid provider or fabricating credentials.
//
// To enable real SMS sending, implement send() using your provider's SDK/API
// (e.g. Twilio, Semaphore, Vonage) and add its credentials to .env, mirroring
// the MAIL_* pattern in app/config/mail.php. Until then, every call is a
// documented no-op that never claims a message was sent.

namespace App\Core;

require_once __DIR__ . '/Database.php';

class SmsService
{
    private $enabled;

    public function __construct()
    {
        // No provider configured. Flip this on only once real credentials
        // (e.g. SMS_PROVIDER, SMS_API_KEY, SMS_SENDER_ID) exist in .env.
        $this->enabled = false;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function send($toPhoneNumber, $message)
    {
        $this->logAttempt($toPhoneNumber, $message, $this->enabled ? 'sent' : 'not_configured');

        if (!$this->enabled) {
            error_log("📱 [SMS NOT CONFIGURED] Would send to {$toPhoneNumber}: {$message}");
            return ['success' => false, 'message' => 'SMS provider is not configured for this environment.'];
        }

        // Real provider integration would go here.
        return ['success' => false, 'message' => 'SMS provider not implemented.'];
    }

    private function logAttempt($to, $message, $status)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO email_logs (recipient_email, subject, body, status) VALUES (?, ?, ?, ?)");
            // Reuses the existing email_logs table as a generic outbound-message
            // log (recipient_email doubles as "recipient") rather than adding a
            // near-duplicate sms_logs table for a provider that doesn't exist yet.
            $stmt->execute(['sms:' . $to, 'SMS notification', $message, $status === 'sent' ? 'sent' : 'failed']);
        } catch (\Exception $e) {
            error_log('SmsService log failed: ' . $e->getMessage());
        }
    }
}
