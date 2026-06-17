-- ============================================================
-- MIGRACE 20260616 - Nahradnici lekci
-- ============================================================
-- ZMENY:
--   1. blog_registration.registration_status
--   2. mv_lekce_ucast pocita jen skutecne ucastniky
--   3. Triggery blog_registration respektuji nahradniky
-- ============================================================


-- KROK 1: Stav registrace
ALTER TABLE blog_registration
  ADD COLUMN IF NOT EXISTS registration_status ENUM('ucastnik','nahradnik')
    NOT NULL DEFAULT 'ucastnik'
    AFTER aktivita_id;


-- KROK 2: Prepocet obsazenosti lekci
DELETE FROM mv_lekce_ucast;

INSERT INTO mv_lekce_ucast (diary_id, total)
SELECT
  diary_id,
  COUNT(*) AS total
FROM blog_registration
WHERE
  deleted = 0
  AND registration_status = 'ucastnik'
GROUP BY diary_id;


-- KROK 3: Trigger po vlozeni registrace
DROP TRIGGER IF EXISTS `br_ai`;

DELIMITER //
CREATE TRIGGER `br_ai` AFTER INSERT ON `blog_registration`
 FOR EACH ROW BEGIN
  IF NEW.deleted = 0 AND NEW.registration_status = 'ucastnik' THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;
END
//
DELIMITER ;


-- KROK 4: Trigger po uprave registrace
DROP TRIGGER IF EXISTS `br_au`;

DELIMITER //
CREATE TRIGGER `br_au` AFTER UPDATE ON `blog_registration`
 FOR EACH ROW BEGIN
  IF OLD.deleted = 0
     AND OLD.registration_status = 'ucastnik'
     AND (NEW.deleted != 0 OR NEW.registration_status != 'ucastnik') THEN
    UPDATE mv_lekce_ucast
      SET total = GREATEST(0, total - 1)
      WHERE diary_id = OLD.diary_id;
  END IF;

  IF (OLD.deleted != 0 OR OLD.registration_status != 'ucastnik')
     AND NEW.deleted = 0
     AND NEW.registration_status = 'ucastnik' THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;

  IF OLD.deleted = 0
     AND NEW.deleted = 0
     AND OLD.registration_status = 'ucastnik'
     AND NEW.registration_status = 'ucastnik'
     AND OLD.diary_id != NEW.diary_id THEN
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


-- KROK 5: Trigger po smazani registrace
DROP TRIGGER IF EXISTS `br_ad`;

DELIMITER //
CREATE TRIGGER `br_ad` AFTER DELETE ON `blog_registration`
 FOR EACH ROW BEGIN
  IF OLD.deleted = 0 AND OLD.registration_status = 'ucastnik' THEN
    UPDATE mv_lekce_ucast
      SET total = GREATEST(0, total - 1)
      WHERE diary_id = OLD.diary_id;
  END IF;
END
//
DELIMITER ;


-- KROK 6: Kontrola
-- Nepouzivame information_schema, protoze nektere hostingy k nemu
-- nedavaji aplikacnim DB uzivatelum pristup.
SHOW COLUMNS FROM blog_registration LIKE 'registration_status';

SHOW TRIGGERS LIKE 'blog_registration';

-- KROK 7: Modifikace sloupce action v tabulce blog_eventlog

ALTER TABLE `blog_eventlog` CHANGE `action` `action` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;


-- KROK 8: Bezpecnejsi obnova hesla
-- password_recovery_pin od teto verze uklada hash PIN, ne PIN v citelne podobe.
ALTER TABLE blog_users
  ADD COLUMN IF NOT EXISTS password_recovery_pin_created_at DATETIME NULL DEFAULT NULL
    AFTER password_recovery_pin;

ALTER TABLE blog_users
  ADD COLUMN IF NOT EXISTS password_recovery_pin_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0
    AFTER password_recovery_pin_created_at;

UPDATE blog_users
SET
  password_recovery_pin = NULL,
  password_recovery_pin_created_at = NULL,
  password_recovery_pin_attempts = 0
WHERE password_recovery_pin IS NOT NULL;

SHOW COLUMNS FROM blog_users LIKE 'password_recovery_pin_created_at';
SHOW COLUMNS FROM blog_users LIKE 'password_recovery_pin_attempts';


-- KROK 9: Serverovy rate limit prihlaseni a obnovy hesla
CREATE TABLE IF NOT EXISTS blog_security_rate_limit (
  id INT NOT NULL AUTO_INCREMENT,
  rate_key CHAR(40) NOT NULL,
  scope VARCHAR(50) NOT NULL,
  identifier VARCHAR(255) DEFAULT NULL,
  remote_ip VARCHAR(45) DEFAULT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  first_at DATETIME NOT NULL,
  blocked_until DATETIME DEFAULT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ux_blog_security_rate_limit_key (rate_key),
  KEY ix_blog_security_rate_limit_scope_ip (scope, remote_ip),
  KEY ix_blog_security_rate_limit_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SHOW TABLES LIKE 'blog_security_rate_limit';
