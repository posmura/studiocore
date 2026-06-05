-- ============================================================
-- MIGRACE 20260605 – Ochrana kreditů a konzistence docházky
-- ============================================================
-- Autor  : posmura
-- Datum  : 2026-06-05
-- Větev  : develop
-- ============================================================
-- ZMĚNY:
--   1. Oprava dat        – kredity < -1 se nastaví na -1
--   2. CHECK constraint  – blog_credits.kredity >= -1
--   3. Trigger br_au     – ochrana mv_lekce_ucast.total >= 0
--   4. Trigger br_ad     – ochrana mv_lekce_ucast.total >= 0
-- ============================================================
-- POSTUP SPUŠTĚNÍ (vývojový / produkční server):
--   mysql -h <host> -u <user> -p<pass> <db> < 20260605.sql
-- ============================================================


-- ============================================================
-- KROK 0: PRE-CHECK (jen čtení, nic nemění)
-- ============================================================

SELECT 'PRE-CHECK: počet záznamů s kredity < -1' AS info,
       COUNT(*) AS pocet
FROM blog_credits
WHERE kredity < -1;

SELECT 'PRE-CHECK: počet záporných total v mv_lekce_ucast' AS info,
       COUNT(*) AS pocet
FROM mv_lekce_ucast
WHERE total < 0;


-- ============================================================
-- KROK 1: OPRAVA DAT – kredity < -1 → -1
-- ============================================================
-- Důvod: invariant „kredit nesmí být < -1" byl porušen
--        historicky (chyběla ochrana na DB úrovni).
--        Nastavujeme na -1 = „klient má dluh, ale jen jeden".

UPDATE blog_credits
SET
  kredity    = -1,
  updated_at = NOW(),
  updated_by = 'migrace-20260605'
WHERE kredity < -1;

-- Ověření po opravě
SELECT 'POST-FIX: počet záznamů s kredity < -1 (musí být 0)' AS info,
       COUNT(*) AS pocet
FROM blog_credits
WHERE kredity < -1;


-- ============================================================
-- KROK 2: CHECK CONSTRAINT – blog_credits.kredity >= -1
-- ============================================================
-- IF NOT EXISTS – migrace je bezpečná při opakovaném spuštění.

ALTER TABLE blog_credits
  ADD CONSTRAINT IF NOT EXISTS chk_kredity_min CHECK (kredity >= -1);


-- ============================================================
-- KROK 3: TRIGGER br_au – ochrana total >= 0 (AFTER UPDATE)
-- ============================================================

DROP TRIGGER IF EXISTS `br_au`;

DELIMITER //
CREATE TRIGGER `br_au` AFTER UPDATE ON `blog_registration`
 FOR EACH ROW BEGIN

  -- Pokud se změnil deleted z 0 → 1 → odečti (ochrana: min 0)
  IF OLD.deleted = 0 AND NEW.deleted != 0 THEN
    UPDATE mv_lekce_ucast
      SET total = GREATEST(0, total - 1)
      WHERE diary_id = OLD.diary_id;
  END IF;

  -- Pokud se změnil deleted z 1 → 0 → přičti
  IF OLD.deleted != 0 AND NEW.deleted = 0 THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;

  -- Pokud se změnil diary_id při deleted = 0 (přesun lekce)
  IF OLD.deleted = 0 AND NEW.deleted = 0 AND OLD.diary_id != NEW.diary_id THEN
    UPDATE mv_lekce_ucast
      SET total = GREATEST(0, total - 1)
      WHERE diary_id = OLD.diary_id;
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;

END
//
DELIMITER ;


-- ============================================================
-- KROK 4: TRIGGER br_ad – ochrana total >= 0 (AFTER DELETE)
-- ============================================================

DROP TRIGGER IF EXISTS `br_ad`;

DELIMITER //
CREATE TRIGGER `br_ad` AFTER DELETE ON `blog_registration`
 FOR EACH ROW BEGIN
  IF OLD.deleted = 0 THEN
    UPDATE mv_lekce_ucast
      SET total = GREATEST(0, total - 1)
      WHERE diary_id = OLD.diary_id;
  END IF;
END
//
DELIMITER ;


-- ============================================================
-- KROK 5: POST-CHECK (ověření výsledku)
-- ============================================================

SELECT 'POST-CHECK: CHECK constraint chk_kredity_min' AS info,
       CONSTRAINT_NAME,
       CHECK_CLAUSE
FROM information_schema.CHECK_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = 'blog_credits'
  AND CONSTRAINT_NAME = 'chk_kredity_min';

SELECT 'POST-CHECK: triggery blog_registration' AS info,
       TRIGGER_NAME,
       ACTION_STATEMENT
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND EVENT_OBJECT_TABLE = 'blog_registration'
ORDER BY TRIGGER_NAME;
