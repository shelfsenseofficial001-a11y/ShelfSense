-- Holidays reference table for Payday adjustment logic (Payroll): lets HR
-- flag a proposed payday that falls on a weekend or a known holiday.
--
-- Seeded rows are limited to dates that are either fixed by statute (RA 9492
-- regular holidays) or exactly computable (Holy Week via the Gregorian
-- Easter algorithm; National Heroes Day = last Monday of August). Movable,
-- proclamation-only holidays (e.g. Chinese New Year, which follows the
-- lunar calendar) are deliberately NOT seeded -- HR should add those via the
-- Manage Holidays screen once the annual Presidential Proclamation is out.
-- Seeded "special_non_working" dates are HR's to confirm/adjust each year
-- too, since their observance is re-declared annually even though the
-- calendar date itself doesn't move.

CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('regular','special_non_working') NOT NULL DEFAULT 'special_non_working',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `holiday_date` (`holiday_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `holidays` (`holiday_date`, `name`, `type`) VALUES
-- 2026
('2026-01-01', 'New Year''s Day', 'regular'),
('2026-02-25', 'EDSA People Power Anniversary', 'special_non_working'),
('2026-04-01', 'Maundy Thursday', 'regular'),
('2026-04-02', 'Good Friday', 'regular'),
('2026-04-03', 'Black Saturday', 'special_non_working'),
('2026-04-09', 'Araw ng Kagitingan', 'regular'),
('2026-05-01', 'Labor Day', 'regular'),
('2026-06-12', 'Independence Day', 'regular'),
('2026-08-21', 'Ninoy Aquino Day', 'special_non_working'),
('2026-08-31', 'National Heroes Day', 'regular'),
('2026-11-01', 'All Saints'' Day', 'special_non_working'),
('2026-11-30', 'Bonifacio Day', 'regular'),
('2026-12-08', 'Feast of the Immaculate Conception', 'special_non_working'),
('2026-12-24', 'Christmas Eve', 'special_non_working'),
('2026-12-25', 'Christmas Day', 'regular'),
('2026-12-30', 'Rizal Day', 'regular'),
('2026-12-31', 'Last Day of the Year', 'special_non_working'),
-- 2027
('2027-01-01', 'New Year''s Day', 'regular'),
('2027-02-25', 'EDSA People Power Anniversary', 'special_non_working'),
('2027-03-24', 'Maundy Thursday', 'regular'),
('2027-03-25', 'Good Friday', 'regular'),
('2027-03-26', 'Black Saturday', 'special_non_working'),
('2027-04-09', 'Araw ng Kagitingan', 'regular'),
('2027-05-01', 'Labor Day', 'regular'),
('2027-06-12', 'Independence Day', 'regular'),
('2027-08-21', 'Ninoy Aquino Day', 'special_non_working'),
('2027-08-30', 'National Heroes Day', 'regular'),
('2027-11-01', 'All Saints'' Day', 'special_non_working'),
('2027-11-30', 'Bonifacio Day', 'regular'),
('2027-12-08', 'Feast of the Immaculate Conception', 'special_non_working'),
('2027-12-24', 'Christmas Eve', 'special_non_working'),
('2027-12-25', 'Christmas Day', 'regular'),
('2027-12-30', 'Rizal Day', 'regular'),
('2027-12-31', 'Last Day of the Year', 'special_non_working');
