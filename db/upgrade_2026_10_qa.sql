-- ============================================================================
--  BloodLink — upgrade for an EXISTING database (October 2026 QA fixes).
--  Fresh installs don't need this: db/schema.sql already includes it.
--
--  Member IDs become unique (login accepts email OR Member ID, so two members
--  sharing an ID could lock each other out). Any duplicates already in the
--  table are renamed first by appending the member's numeric id.
--
--  Run once:  mysql -u root -p donor_app < db/upgrade_2026_10_qa.sql
-- ============================================================================

UPDATE members m
JOIN (
    SELECT member_code, MIN(member_id) AS keep_id
    FROM members GROUP BY member_code HAVING COUNT(*) > 1
) d ON d.member_code = m.member_code AND m.member_id <> d.keep_id
SET m.member_code = CONCAT(m.member_code, '-', m.member_id);

UPDATE members SET member_code = UPPER(member_code), email = LOWER(email);

ALTER TABLE members
    DROP INDEX idx_members_member_code,
    ADD UNIQUE KEY uq_members_member_code (member_code);

-- Emergency alerts used to be HTML-escaped before saving AND again on display,
-- so "&" showed as "&amp;". They are now stored as plain text; un-escape old rows.
UPDATE notices SET
    title   = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title,   '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
    message = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(message, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&');
