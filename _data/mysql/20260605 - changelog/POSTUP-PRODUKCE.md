# Nasazení migrace 20260605 na produkci

## Co migrace mění

| Krok | Tabulka / objekt | Typ změny |
|------|-----------------|-----------|
| 1 | `blog_credits` – záznamy s `kredity < -1` | UPDATE dat → nastaví na `-1` |
| 2 | `blog_credits` – CHECK constraint | ALTER TABLE – přidá `CHECK (kredity >= -1)` |
| 3 | Trigger `br_au` na `blog_registration` | REPLACE – přidá `GREATEST(0, total-1)` |
| 4 | Trigger `br_ad` na `blog_registration` | REPLACE – přidá `GREATEST(0, total-1)` |

## Před nasazením

1. **Záloha DB** (nebo ověřit, že existuje aktuální záloha):
   ```bash
   mysqldump -h <host> -u <user> -p<pass> <db> > backup_pred_migraci_$(date +%Y%m%d_%H%M).sql
   ```

2. **Spustit PRE-CHECK ručně** (jen SELECT, nic nemění):
   ```sql
   -- Počty porušení
   SELECT COUNT(*) AS pocet_kreditu_pod_minus1 FROM blog_credits WHERE kredity < -1;
   SELECT COUNT(*) AS total_neg FROM mv_lekce_ucast WHERE total < 0;

   -- Seznam uživatelů s kredity < -1 (pro informaci / případnou komunikaci)
   SELECT
     c.ID         AS kredit_id,
     u.id         AS user_id,
     u.username,
     u.firstname,
     u.surname,
     u.email,
     a.nazev      AS aktivita,
     c.kredity,
     c.updated_at AS kredit_upraveny
   FROM blog_credits c
   JOIN blog_users   u ON u.id = c.user_id      AND u.deleted = 0
   JOIN blog_activity a ON a.id = c.aktivita_id AND a.deleted = 0
   WHERE c.kredity < -1
     AND c.deleted = 0
   ORDER BY c.kredity ASC, u.surname, u.firstname;
   ```
   Výsledek zaznamenat — slouží pro srovnání po migraci a případnou komunikaci s uživateli.

3. **Zkontrolovat aktuální databázi a strukturu tabulky**:
   ```sql
   SELECT DATABASE() AS aktualni_databaze;
   SHOW CREATE TABLE blog_credits;
   ```

## Vhodný čas nasazení

- Migrace **nevyžaduje výpadek** — ALTER TABLE i UPDATE proběhnou rychle (malá tabulka).
- Vhodné nasadit v **době nízké zátěže** (noc, brzy ráno) jako prevence.
- Triggery jsou nahrazeny atomicky (`DROP` + `CREATE`) — riziko race condition je minimální.

## Spuštění migrace

```bash
mysql -h <host> -u <user> -p<pass> <db> < 20260605.sql
```

Výstup bude obsahovat výsledky PRE-CHECK a POST-CHECK. Zkontrolovat:
- `POST-FIX: počet záznamů s kredity < -1 (musí být 0)` → **0**
- `SHOW CREATE TABLE blog_credits` → obsahuje `chk_kredity_min`
- `POST-CHECK: triggery blog_registration` → všechny tři triggery (`br_ad`, `br_ai`, `br_au`)

## Ověření po nasazení

```sql
-- 1. Žádné porušení constraintu
SELECT COUNT(*) FROM blog_credits WHERE kredity < -1;  -- musí být 0

-- 2. Constraint existuje
SHOW CREATE TABLE blog_credits;

-- 3. Triggery jsou aktuální
SHOW TRIGGERS LIKE 'blog_registration';
-- br_au a br_ad musí obsahovat GREATEST

-- 4. Aplikace funguje – otestovat registraci a zrušení registrace
```

## Rollback

Constraint lze bezpečně odebrat, triggery obnovit ze zálohy SQL:

```sql
-- Odstranit constraint
ALTER TABLE blog_credits DROP CONSTRAINT chk_kredity_min;

-- Obnovit původní trigger br_au (bez GREATEST)
DROP TRIGGER IF EXISTS `br_au`;
DELIMITER //
CREATE TRIGGER `br_au` AFTER UPDATE ON `blog_registration`
 FOR EACH ROW BEGIN
  IF OLD.deleted = 0 AND NEW.deleted != 0 THEN
    UPDATE mv_lekce_ucast SET total = total - 1 WHERE diary_id = OLD.diary_id;
  END IF;
  IF OLD.deleted != 0 AND NEW.deleted = 0 THEN
    INSERT INTO mv_lekce_ucast (diary_id, total) VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;
  IF OLD.deleted = 0 AND NEW.deleted = 0 AND OLD.diary_id != NEW.diary_id THEN
    UPDATE mv_lekce_ucast SET total = total - 1 WHERE diary_id = OLD.diary_id;
    INSERT INTO mv_lekce_ucast (diary_id, total) VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;
END
//
DELIMITER ;

-- Obnovit původní trigger br_ad (bez GREATEST)
DROP TRIGGER IF EXISTS `br_ad`;
DELIMITER //
CREATE TRIGGER `br_ad` AFTER DELETE ON `blog_registration`
 FOR EACH ROW BEGIN
  IF OLD.deleted = 0 THEN
    UPDATE mv_lekce_ucast SET total = total - 1 WHERE diary_id = OLD.diary_id;
  END IF;
END
//
DELIMITER ;
```

> Opravu dat (krok 1 – `UPDATE kredity = -1`) **nelze automaticky vrátit zpět**.
> Původní hodnoty jsou viditelné v záloze DB. Pokud by bylo třeba obnovit,
> je nutné ručně porovnat zálohu a aktualizovat konkrétní záznamy.
