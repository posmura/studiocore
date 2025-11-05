CREATE TABLE mv_lekce_ucast (
    diary_id INT UNSIGNED PRIMARY KEY,
    total INT UNSIGNED NOT NULL DEFAULT 0
);


INSERT INTO mv_lekce_ucast (diary_id, total)
SELECT diary_id, COUNT(*) 
FROM blog_registration
WHERE deleted = 0
GROUP BY diary_id;



DELIMITER $$
CREATE TRIGGER br_ai AFTER INSERT ON blog_registration
FOR EACH ROW
BEGIN
  IF NEW.deleted = 0 THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;
END$$
DELIMITER ;



DELIMITER $$
CREATE TRIGGER br_au AFTER UPDATE ON blog_registration
FOR EACH ROW
BEGIN
  -- Pokud se změnil deleted z 0 → 1 → odečti
  IF OLD.deleted = 0 AND NEW.deleted != 0 THEN
    UPDATE mv_lekce_ucast 
      SET total = total - 1 
      WHERE diary_id = OLD.diary_id;
  END IF;

  -- Pokud se změnil deleted z 1 → 0 → přičti
  IF OLD.deleted != 0 AND NEW.deleted = 0 THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;

  -- Pokud se změnil diary_id při deleted = 0
  IF OLD.deleted = 0 AND NEW.deleted = 0 AND OLD.diary_id != NEW.diary_id THEN
    UPDATE mv_lekce_ucast SET total = total - 1 WHERE diary_id = OLD.diary_id;
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;
END$$
DELIMITER ;



DELIMITER $$
CREATE TRIGGER br_ad AFTER DELETE ON blog_registration
FOR EACH ROW
BEGIN
  IF OLD.deleted = 0 THEN
    UPDATE mv_lekce_ucast 
      SET total = total - 1 
      WHERE diary_id = OLD.diary_id;
  END IF;
END$$
DELIMITER ;

