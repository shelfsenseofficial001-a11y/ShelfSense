-- Adds a per-user toggle for the Store Manager dashboard's onboarding
-- tour (spotlight walkthrough). Defaults to on so new/existing users see
-- it once until they turn it off from Profile or dismiss it permanently
-- from within the tour itself.
ALTER TABLE users
    ADD COLUMN show_dashboard_tour TINYINT(1) NOT NULL DEFAULT 1;
