<?php
// app/models/User.php

namespace App\Models;

use App\Core\Database;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updatePassword($userId, $password)
    {
        // Use PASSWORD_DEFAULT (bcrypt)
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("UPDATE users SET password = ?, is_first_login = 1, updated_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$hashed, $userId]);
    }

    public function createPasswordReset($userId, $otp)
    {
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, otp, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
        ");
        return $stmt->execute([$userId, $otp]);
    }

    public function verifyOTP($otp, $userId = null)
    {
        $sql = "SELECT * FROM password_resets WHERE otp = ? AND used = 0 AND expires_at > NOW()";
        $params = [$otp];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function markOTPUsed($resetId)
    {
        $stmt = $this->db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        return $stmt->execute([$resetId]);
    }

    public function generateOTP()
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function clearExpiredOTPs()
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
        return $stmt->execute();
    }
}