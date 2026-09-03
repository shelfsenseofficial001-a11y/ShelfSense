-- Adds birthdate and a Philippines-only address to the public career
-- application form. This app serves Philippine hiring only, so there is no
-- country column for the applicant to edit -- `country` is added as a fixed
-- non-editable default ('Philippines') purely for reporting consistency.
--
-- Province / City-Municipality / Barangay are validated against the live
-- PSGC (Philippine Standard Geographic Code) hierarchy at submission time
-- (see app/services/PsgcClient.php), so both the human-readable name and its
-- PSGC code are stored -- the code is the authoritative value (names like
-- "San Isidro" repeat across many different cities/provinces), the name is
-- kept alongside it so admin views/exports don't need to re-resolve codes.
--
-- No local province/city/barangay reference tables are created: PSGC data
-- is fetched live from psgc.gitlab.io and cached on disk (see PsgcClient),
-- so nothing needs seeding here.
--
-- Postal code has no reliable Philippines-wide open dataset tying it to
-- barangay/city (confirmed by testing multiple candidate sources), so it is
-- a plain applicant-entered value, format-validated only (4 digits, stored
-- as a string to preserve any leading zero) -- not cross-checked against
-- the selected location.
--
-- All new columns are nullable (except `country`, which is safe to default)
-- so existing applicant rows are left completely untouched.

ALTER TABLE `applicants`
    ADD COLUMN `birthdate` DATE NULL AFTER `phone`,
    ADD COLUMN `province` VARCHAR(100) NULL AFTER `birthdate`,
    ADD COLUMN `province_code` VARCHAR(20) NULL AFTER `province`,
    ADD COLUMN `city_municipality` VARCHAR(150) NULL AFTER `province_code`,
    ADD COLUMN `city_municipality_code` VARCHAR(20) NULL AFTER `city_municipality`,
    ADD COLUMN `barangay` VARCHAR(150) NULL AFTER `city_municipality_code`,
    ADD COLUMN `barangay_code` VARCHAR(20) NULL AFTER `barangay`,
    ADD COLUMN `house_block_lot` VARCHAR(255) NULL AFTER `barangay_code`,
    ADD COLUMN `street` VARCHAR(255) NULL AFTER `house_block_lot`,
    ADD COLUMN `subdivision` VARCHAR(255) NULL AFTER `street`,
    ADD COLUMN `postal_code` VARCHAR(4) NULL AFTER `subdivision`,
    ADD COLUMN `country` VARCHAR(50) NOT NULL DEFAULT 'Philippines' AFTER `postal_code`,
    ADD INDEX `idx_province_code` (`province_code`),
    ADD INDEX `idx_city_municipality_code` (`city_municipality_code`);
