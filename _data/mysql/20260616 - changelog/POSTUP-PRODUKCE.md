# Nasazení migrace 20260616 na produkci

## Co migrace mění

| Krok | Tabulka / objekt | Typ změny |
|------|------------------|-----------|
| 1 | `blog_registration` | ALTER TABLE - přidá `registration_status` (`ucastnik`, `nahradnik`) |
| 2 | `mv_lekce_ucast` | Přepočet obsazenosti - počítá jen aktivní účastníky |
| 3 | Trigger `br_ai` na `blog_registration` | REPLACE - započítá jen nové účastníky |
| 4 | Trigger `br_au` na `blog_registration` | REPLACE - řeší změny `deleted`, `diary_id` a `registration_status` |
| 5 | Trigger `br_ad` na `blog_registration` | REPLACE - odečítá jen smazané účastníky |

## Před nasazením

1. **Záloha DB** (nebo ověřit, že existuje aktuální záloha):
   ```bash
   mysqldump -h <host> -u <user> -p<pass> <db> > backup_pred_migraci_20260616_$(date +%Y%m%d_%H%M).sql
   ```

2. **Ověřit aktuální databázi**:
   ```sql
   SELECT DATABASE() AS aktualni_databaze;
   ```
   Pokud skript spouštíte ručně v phpMyAdmin, musí být vybraná aplikační databáze, ne `information_schema`. Pro tuto instalaci typicky:
   ```sql
   USE d373504_rezerva;
   ```

3. **Spustit PRE-CHECK ručně** (jen SELECT, nic nemění):
   ```sql
   -- Aktuální aktivní registrace podle lekce
   SELECT
     diary_id,
     COUNT(*) AS aktivni_registrace
   FROM blog_registration
   WHERE deleted = 0
   GROUP BY diary_id
   ORDER BY diary_id;

   -- Kontrola rozdílů mezi materializovaným počtem a registracemi před migrací
   SELECT
     r.diary_id,
     r.aktivni_registrace,
     COALESCE(m.total, 0) AS mv_total
   FROM (
     SELECT diary_id, COUNT(*) AS aktivni_registrace
     FROM blog_registration
     WHERE deleted = 0
     GROUP BY diary_id
   ) r
   LEFT JOIN mv_lekce_ucast m ON m.diary_id = r.diary_id
   WHERE r.aktivni_registrace != COALESCE(m.total, 0)
   ORDER BY r.diary_id;
   ```
   Výsledek zaznamenat - po migraci se `mv_lekce_ucast.total` bude rovnat počtu aktivních registrací se stavem `ucastnik`.

4. **Ověřit, zda sloupec ještě neexistuje**:
   ```sql
   SHOW COLUMNS FROM blog_registration LIKE 'registration_status';
   ```
   Pokud nevrátí žádný řádek, migrace sloupec přidá. Pokud už existuje, `ADD COLUMN IF NOT EXISTS` krok přeskočí.

## Pořadí nasazení

1. Záloha databáze.
2. Spustit migraci `20260616.sql`.
3. Ověřit výstup migrace.
4. Teprve potom nasadit PHP kód s podporou náhradníků.

PHP kód po této změně počítá se sloupcem `blog_registration.registration_status`, proto nesmí být nasazen před migrací DB.

## Vhodný čas nasazení

- Migrace krátce upravuje tabulku `blog_registration` a přepisuje triggery, proto je vhodné nasazení v době nízké zátěže.
- Krok přepočtu `mv_lekce_ucast` smaže a znovu naplní materializované počty podle aktuálních registrací.
- Během `DROP TRIGGER` + `CREATE TRIGGER` je malé okno, kdy triggery nejsou aktivní; nasazujte mimo špičku.

## Spuštění migrace

```bash
mysql -h <host> -u <user> -p<pass> <db> < 20260616.sql
```

Při ručním spuštění v phpMyAdmin nejprve vyberte aplikační databázi nebo spusťte:

```sql
USE d373504_rezerva;
```

Výstup migrace zkontrolovat:
- `SHOW COLUMNS FROM blog_registration LIKE 'registration_status'` → vrací sloupec typu `enum('ucastnik','nahradnik')` s defaultem `ucastnik`
- `SHOW TRIGGERS LIKE 'blog_registration'` → obsahuje `br_ai`, `br_au`, `br_ad`
- triggery pracují s podmínkou `registration_status = 'ucastnik'`

## Ověření po nasazení

```sql
-- 1. Sloupec existuje
SHOW COLUMNS FROM blog_registration LIKE 'registration_status';

-- 2. Všechny aktivní historické registrace jsou po migraci účastníci
SELECT registration_status, COUNT(*) AS pocet
FROM blog_registration
WHERE deleted = 0
GROUP BY registration_status;

-- 3. mv_lekce_ucast odpovídá aktivním účastníkům
SELECT
  r.diary_id,
  r.ucastnici,
  COALESCE(m.total, 0) AS mv_total
FROM (
  SELECT diary_id, COUNT(*) AS ucastnici
  FROM blog_registration
  WHERE deleted = 0
    AND registration_status = 'ucastnik'
  GROUP BY diary_id
) r
LEFT JOIN mv_lekce_ucast m ON m.diary_id = r.diary_id
WHERE r.ucastnici != COALESCE(m.total, 0)
ORDER BY r.diary_id;

-- 4. Triggery jsou aktuální
SHOW TRIGGERS LIKE 'blog_registration';
```

Očekávání:
- kontrola 1 vrátí jeden řádek,
- kontrola 2 bude po první migraci typicky obsahovat pouze `ucastnik`, náhradníci vzniknou až novým kódem,
- kontrola 3 nevrátí žádný řádek,
- kontrola 4 ukáže `br_ai`, `br_au`, `br_ad` s podmínkami pro `registration_status`.

## Aplikační test po nasazení

1. Najít lekci s naplněnou kapacitou.
2. Přihlásit dalšího klienta - má vzniknout registrace se stavem `nahradnik`.
3. Ověřit, že obsazenost lekce zůstala na maximu a počet náhradníků je `1/3`.
4. Zrušit běžného účastníka.
5. Ověřit, že první náhradník byl přesunut na `ucastnik`.
6. Ověřit, že náhradníkovi byla odeslána SMS.
7. Ověřit, že `mv_lekce_ucast.total` zůstává rovný počtu účastníků, ne účastníků plus náhradníků.

## Rollback

Nejbezpečnější rollback je obnova DB ze zálohy před migrací. Ruční rollback je možný, pokud po nasazení nevznikly žádné aktivní registrace se stavem `nahradnik`.

### Ruční rollback pouze před vznikem náhradníků

```sql
-- Kontrola, jestli už vznikli náhradníci
SELECT COUNT(*) AS aktivni_nahradnici
FROM blog_registration
WHERE deleted = 0
  AND registration_status = 'nahradnik';
```

Pokud výsledek není `0`, nepoužívat ruční rollback bez individuálního rozhodnutí, co s náhradnickými registracemi.

Pokud výsledek je `0`, lze vrátit triggery na verzi bez `registration_status` a odstranit sloupec:

```sql
DROP TRIGGER IF EXISTS `br_ai`;
DELIMITER //
CREATE TRIGGER `br_ai` AFTER INSERT ON `blog_registration`
 FOR EACH ROW BEGIN
  IF NEW.deleted = 0 THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;
END
//
DELIMITER ;

DROP TRIGGER IF EXISTS `br_au`;
DELIMITER //
CREATE TRIGGER `br_au` AFTER UPDATE ON `blog_registration`
 FOR EACH ROW BEGIN
  IF OLD.deleted = 0 AND NEW.deleted != 0 THEN
    UPDATE mv_lekce_ucast
      SET total = GREATEST(0, total - 1)
      WHERE diary_id = OLD.diary_id;
  END IF;

  IF OLD.deleted != 0 AND NEW.deleted = 0 THEN
    INSERT INTO mv_lekce_ucast (diary_id, total)
    VALUES (NEW.diary_id, 1)
    ON DUPLICATE KEY UPDATE total = total + 1;
  END IF;

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

ALTER TABLE blog_registration
  DROP COLUMN registration_status;

DELETE FROM mv_lekce_ucast;

INSERT INTO mv_lekce_ucast (diary_id, total)
SELECT
  diary_id,
  COUNT(*) AS total
FROM blog_registration
WHERE deleted = 0
GROUP BY diary_id;
```

> Pokud už existují náhradníci, odstranění sloupce by ztratilo informaci o jejich stavu a započítalo by je jako běžné účastníky. V takovém případě raději obnovit zálohu nebo ručně zrušit/převést náhradnické registrace podle provozní situace.
