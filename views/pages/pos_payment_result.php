<?php
// views/pages/pos_payment_result.php
// Public, no-login landing page for the CUSTOMER'S OWN PHONE after they
// approve/decline a GCash or PayMaya payment on PayMongo's page -- this is
// where PayMongo's Source redirect (success and failed both point here,
// see paymongo_create_source.php) sends them.
//
// It deliberately does not attempt to confirm anything itself: the
// register is the one polling the Source's status and charging it (see
// pos.js), so by the time a customer's phone gets redirected here, that
// process is already running independently on the register. This page
// exists purely so the phone lands somewhere that actually loads instead
// of being bounced through the staff-only POS session guards into the
// public landing page.

$title = 'Payment Submitted - ShelfSense';

$content = '
<div class="text-center py-3">
    <div class="mb-3" style="font-size: 3rem; color: var(--brand-yellow);">
        <i class="bi bi-check-circle-fill"></i>
    </div>
    <h4 class="mb-2">Thanks!</h4>
    <p class="text-muted mb-0">Your payment has been submitted. Please hand the phone back to the cashier to finish the transaction.</p>
</div>
';

require_once __DIR__ . '/../layouts/auth.php';
