<?php
namespace App\Core;

use App\Core\Database;

class Auth
{
    public static function login($email, $password)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['permission_level'] = (int)$user['permission_level'];
        $_SESSION['employee_number'] = $user['employee_number'];
        $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['is_first_login'] = $user['is_first_login'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        $_SESSION['email'] = $user['email'];

        session_regenerate_id(true);

        return [
            'user_id' => $user['user_id'],
            'role' => $user['role'],
            'permission_level' => (int)$user['permission_level'],
            'employee_number' => $user['employee_number'],
            'fullname' => $user['first_name'] . ' ' . $user['last_name'],
            'is_first_login' => $user['is_first_login']
        ];
    }

    public static function logout()
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function user()
    {
        if (!self::check()) {
            return null;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['permission_level'] = (int)$user['permission_level'];
        }
        return $user;
    }

    public static function role()
    {
        return $_SESSION['role'] ?? null;
    }

    public static function userId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function permissionLevel()
    {
        return $_SESSION['permission_level'] ?? 1;
    }

    // ============================================
    // ROLE-BASED CHECKS
    // ============================================

    public static function isHR()
    {
        $role = self::role();
        return in_array($role, ['hr_head', 'hr_staff']);
    }

    public static function isHRHead()
    {
        return self::role() === 'hr_head';
    }

    public static function isEmployee()
    {
        return self::role() === 'employee';
    }

    public static function isOwner()
    {
        return self::role() === 'owner';
    }

    public static function isTrainee()
    {
        return self::role() === 'trainee';
    }

    public static function isStoreManager()
    {
        return self::role() === 'store_manager';
    }

    public static function isSupplier()
    {
        return self::role() === 'supplier';
    }

    // ✅ NEW: Finance role checks
    public static function isFinanceStaff()
    {
        return self::role() === 'finance_staff';
    }

    public static function isFinanceHead()
    {
        return self::role() === 'finance_head';
    }

    public static function isFirstLogin()
    {
        return isset($_SESSION['is_first_login']) && $_SESSION['is_first_login'] == 1;
    }

    // ============================================
    // PERMISSION-BASED CHECKS
    // ============================================

    public static function isSuperAdmin()
    {
        return self::permissionLevel() >= 5;
    }

    public static function hasPermission($requiredLevel)
    {
        return self::isSuperAdmin() || self::permissionLevel() >= $requiredLevel;
    }

    public static function canApprove()
    {
        return self::hasPermission(4);
    }

    public static function canEdit()
    {
        return self::hasPermission(1);
    }

    public static function canView()
    {
        return self::hasPermission(0);
    }

    // ============================================
    // MODULE ACCESS
    // ============================================

    public static function canAccessModule($moduleRole)
    {
        if (self::isSuperAdmin()) return true;
        if (self::isOwner()) return true;
        if (self::isHR() && in_array($moduleRole, ['hr_head', 'hr_staff'])) return true;
        if (self::isEmployee() && $moduleRole === 'employee') return true;
        if (in_array(self::role(), ['finance_head', 'finance_staff']) && in_array($moduleRole, ['finance_head', 'finance_staff'])) return true;
        if (self::isStoreManager() && $moduleRole === 'store_manager') return true;
        if (self::isSupplier() && $moduleRole === 'supplier') return true;
        if (self::isTrainee() && self::getNormalizedTargetRole() === $moduleRole) return true;
        return false;
    }

    // ============================================
    // TRAINEE SUPPORT
    // ============================================

    public static function getTraineeTargetRole()
    {
        if (!self::isTrainee()) {
            return null;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT target_role FROM trainees WHERE user_id = ?");
        $stmt->execute([self::userId()]);
        $row = $stmt->fetch();
        return $row ? trim($row['target_role']) : null;
    }

    public static function getNormalizedTargetRole()
    {
        $target = self::getTraineeTargetRole();
        if (!$target) {
            return null;
        }

        $roleMap = [
            'head hr' => 'hr_head',
            'hr head' => 'hr_head',
            'hr' => 'hr_head',
            'cashier' => 'employee',
            'employee' => 'employee',
            'finance head' => 'finance_head',
            'finance' => 'finance_head',
            'hr staff' => 'hr_staff',
            'finance staff' => 'finance_staff',
        ];

        $normalized = strtolower(trim($target));
        return $roleMap[$normalized] ?? $normalized;
    }

    public static function getModuleSidebar($moduleRole)
    {
        $sidebar = ['dashboard' => '?page=trainee_dashboard'];

        if ($moduleRole === 'hr_head' || $moduleRole === 'hr_staff') {
            $sidebar['hr_dashboard'] = '?page=hr_dashboard';
            $sidebar['hr_applicants'] = '?page=hr_applicants';
            $sidebar['hr_interviews'] = '?page=hr_interviews';
            $sidebar['hr_trainees'] = '?page=hr_trainees';
            $sidebar['hr_contracts'] = '?page=hr_contracts';
            $sidebar['hr_schedules'] = '?page=hr_schedules';
            $sidebar['hr_attendance'] = '?page=hr_attendance';
            if ($moduleRole === 'hr_head') {
                $sidebar['hr_attendance_review'] = '?page=hr_attendance_review';
            }
            $sidebar['hr_payroll'] = '?page=hr_payroll';
        } elseif ($moduleRole === 'employee') {
            $sidebar['pos_checkout'] = '?page=pos_checkout';
            $sidebar['pos_orders'] = '?page=pos_orders';
        } elseif ($moduleRole === 'finance_head') {
            $sidebar['finance_dashboard'] = '?page=finance_head_dashboard';
        } elseif ($moduleRole === 'finance_staff') {
            $sidebar['finance_dashboard'] = '?page=finance_staff_dashboard';
        }

        return $sidebar;
    }
}