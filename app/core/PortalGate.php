<?php
namespace App\Core;

/**
 * The Staff Portal landing-page checkpoint: before the login page opens, a
 * visitor must confirm a real employee number exists (no password yet --
 * that still happens on the actual login page). Tracked per browser session,
 * with an escalating cooldown against enumerating valid employee numbers by
 * brute force: 5 attempts per cycle, 3 min lockout, doubling each cycle the
 * lockout is hit again (3 -> 6 -> 12 ...), reset back to the 3 min baseline
 * the moment a check actually succeeds.
 */
class PortalGate
{
    const MAX_ATTEMPTS = 5;
    const BASE_COOLDOWN_MINUTES = 3;

    public static function isLockedOut()
    {
        $until = $_SESSION['gate_lockout_until'] ?? null;
        return $until !== null && time() < $until;
    }

    public static function lockedOutSecondsRemaining()
    {
        $until = $_SESSION['gate_lockout_until'] ?? null;
        return $until !== null ? max(0, $until - time()) : 0;
    }

    public static function attemptsRemaining()
    {
        return max(0, self::MAX_ATTEMPTS - ($_SESSION['gate_attempts'] ?? 0));
    }

    /**
     * Records a failed check. Once MAX_ATTEMPTS is hit within a cycle, opens
     * a lockout window and starts the next cycle at double the last cooldown.
     */
    public static function recordFailure()
    {
        $_SESSION['gate_attempts'] = ($_SESSION['gate_attempts'] ?? 0) + 1;

        if ($_SESSION['gate_attempts'] >= self::MAX_ATTEMPTS) {
            $cycle = ($_SESSION['gate_lockout_cycle'] ?? 0) + 1;
            $_SESSION['gate_lockout_cycle'] = $cycle;
            $minutes = self::BASE_COOLDOWN_MINUTES * (2 ** ($cycle - 1));
            $_SESSION['gate_lockout_until'] = time() + ($minutes * 60);
            $_SESSION['gate_attempts'] = 0;
        }
    }

    /**
     * A successful check resets the whole rate-limit state back to baseline
     * (next lockout, if any, starts again at 3 minutes).
     */
    public static function recordSuccess()
    {
        unset($_SESSION['gate_attempts'], $_SESSION['gate_lockout_until'], $_SESSION['gate_lockout_cycle']);
    }

    public static function pass($employeeNumber)
    {
        $_SESSION['portal_gate_passed'] = true;
        $_SESSION['portal_gate_employee_number'] = $employeeNumber;
    }

    public static function hasPassed()
    {
        return !empty($_SESSION['portal_gate_passed']);
    }

    /**
     * Backing out of the Login page (the "Back" link) re-locks the gate for
     * this browser session -- otherwise the Login page would stay reachable
     * by typing its URL directly, without going through the ID check again.
     * The rate-limit state (attempts/lockout/cycle) is untouched.
     */
    public static function leave()
    {
        unset($_SESSION['portal_gate_passed'], $_SESSION['portal_gate_employee_number']);
    }

    public static function getPassedEmployeeNumber()
    {
        return $_SESSION['portal_gate_employee_number'] ?? null;
    }
}
