-- Lets the applicant/trainee accept or decline straight from the email,
-- without logging in. A random, unguessable token per contract (not the
-- sequential contract id) is what the public link carries.
ALTER TABLE contracts ADD COLUMN response_token VARCHAR(64) NULL AFTER decision_deadline;
ALTER TABLE contracts ADD UNIQUE KEY response_token (response_token);
