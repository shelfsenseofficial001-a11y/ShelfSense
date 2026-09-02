<?php
namespace App\Core;

use App\Core\Database;

class Auth
{
    /**
     * $identifier may be either the account's email or its employee_number --
     * the Staff Portal landing-page gate already confirmed a real employee
     * number exists before sending someone here, so the login form itself
     * accepts whichever one the person actually has memorized.
     */
    public static function login($identifier, $password)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR employee_number = ?) AND is_active = 1");
        $stmt->execute([$identifier, $identifier]);
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

        // A successful login re-locks the Staff Portal gate for this browser
        // session -- the next visitor (or this one, after logging out) must
        // verify a real employee number again before the login page opens.
        unset($_SESSION['portal_gate_passed'], $_SESSION['portal_gate_employee_number']);

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

    private static $profilePicSynced = false;

    public static function check()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        self::syncProfilePic();
        return true;
    }

    // Approving someone's pending profile picture (app/handlers/hr/approve_avatar.php)
    // only touches the DB -- it can't reach into the approved user's own
    // browser session (that's a different, unrelated PHP session), so
    // without this their sidebar avatar would stay stuck on the old photo
    // until they happened to log out and back in. Runs at most once per
    // request (static flag), so the extra query is cheap even though
    // check() itself gets called many times (once per API call a page fires).
    private static function syncProfilePic()
    {
        if (self::$profilePicSynced) {
            return;
        }
        self::$profilePicSynced = true;

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if ($row && $row['profile_pic'] !== ($_SESSION['profile_pic'] ?? null)) {
            $_SESSION['profile_pic'] = $row['profile_pic'];
        }
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

    // ============================================
    // POS SESSION (register/terminal login -- separate from a staff account
    // login above: a POS session has no user_id of its own, only a
    // register_id, plus whichever cashier is currently picked for
    // attribution on this terminal)
    // ============================================

    /**
     * Verifies a POS ID + 4-digit PIN against the registers table and, on
     * success, opens a POS session. Returns the register row, or false.
     */
    public static function posLogin($posId, $pin)
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM registers WHERE pos_id = ?");
        $stmt->execute([$posId]);
        $register = $stmt->fetch();

        if (!$register || empty($register['pin_hash']) || !password_verify($pin, $register['pin_hash'])) {
            return false;
        }

        $_SESSION['pos_register_id'] = (int)$register['id'];
        $_SESSION['pos_register_name'] = $register['name'];
        $_SESSION['pos_id'] = $register['pos_id'];
        unset($_SESSION['pos_cashier_id'], $_SESSION['pos_cashier_name']);

        session_regenerate_id(true);

        return $register;
    }

    public static function posCheck()
    {
        return isset($_SESSION['pos_register_id']);
    }

    public static function posRegisterId()
    {
        return $_SESSION['pos_register_id'] ?? null;
    }

    public static function posRegisterName()
    {
        return $_SESSION['pos_register_name'] ?? null;
    }

    /**
     * Attributes subsequent orders on this POS session to a specific staff
     * member (picked after PIN login, purely for accountability -- it does
     * not authenticate them and grants no staff-portal access).
     */
    public static function posSetCashier($userId, $fullName)
    {
        $_SESSION['pos_cashier_id'] = (int)$userId;
        $_SESSION['pos_cashier_name'] = $fullName;
    }

    public static function posCashierId()
    {
        return $_SESSION['pos_cashier_id'] ?? null;
    }

    public static function posCashierName()
    {
        return $_SESSION['pos_cashier_name'] ?? null;
    }

    public static function posLogout()
    {
        unset(
            $_SESSION['pos_register_id'],
            $_SESSION['pos_register_name'],
            $_SESSION['pos_id'],
            $_SESSION['pos_cashier_id'],
            $_SESSION['pos_cashier_name']
        );
    }
}