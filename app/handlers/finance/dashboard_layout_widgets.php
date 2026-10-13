<?php
// app/handlers/finance/dashboard_layout_widgets.php
// Shared between get_dashboard_layout.php and save_dashboard_layout.php:
// which Finance dashboard the current user owns, and the exact widget ids
// that dashboard is allowed to contain. Kept in one place so the save
// whitelist can never drift from the key the get reads back.

require_once __DIR__ . '/../../core/Auth.php';

use App\Core\Auth;

/**
 * The stored dashboard_key for whoever is logged in, or null if they are
 * not a Finance user at all. SuperAdmin can reach either dashboard, so it
 * falls back to the Head one (the page it is routed to by default).
 */
function fn_dashboard_key_for_current_user()
{
    if (Auth::isFinanceStaff()) {
        return 'finance_staff_dashboard';
    }
    if (Auth::isFinanceHead() || Auth::isSuperAdmin()) {
        return 'finance_head_dashboard';
    }
    return null;
}

/**
 * Each group is independently reorderable -- widgets never move between
 * groups. The stat cards stay their own group (different card size), and
 * the full-width panels share the "content" group.
 */
function fn_dashboard_widget_groups($dashboardKey)
{
    if ($dashboardKey === 'finance_staff_dashboard') {
        return [
            'stats' => ['stat_pending_req', 'stat_pending_pay', 'stat_budget_avail', 'stat_budget_exceeded'],
            'content' => [
                'table_to_review',
                'table_budget_exceeded',
                'table_my_payments',
                'panel_budget_status',
                'panel_recent_activity',
            ],
        ];
    }
    return [
        'stats' => ['stat_pending_pay', 'stat_approved', 'stat_rejected', 'stat_budget_used'],
        'content' => [
            'table_reqs_to_approve',
            'table_payments_to_approve',
            'panel_budget_overview',
            'panel_recent_activity',
        ],
    ];
}
