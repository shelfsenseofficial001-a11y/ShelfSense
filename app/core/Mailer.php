<?php
// app/core/Mailer.php

namespace App\Core;

require_once __DIR__ . '/Database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;
    private $config;
    private $enabled;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
        $this->enabled = !empty($this->config['username']) && !empty($this->config['password']);
        
        if ($this->enabled) {
            $this->mail = new PHPMailer(true);
            
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = $this->config['encryption'];
            $this->mail->Port = $this->config['port'];
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Timeout = $this->config['timeout'] ?? 30;
        }
    }

    public function send($to, $subject, $body, $attachments = [])
    {
        // If not enabled, just log
        if (!$this->enabled) {
            error_log("📧 [MAIL DISABLED] To: $to, Subject: $subject");
            $this->logEmail($to, $subject, 'failed');
            return ['success' => true, 'message' => 'Email disabled (testing)'];
        }

        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);

            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $this->mail->addAttachment($attachment);
                }
            }

            $this->mail->send();

            error_log("📧 [MAIL SENT] To: $to, Subject: $subject");
            $this->logEmail($to, $subject, 'sent');
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            error_log('📧 [MAIL ERROR] ' . $e->getMessage());
            $this->logEmail($to, $subject, 'failed');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Truthful record of every email attempt (sent or failed) in the existing
     * email_logs table -- never includes SMTP credentials or secrets.
     */
    private function logEmail($to, $subject, $status)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO email_logs (recipient_email, subject, status) VALUES (?, ?, ?)");
            $stmt->execute([$to, $subject, $status]);
        } catch (\Exception $e) {
            error_log('email_logs insert failed: ' . $e->getMessage());
        }
    }

    // ============================================
    // TEMPLATES
    // ============================================

    public function sendOrderConfirmation($order, $user)
    {
        $subject = "Order #{$order['order_number']} Confirmation - ShelfSense POS";
        $body = $this->getOrderEmailTemplate($order, $user);
        return $this->send($user['email'], $subject, $body);
    }

    public function sendLeaveApproval($leave, $user)
    {
        $subject = "Leave Request Approved - ShelfSense";
        $body = $this->getLeaveEmailTemplate($leave, $user, 'approved');
        return $this->send($user['email'], $subject, $body);
    }

    public function sendLeaveRejection($leave, $user, $reason = null)
    {
        $subject = "Leave Request Rejected - ShelfSense";
        $body = $this->getLeaveEmailTemplate($leave, $user, 'rejected', $reason);
        return $this->send($user['email'], $subject, $body);
    }

    public function sendApplicantStatusUpdate($applicant, $status, $message = null)
    {
        $statusLabels = [
            'application_received' => 'Application Received',
            'initial_scheduled' => 'Initial Interview Scheduled',
            'final_scheduled' => 'Final Interview Scheduled',
            'contract_offered' => 'Contract Offered',
            'hired' => 'Congratulations! You\'re Hired!',
            'initial_failed' => 'Application Update',
            'final_failed' => 'Application Update',
        ];
        
        $subject = $statusLabels[$status] ?? 'Application Status Update - ShelfSense';
        $body = $this->getApplicantEmailTemplate($applicant, $status, $message);
        return $this->send($applicant['email'], $subject, $body);
    }

    public function sendPasswordResetOTP($user, $otp)
    {
        $subject = "Password Reset OTP - ShelfSense";
        $body = $this->getPasswordResetOTPTemplate($user, $otp);
        return $this->send($user['email'], $subject, $body);
    }

    /** Sent to the applicant once their Trainee Contract terms are set and a trainer is assigned. */
    public function sendTraineeContractNotice($applicant, $terms)
    {
        $subject = "Your Trainee Contract - ShelfSense";
        $body = $this->getTraineeContractTemplate($applicant, $terms);
        return $this->send($applicant['email'], $subject, $body);
    }

    /** Sent to the trainer when they're assigned a new trainee. */
    public function sendTrainerAssignmentNotice($trainer, $traineeName, $targetRole)
    {
        $subject = "New Trainee Assigned - ShelfSense";
        $body = $this->getTrainerAssignmentTemplate($trainer, $traineeName, $targetRole);
        return $this->send($trainer['email'], $subject, $body);
    }

    // ============================================
    // EMAIL TEMPLATES
    // ============================================

    private function getOrderEmailTemplate($order, $user)
    {
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$item['quantity']}x {$item['name']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;'>₱" . number_format($item['price'], 2) . "</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;'>₱" . number_format($item['subtotal'], 2) . "</td>
            </tr>";
        }

        return $this->getLayout("
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>🧾 Order Confirmation</h2>
                <p style='margin:4px 0 0;color:#4b5563;'>Thank you for your purchase!</p>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;'>
                <p><strong>Order #:</strong> {$order['order_number']}</p>
                <p><strong>Date:</strong> " . date('F j, Y h:i A', strtotime($order['created_at'])) . "</p>
                <p><strong>Cashier:</strong> {$user['first_name']} {$user['last_name']}</p>
                <p><strong>Payment Method:</strong> " . strtoupper($order['payment_method']) . "</p>
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <table style='width:100%;border-collapse:collapse;'>
                    <thead>
                        <tr>
                            <th style='padding:8px 12px;text-align:left;background:#f3f4f6;'>Item</th>
                            <th style='padding:8px 12px;text-align:right;background:#f3f4f6;'>Price</th>
                            <th style='padding:8px 12px;text-align:right;background:#f3f4f6;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding:12px;text-align:right;font-weight:bold;'>Total</td>
                            <td style='padding:12px;text-align:right;font-weight:bold;color:#059669;'>₱" . number_format($order['total'], 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
                " . ($order['amount_paid'] > 0 ? "
                    <p><strong>Amount Paid:</strong> ₱" . number_format($order['amount_paid'], 2) . "</p>
                    <p><strong>Change:</strong> ₱" . number_format($order['change_amount'], 2) . "</p>
                " : "") . "
                " . ($order['notes'] ? "<p><strong>Notes:</strong> {$order['notes']}</p>" : "") . "
            </div>
        ", "ShelfSense POS — Order Confirmation");
    }

    private function getLeaveEmailTemplate($leave, $user, $status, $reason = null)
    {
        $statusText = $status === 'approved' ? '✅ Approved' : '❌ Rejected';
        $statusColor = $status === 'approved' ? '#059669' : '#dc2626';
        $leaveTypes = ['sick' => 'Sick Leave', 'vacation' => 'Vacation Leave', 'emergency' => 'Emergency Leave', 'other' => 'Other Leave'];
        $leaveTypeLabel = $leaveTypes[$leave['leave_type']] ?? $leave['leave_type'];

        return $this->getLayout("
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>📋 Leave Request {$statusText}</h2>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;'>
                <p>Dear <strong>{$user['first_name']}</strong>,</p>
                <p>Your leave request has been <span style='color:{$statusColor};font-weight:bold;'>{$statusText}</span>.</p>
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <p><strong>Leave Type:</strong> {$leaveTypeLabel}</p>
                <p><strong>From:</strong> " . date('F j, Y', strtotime($leave['start_date'])) . "</p>
                <p><strong>To:</strong> " . date('F j, Y', strtotime($leave['end_date'])) . "</p>
                <p><strong>Duration:</strong> " . (isset($leave['duration']) ? $leave['duration'] : 'N/A') . " day(s)</p>
                " . ($reason ? "<p><strong>Notes:</strong> {$reason}</p>" : "") . "
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <p style='font-size:12px;color:#6b7280;'>If you have any questions, please contact your HR department.</p>
            </div>
        ", "Leave Request {$statusText}");
    }

    private function getApplicantEmailTemplate($applicant, $status, $message = null)
    {
        $statusMessages = [
            'initial_scheduled' => "You have been scheduled for an initial interview. Please check your email for the Google Meet link.",
            'final_scheduled' => "You have been scheduled for a final interview. Please check your email for the Google Meet link.",
            'contract_offered' => "Congratulations! You have been offered a contract. Please check the portal for details.",
            'hired' => "Congratulations! You have been hired. Welcome to the team!",
            'initial_failed' => "Thank you for your interest. Unfortunately, you did not pass the initial interview.",
            'final_failed' => "Thank you for your interest. Unfortunately, you did not pass the final interview.",
        ];
        
        $statusLabels = [
            'initial_scheduled' => 'Initial Interview Scheduled',
            'final_scheduled' => 'Final Interview Scheduled',
            'contract_offered' => 'Contract Offered',
            'hired' => 'You\'re Hired!',
            'initial_failed' => 'Application Update',
            'final_failed' => 'Application Update',
        ];

        $bodyText = $message ?? ($statusMessages[$status] ?? 'Your application status has been updated.');

        return $this->getLayout("
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>📋 Application Update</h2>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;'>
                <p>Dear <strong>{$applicant['first_name']}</strong>,</p>
                <p><strong>Status:</strong> " . ($statusLabels[$status] ?? $status) . "</p>
                <p>{$bodyText}</p>
                " . ($status === 'hired' ? "
                    <div style='background:#d1fae5;padding:16px;border-radius:8px;margin:16px 0;text-align:center;'>
                        <p style='margin:0;color:#065f46;font-weight:bold;'>🎉 Welcome to the team!</p>
                    </div>
                " : "") . "
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <p style='font-size:12px;color:#6b7280;'>Please log in to the portal for more details.</p>
            </div>
        ", "Application Update - ShelfSense");
    }

    private function getPasswordResetOTPTemplate($user, $otp)
    {
        return $this->getLayout("
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>🔐 Password Reset OTP</h2>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;'>
                <p>Dear <strong>{$user['first_name']}</strong>,</p>
                <p>We received a request to reset your password. Use the One-Time Password (OTP) below to proceed:</p>
                <div style='text-align:center;margin:24px 0;padding:20px;background:#f3f4f6;border-radius:8px;'>
                    <span style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#1a1a1a;'>{$otp}</span>
                </div>
                <p style='font-size:12px;color:#6b7280;'>This OTP will expire in 15 minutes. If you did not request this, please ignore this email.</p>
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <p style='font-size:12px;color:#6b7280;'>Please enter this OTP on the password reset page to set a new password.</p>
            </div>
        ", "Password Reset OTP - ShelfSense");
    }

    private function getTraineeContractTemplate($applicant, $terms)
    {
        return $this->getLayout("
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>📄 Your Trainee Contract</h2>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;'>
                <p>Dear <strong>{$applicant['first_name']}</strong>,</p>
                <p>Congratulations on passing your initial interview! Here are your Trainee Contract terms as discussed:</p>
                <ul>
                    <li><strong>Trainer:</strong> {$terms['trainer_name']}</li>
                    <li><strong>Salary Range:</strong> ₱" . number_format($terms['salary_min'], 2) . " – ₱" . number_format($terms['salary_max'], 2) . "</li>
                    <li><strong>Working Hours:</strong> {$terms['schedule_start']} – {$terms['schedule_end']} (5 hours/day)</li>
                    <li><strong>Rest Days:</strong> {$terms['rest_days']}</li>
                    <li><strong>Training Period:</strong> {$terms['start_date']} to {$terms['end_date']} (3 months)</li>
                </ul>
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <p style='font-size:12px;color:#6b7280;'>Please log in to the portal for more details.</p>
            </div>
        ", "Your Trainee Contract - ShelfSense");
    }

    private function getTrainerAssignmentTemplate($trainer, $traineeName, $targetRole)
    {
        return $this->getLayout("
            <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
                <h2 style='margin:0;color:#1a1a1a;'>🎓 New Trainee Assigned</h2>
            </div>
            <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;'>
                <p>Dear <strong>{$trainer['first_name']}</strong>,</p>
                <p>You have been assigned as the trainer for <strong>{$traineeName}</strong> (target role: {$targetRole}).</p>
                <p>Please prepare to submit weekly training reports for this trainee over the next 3 months.</p>
                <hr style='border:none;border-top:1px solid #e5e7eb;'>
                <p style='font-size:12px;color:#6b7280;'>Please log in to the portal for more details.</p>
            </div>
        ", "New Trainee Assigned - ShelfSense");
    }

    private function getLayout($content, $subject)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 20px; background: #f9f9f9; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #6b7280; background: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class='container'>
                {$content}
                <div class='footer'>
                    <p>ShelfSense — Smart Retail Operations</p>
                    <p>© " . date('Y') . " ShelfSense Inc. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}