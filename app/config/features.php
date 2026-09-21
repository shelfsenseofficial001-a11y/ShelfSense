<?php
// app/config/features.php
// Small on/off switches for features that are still being rolled out.

return [
    // Temporarily off: cashiers (role=employee) skip Face ID enrollment
    // at first login and skip face verification when clocking in at a
    // POS register, falling back to password-only. Re-enable once the
    // Face ID flow is ready for cashier rollout. Trainees are unaffected.
    'face_id_required_for_cashier' => false,
];

// Test Mode lives in the system_settings DB table (App\Core\Settings),
// not here -- it's toggled live from the Owner Settings page instead of
// requiring a file edit. See App\Core\Settings::isTestMode().
