-- Rest-day changes are always a paired swap (one day becomes rest, an
-- existing rest day in the same cutoff becomes the work day instead, in a
-- single action with one reason) rather than two independent edits.
-- swap_with_day records the other half of the pair so a revert removes
-- both sides together, not just one.
ALTER TABLE schedule_overrides
    ADD COLUMN swap_with_day ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL AFTER day_of_week;
