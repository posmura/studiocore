
-- TEST NA DUPLICITU --
SELECT user_id, diary_id, aktivita_id, COUNT(*) cnt
FROM blog_registration
WHERE deleted = 0
GROUP BY user_id, diary_id, aktivita_id
HAVING cnt > 1;

-- NEPOKRAČOVAT - POKUD JE NALEZENA DUPLICITA --


ALTER TABLE blog_registration
ADD COLUMN aktivita_active INT
  GENERATED ALWAYS AS (IF(deleted = 0, aktivita_id, NULL)) STORED;

ALTER TABLE blog_registration
ADD UNIQUE KEY uniq_active (user_id, diary_id, aktivita_active);