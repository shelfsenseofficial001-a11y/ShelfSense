-- Supports a real Trainee Contract (offered before trainer assignment) that
-- coexists with a later Hiring Contract for the same person. The previous
-- UNIQUE(user_id) only ever allowed one contracts row per user in total --
-- fine while only 'hired' contracts were ever created, but blocks a 'hired'
-- contract from being created later for someone who already has a 'trainee'
-- contract. Replaced with UNIQUE(user_id, contract_type): still prevents two
-- trainee (or two hired) contracts for the same person, but allows one of
-- each type.
ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_2;
ALTER TABLE contracts DROP INDEX user_id;
ALTER TABLE contracts ADD UNIQUE KEY user_id_contract_type (user_id, contract_type);
ALTER TABLE contracts ADD CONSTRAINT contracts_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (user_id);

-- Trainee contracts record a custom daily shift (a 5-hour window starting at
-- whatever time was discussed), unlike the fixed opening/midshift/closing
-- enum used for Hiring Contracts -- so `shift` is now optional and these two
-- columns hold the trainee-specific start/end instead.
ALTER TABLE contracts MODIFY COLUMN shift enum('opening','closing','midshift') NULL;
ALTER TABLE contracts ADD COLUMN schedule_start TIME NULL AFTER shift;
ALTER TABLE contracts ADD COLUMN schedule_end TIME NULL AFTER schedule_start;
