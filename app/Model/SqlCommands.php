<?php

  namespace App\Model;

  class SqlCommands
  {


    /**
     * KREDITY: Seznam aktivních permanentek pro načtení a úpravu kreditů
     *
     * Pozn.: Aktivní permanentka je ta, která má aktuální počet kreditů > 0 a konec platnosti <= aktuální den
     *
     * @return string
     */
    public static function getAktivniPermanentkyID(): string
    {
      return <<<SQL
SELECT *
FROM
  `blog_sales`
WHERE
  `user_id`=?
  AND `aktivita_id` = ?
  AND `vstupy_aktualni` > 0
  AND `datum_konce` >= ?
  AND `deleted` = 0
ORDER BY
  `datum_konce` ASC,
  `ID` ASC

SQL;
    }


    /**
     * KREDITY: Vybere kredity klienty
     *
     * @return string
     */
    public static function getKredityKlienta(): string
    {
      return <<<SQL
SELECT * FROM blog_credits WHERE user_id=? AND aktivita_id=? AND deleted=0
SQL;
    }


    /**
     * KREDITY: Upraví kredity klienta o požadovanou hodnotu
     *
     * @return string
     */
    public static function updateKredityKlienta(): string
    {
      return <<<SQL
UPDATE blog_credits
SET
  `kredity`= `kredity`+ ?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   user_id=?
   AND aktivita_id=?
   AND deleted=0
SQL;
    }



    /**
     * KREDITY: Vyresetuje kredity klienta pro danou aktivitu
     *
     * @return string
     */
    public static function resetKredit(): string
    {
      return <<<SQL
UPDATE blog_credits
SET
  `kredity`=?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   user_id=?
   AND aktivita_id=?
   AND deleted=0
SQL;
    }

    /**
     * KREDITY: Ruční úprava kreditu klienta pro konkrétní aktivita_id
     *
     * @return string
     */
    public static function updateKredit(): string
    {
      return <<<SQL
UPDATE blog_credits
SET
  `kredity`= ?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   user_id=?
   AND aktivita_id=?
   AND deleted=0
SQL;
    }


    /**
     * KREDITY: Upraví aktualní vstupy na aktuální pemanentce klienta při změně registrace (přihlášení/odhlášení na/z lekce)
     *
     * @return string
     */
    public static function updateKredityAktivniPermanentka(): string
    {
      return <<<SQL
UPDATE blog_sales
SET
  `vstupy_aktualni`= CAST(`vstupy_aktualni` AS SIGNED) + ?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   ID=?
   AND deleted=0
   AND (CAST(`vstupy_aktualni` AS SIGNED) + ?) >= 0
SQL;
    }


    /**
     * KREDITY: Vybere kredity všech klientů
     *
     * @return string
     */
    public static function getAllKredityKlienta(): string
    {
      return <<<SQL
SELECT * FROM blog_credits WHERE deleted=0
SQL;
    }


    /**
     * KREDITY: Vytvoří záznam pro kredity klienta
     *
     * @return string
     */
    public static function createKredityKlienta(): string
    {
      return <<<SQL
INSERT INTO blog_credits
(
  `user_id`,
  `aktivita_id`,
  `kredity`,
  `created_at`,
  `created_by`
)
VALUES
  (?,?,?,NOW(),?);
SQL;
    }


    /**
     * PRODEJ: Vybere všechny prodeje
     *
     * @return string
     */
    public static function getAllProdej(): string
    {
      return <<<SQL
SELECT * FROM blog_sales WHERE deleted=0 ORDER BY created_at DESC
SQL;
    }


    /**
     * PRODEJ: Vybere prodej podle ID
     *
     * @return string
     */
    public static function getProdej(): string
    {
      return <<<SQL
SELECT * FROM blog_sales WHERE id=? AND deleted=0
SQL;
    }


    /**
     * PRODEJ: Vrací počet registrací navázaných na prodej.
     *
     * @return string
     */
    public static function getRegistrationCountBySalesId(): string
    {
      return <<<SQL
SELECT COUNT(*) AS total FROM blog_registration WHERE sales_id=?
SQL;
    }


    /**
     * PRODEJ: Vloží prodej
     *
     * @return string
     */
    public static function insertProdej(): string
    {
      return <<<SQL
INSERT INTO blog_sales
(
  `user_id`,
  `username_full`,
  `permanentka_id`,
  `aktivita_id`,
  `aktivita_name`,
  `cena`,
  `vstupy_celkem`,
  `vstupy_aktualni`,
  `datum_prodeje`,
  `datum_konce`,
  `desc`,
  `created_at`,
  `created_by`
)
VALUES
  (?,?,?,?,?,?,?,?,?,?,?,NOW(),?);
SQL;
    }


    /**
     * PRODEJ: Smaže prodej
     *
     * @return string
     */
    public static function deleteProdej(): string
    {
      return <<<SQL
UPDATE blog_sales SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=? AND deleted=0
SQL;
    }


    /**
     * PERMANENTKA: Vybere všechny permanentky
     *
     * @return string
     */
    public static function getAllPermanentka(): string
    {
      return <<<SQL
SELECT * FROM blog_membership_card WHERE deleted=0 ORDER BY created_at DESC
SQL;
    }


    /**
     * PERMANENTKA: Vybere permanentku podle id
     *
     * @return string
     */
    public static function getPermanentka(): string
    {
      return <<<SQL
SELECT * FROM blog_membership_card WHERE id=? AND deleted=0
SQL;
    }


    /**
     * PERMANENTKA: Vrací počet prodejů navázaných na permanentku.
     *
     * @return string
     */
    public static function getSalesCountByPermanentkaId(): string
    {
      return <<<SQL
SELECT COUNT(*) AS total FROM blog_sales WHERE permanentka_id=?
SQL;
    }


    /**
     * PERMANENTKA: Vybere všechny permanentky setříděné podle aktivity a ceny
     *
     * @return string
     */
    public static function getAllPermanentkaOrderByActivity(): string
    {
      return <<<SQL
SELECT
  b.nazev AS nazev_aktivity,
  a.*
FROM
  blog_membership_card AS a
LEFT JOIN
  blog_activity AS  b ON a.aktivita_id = b.id
WHERE
  a.deleted = 0
ORDER BY
  b.nazev,
  a.cena
SQL;
    }


    /**
     * PERMANENTKA: Vybere aktivní permanentky setříděné podle aktivity a ceny.
     *
     * @return string
     */
    public static function getAllAktivniPermanentkaOrderByActivity(): string
    {
      return <<<SQL
SELECT
  b.nazev AS nazev_aktivity,
  a.*
FROM
  blog_membership_card AS a
LEFT JOIN
  blog_activity AS  b ON a.aktivita_id = b.id
WHERE
  a.deleted = 0
  AND a.aktivni = 'ano'
  AND b.deleted = 0
ORDER BY
  b.nazev,
  a.cena
SQL;
    }


    /**
     * PRODEJ: Vybere permanentku a odpovídající aktivitu
     *
     * @return string
     */
    public static function getPermanentkaActivitaById(): string
    {
      return <<<SQL
SELECT
  b.nazev AS nazev_aktivity,
  a.*
FROM
  blog_membership_card AS a
LEFT JOIN
  blog_activity AS  b ON a.aktivita_id = b.id
WHERE
  a.deleted = 0
  AND a.aktivni = 'ano'
  AND b.deleted = 0
  AND a.id = ?
SQL;
    }


    /**
     * PERMANENTKA: Vloží permanentku
     *
     * @return string
     */
    public static function insertPermanentka(): string
    {
      return <<<SQL
INSERT INTO blog_membership_card
(
  `aktivita_id`,
  `nazev`,
  `cena`,
  `platnost`,
  `platnost_ts`,
  `aktivni`,
  `vstupy`,
  `created_at`,
  `created_by`
)
VALUES
  (?,?,?,?,?,?,?,NOW(),?);
SQL;
    }


    /**
     * PERMANENTKA: Upraví permanentku
     *
     * @return string
     */
    public static function updatePermanentka(): string
    {
      return <<<SQL
UPDATE blog_membership_card
SET
  `aktivita_id`=?,
  `nazev`=?,
  `cena`=?,
  `platnost`=?,
  `platnost_ts`=?,
  `aktivni`=?,
  `vstupy`=?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   id=?
   AND deleted=0
SQL;
    }


    /**
     * PERMANENTKA: Smaže permanentku
     *
     * @return string
     */
    public static function deletePermanentka(): string
    {
      return <<<SQL
UPDATE blog_membership_card SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=? AND deleted=0
SQL;
    }


    /**
     * AKTIVITA: Vybere všechny aktivity
     *
     * @return string
     */
    public static function getAllAktivita(): string
    {
      return <<<SQL
SELECT * FROM blog_activity WHERE deleted=0 ORDER BY created_at DESC
SQL;
    }


    /**
     * USER: Vybere všechny lektory
     *
     * @return string
     */
    public static function getAllLektor(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE role="lektor" ORDER BY surname, firstname DESC
SQL;
    }


    /**
     * AKTIVITA: Vybere aktivitu podle ID
     *
     * @return string
     */
    public static function getAktivita(): string
    {
      return <<<SQL
SELECT * FROM blog_activity WHERE id=? AND deleted=0
SQL;
    }


    /**
     * AKTIVITA: Vrací počty vazeb na aktivitu.
     *
     * @return string
     */
    public static function getAktivitaUsageCounts(): string
    {
      return <<<SQL
SELECT
  (SELECT COUNT(*) FROM blog_diary WHERE aktivita_id=?) AS diary_total,
  (SELECT COUNT(*) FROM blog_membership_card WHERE aktivita_id=?) AS membership_card_total,
  (SELECT COUNT(*) FROM blog_sales WHERE aktivita_id=?) AS sales_total,
  (SELECT COUNT(*) FROM blog_registration WHERE aktivita_id=?) AS registration_total,
  (SELECT COUNT(*) FROM blog_credits WHERE aktivita_id=? AND deleted=0 AND kredity != 0) AS credits_total
SQL;
    }


    /**
     * AKTIVITA: Vloží aktivitu
     *
     * @return string
     */
    public static function insertAktivita(): string
    {
      return <<<SQL
INSERT INTO blog_activity
(
  `nazev`,
  `vstupy_min`,
  `vstupy_max`,
  `zruseni_zdarma`,
  `zruseni_zdarma_ts`,
  `zruseni_neucast`,
  `zruseni_neucast_ts`,
  `registrace_konec`,
  `registrace_konec_ts`,
  `created_at`,
  `created_by`
)
VALUES
  (?,?,?,?,?,?,?,?,?,NOW(),?);
SQL;
    }


    /**
     * AKTIVITA: Upraví aktivitu
     *
     * @return string
     */
    public static function updateAktivita(): string
    {
      return <<<SQL
UPDATE blog_activity
SET
  `nazev`=?,
  `vstupy_min`=?,
  `vstupy_max`=?,
  `zruseni_zdarma`=?,
  `zruseni_zdarma_ts`=?,
  `zruseni_neucast`=?,
  `zruseni_neucast_ts`=?,
  `registrace_konec`=?,
  `registrace_konec_ts`=?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   id=?
   AND deleted=0
SQL;
    }


    /**
     * AKTIVITA: Smaže aktivitu
     *
     * @return string
     */
    public static function deleteAktivita(): string
    {
      return <<<SQL
UPDATE blog_activity SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=? AND deleted=0
SQL;
    }


    /**
     * UŽIVATEL: Vybere všchny uživatele
     *
     * @return string
     */
    public static function getAllUsers(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE deleted=0 ORDER BY username
SQL;
    }


    /**
     * UŽIVATEL: Vybere všchny uživatele
     *
     * @return string
     */
    public static function getAllUsersOrderBySurname(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE deleted=0 ORDER BY surname, firstname, username
SQL;
    }


    /**
     * UŽIVATEL: Vybere uživatele podle uživatelského jména
     *
     * @return string
     */
    public static function getUser(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE username=? AND deleted=0
SQL;
    }


    /**
     * UŽIVATEL: Vybere uživatele podle ID
     *
     * @return string
     */
    public static function getUserByID(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE id=? AND deleted=0
SQL;
    }


    /**
     * UŽIVATEL: Vybere uživatele podle uživatelského jména a mobilního telefonu
     *
     * @return string
     */
    public static function getUserByMobilNumber(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE username=? AND mobil_number=? AND deleted=0
SQL;
    }


    /**
     * UŽIVATEL: Vybere uživatele podle uživatelského jména a PIN pro obnovení hesla
     *
     * @return string
     */
    public static function getUserByPasswordRecoveryPin(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE username=? AND password_recovery_pin IS NOT NULL AND deleted=0
SQL;
    }


    /**
     * UŽIVATEL: Aktualizuje PIN pro obnovu hesla (password_recovery_pin)
     *
     * @return string
     */
    public static function updatePasswordRecoveryPin(): string
    {
      return <<<SQL
UPDATE
  blog_users
SET
  password_recovery_pin=?,
  password_recovery_pin_created_at=NOW(),
  password_recovery_pin_attempts=0,
  updated_by=?,
  updated_at=NOW()
WHERE
  id=?
SQL;
    }


    /**
     * UŽIVATEL: Navýší počet pokusů o PIN pro obnovu hesla
     *
     * @return string
     */
    public static function increasePasswordRecoveryPinAttempts(): string
    {
      return <<<SQL
UPDATE
  blog_users
SET
  password_recovery_pin_attempts=password_recovery_pin_attempts + 1,
  updated_by=?,
  updated_at=NOW()
WHERE
  id=?
SQL;
    }


    /**
     * UŽIVATEL: Vymaže PIN pro obnovu hesla
     *
     * @return string
     */
    public static function clearPasswordRecoveryPin(): string
    {
      return <<<SQL
UPDATE
  blog_users
SET
  password_recovery_pin=NULL,
  password_recovery_pin_created_at=NULL,
  password_recovery_pin_attempts=0,
  updated_by=?,
  updated_at=NOW()
WHERE
  id=?
SQL;
    }


    /**
     * UŽIVATEL: Aktualizuje heslo uživatele
     *
     * @return string
     */
    public static function updatePassword(): string
    {
      return <<<SQL
UPDATE
  blog_users
SET
  password_hash=?,
  password_recovery_pin=NULL,
  password_recovery_pin_created_at=NULL,
  password_recovery_pin_attempts=0,
  updated_by=?,
  updated_at=NOW()
WHERE
  id=?
SQL;
    }


    /**
     * UŽIVATEL: Vybere všechny uživatele
     *
     * @return string
     */
    public static function getAllUsernames(): string
    {
      return <<<SQL
SELECT username FROM blog_users
SQL;
    }


    /**
     * BEZPEČNOST: Vrací stav rate limitu podle klíče
     *
     * @return string
     */
    public static function getSecurityRateLimit(): string
    {
      return <<<SQL
SELECT * FROM blog_security_rate_limit WHERE rate_key=?
SQL;
    }


    /**
     * BEZPEČNOST: Uloží stav rate limitu
     *
     * @return string
     */
    public static function saveSecurityRateLimit(): string
    {
      return <<<SQL
INSERT INTO blog_security_rate_limit
  (rate_key,scope,identifier,remote_ip,attempts,first_at,blocked_until,updated_at)
VALUES
  (?,?,?,?,?,?,?,NOW())
ON DUPLICATE KEY UPDATE
  attempts=VALUES(attempts),
  first_at=VALUES(first_at),
  blocked_until=VALUES(blocked_until),
  updated_at=NOW()
SQL;
    }


    /**
     * BEZPEČNOST: Vymaže stav rate limitu
     *
     * @return string
     */
    public static function clearSecurityRateLimit(): string
    {
      return <<<SQL
DELETE FROM blog_security_rate_limit WHERE rate_key=?
SQL;
    }


    /**
     * UŽIVATEL: Vytvoří nového uživatele
     *
     * @return string
     */
    public static function insertUser(): string
    {
      return <<<SQL
INSERT INTO blog_users
  (
    username,
    surname,
    firstname,
    password_hash,
    email,
    mobil_number,
    benefit_card,
    role,
    registered_at
  )
VALUES (?,?,?,?,?,?,?,?,NOW())
SQL;
    }


    /**
     * UŽIVATEL: Upraví uživatele
     *
     * @return string
     */
    public static function updateUser(): string
    {
      return <<<SQL
UPDATE
  blog_users
SET
  surname=?,
  firstname=?,
  email=?,
  mobil_number=?,
  role=?,
  benefit_card=?,
  updated_at=NOW(),
  updated_by=?
WHERE
  id=?
SQL;
    }


    /**
     * UŽIVATEL: Upraví uživatele
     *
     * @return string
     */
    public static function updateUserPassword(): string
    {
      return <<<SQL
UPDATE
  blog_users
SET
  password_hash=?,
  password_recovery_pin=NULL,
  password_recovery_pin_created_at=NULL,
  password_recovery_pin_attempts=0,
  updated_by=?
WHERE
  id=?
SQL;
    }


    /**
     * UŽIVATEL: Smaže uživatele
     *
     * @return string
     */
    public static function deleteUser(): string
    {
      return <<<SQL
UPDATE blog_users SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=?
SQL;
    }


    /**
     * EVENTLOG: Vloží zápis do eventlogu
     *
     * @return string
     */
    public static function insertEvenlog(): string
    {
      return <<<SQL
INSERT INTO blog_eventlog (datetime,username,presenter,action,remote_ip) VALUES (NOW(),?,?,?,?);
SQL;
    }


    /**
     * EVENTLOG: Vybere všechny záznamy událostí
     *
     * @return string
     */
    public static function getEventlog(): string
    {
      return <<<SQL
SELECT * FROM blog_eventlog ORDER BY ID DESC LIMIT 0, 3500;
SQL;
    }


    /**
     * EVENTLOG: Vybere daný počet záznamů pro daný offset
     *
     * @return string
     */
    public static function getEventlogByPage($sql_limit): string
    {
      return <<<SQL
SELECT * FROM blog_eventlog ORDER BY ID DESC $sql_limit;
SQL;
    }


    /**
     * AMBULANCE: Vybere všechny objednávky
     *
     * @return string
     */
    public static function getAllOrders(): string
    {
      return <<<SQL
  SELECT
    a.*,
    b.surname,
    b.name,
    b.phone,
    b.unreliable
  FROM blog_orders AS a
  LEFT JOIN blog_patients AS b ON b.ID=a.ID_USER
  WHERE
    a.deleted=0
    AND b.deleted=0
  ORDER BY
    a.date,
    a.hour_from,
    a.min_from,
    a.hour_to,
    a.min_to,
    b.surname
 SQL;
    }


    /**
     * AMBULANCE: Vybere objednávky podle data od do
     *
     * @return string
     */
    public static function getOrders(): string
    {
      return <<<SQL
  SELECT
    a.*,
    b.surname,
    b.name,
    b.phone,
    b.unreliable
  FROM blog_orders AS a
  LEFT JOIN blog_patients AS b ON b.ID=a.ID_USER
  WHERE
    a.date>=?
    AND a.date <=?
    AND a.deleted=0
    AND b.deleted=0
  ORDER BY
    a.date,
    a.hour_from,
    a.min_from,
    a.hour_to,
    a.min_to,
    b.surname
 SQL;
    }


    /**
     * DIÁŘ: Vybere objednávky a události podle data od do
     *
     * @return string
     */
    public static function getDiaryEvents(): string
    {
      return <<<SQL
SELECT
  a.`ID` AS `ID`,
  a.`lekce_id` AS `lekce_id`,
  a.`aktivita_id` AS `aktivita_id`,
  a.`nazev` AS `nazev`,
  a.`popis` AS `popis`,
  a.`lektor_id` AS `lektor_id`,
  a.`lektor` AS `lektor`,
  a.`date` AS `date`,
  a.`hour_from` AS `hour_from`,
  a.`min_from` AS `min_from`,
  a.`hour_to` AS `hour_to`,
  a.`min_to` AS `min_to`,
  a.`desc` AS `desc`,
  a.`color` AS `color`,
  b.`nazev` AS `aktivita_nazev`,
  b.`vstupy_min` AS `aktivita_vstupy_min`,
  b.`vstupy_max` AS `aktivita_vstupy_max`,
  COALESCE(c.`total`, 0) AS `aktivita_vstupy_aktualni`,
  COALESCE(n.`total`, 0) AS `aktivita_nahradnici_aktualni`,
  b.`zruseni_zdarma` AS `aktivita_zruseni_zdarma`,
  b.`zruseni_zdarma_ts` AS `aktivita_zruseni_zdarma_ts`,
  b.`zruseni_neucast` AS `aktivita_zruseni_neucast`,
  b.`zruseni_neucast_ts` AS `aktivita_zruseni_neucast_ts`,
  b.`registrace_konec` AS `aktivita_registrace_konec`,
  b.`registrace_konec_ts` AS `aktivita_registrace_konec_ts`
FROM
  `blog_diary` AS a
LEFT JOIN `blog_activity` AS b
  ON b.`id` = a.`aktivita_id`
LEFT JOIN (
  SELECT
    diary_id,
    COUNT(*) AS total
  FROM
    blog_registration
  WHERE
    deleted = 0
    AND registration_status = 'ucastnik'
  GROUP BY
    diary_id
) AS c
  ON c.`diary_id` = a.`ID`
LEFT JOIN (
  SELECT
    diary_id,
    COUNT(*) AS total
  FROM
    blog_registration
  WHERE
    deleted = 0
    AND registration_status = 'nahradnik'
  GROUP BY
    diary_id
) AS n
  ON n.`diary_id` = a.`ID`
WHERE
  a.`date` >= ?
  AND a.`date` <=?
  AND a.`deleted` = 0
  AND b.`deleted` = 0
ORDER BY
  a.`date`,
  a.`hour_from`,
  a.`min_from`,
  a.`hour_to`,
  a.`min_to`
SQL;
    }


    /**
     * LEKCE: Vrací počet registrací na lekci
     *
     * @return string
     */
    public static function getLekceUcast(): string
    {
      return <<<SQL
SELECT
  diary_id,
  COUNT(*) AS total
FROM
  `blog_registration`
WHERE
  diary_id = ?
  AND deleted = 0
  AND registration_status = 'ucastnik'
GROUP BY
  diary_id
SQL;
    }

    /**
     * AMBULANCE: Vybere všechny objednávky podle ID pacienta
     *
     * @return string
     */
    public static function getOrdersByPatientId(): string
    {
      return <<<SQL
  SELECT
    *
  FROM blog_orders
  WHERE
    ID_USER=?
    AND deleted=0
  ORDER BY
    date,
    hour_from,
    min_from
 SQL;
    }


    /**
     * AMBULANCE: Vybere objednávky podle ID
     *
     * @return string
     */
    public static function getOrder(): string
    {
      return <<<SQL
  SELECT
    a.*,
    b.surname,
    b.name,
    b.phone,
    b.unreliable
  FROM blog_orders AS a
  LEFT JOIN blog_patients AS b ON b.ID=a.ID_USER
  WHERE
    a.ID=?
 SQL;
    }


    /**
     * DIÁŘ: Vybere událost diáře podle ID
     *
     * @return string
     */
    public static function getDiary(): string
    {
      return <<<SQL
  SELECT
    *
  FROM blog_diary
  WHERE
    ID=?
 SQL;
    }


    /**
     * AMBULANCE: Vybere všechny pacienty
     *
     * @return string
     */
    public static function getAllPatients(): string
    {
      return <<<SQL
 SELECT * FROM blog_patients WHERE unreliable>=? AND deleted=0 ORDER BY surname,name,phone COLLATE 'utf8mb4_czech_ci'
 SQL;
    }


    /**
     * AMBULANCE: Vybere všechny pacienty podle příjmení
     *
     * @return string
     */
    public static function getAllPatientsBySurname(): string
    {
      return <<<SQL
 SELECT * FROM blog_patients WHERE surname LIKE ? AND unreliable>=? AND deleted=0 ORDER BY surname,name,phone COLLATE 'utf8mb4_czech_ci'
 SQL;
    }


    /**
     * AMBULANCE: Vybere příjmení všech pacientů
     *
     * @return string
     */
    public static function getAllPatientSurnames(): string
    {
      return <<<SQL
 SELECT ID,surname,name,phone FROM blog_patients WHERE deleted=0 ORDER BY surname,name,phone COLLATE 'utf8mb4_czech_ci'
 SQL;
    }


    /**
     * AMBULANCE: Vybere data pacienta podle ID
     *
     * @return string
     */
    public static function getPatient(): string
    {
      return <<<SQL
 SELECT * FROM blog_patients WHERE ID=? AND deleted=0
 SQL;
    }


    /**
     * AMBULANCE: Vloží novou objednávku
     *
     * @return string
     */
    public static function insertOrder(): string
    {
      return <<<SQL
INSERT INTO `blog_orders` (`ID_USER`,`date`,`hour_from`,`min_from`,`hour_to`,`min_to`,`desc`,`created_by`) VALUES (?,?,?,?,?,?,?,?);
SQL;
    }


    /**
     * DIÁŘ: Vloží novou událost
     *
     * @return string
     */
    public static function insertDiary(): string
    {
      return <<<SQL
INSERT INTO `blog_diary`
(
  `lekce_id`,
  `aktivita_id`,
  `nazev`,
  `popis`,
  `lektor_id`,
  `lektor`,
  `date`,
  `hour_from`,
  `min_from`,
  `hour_to`,
  `min_to`,
  `desc`,
  `color`,
  `created_by`
)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?);
SQL;
    }


    /**
     * AMBULANCE: Smaže objednávku
     *
     * @return string
     */
    public static function deleteOrder(): string
    {
      return <<<SQL
UPDATE `blog_orders` SET `deleted`=1,`deleted_at`=NOW(),`deleted_by`=? WHERE `ID`=?
SQL;
    }


    /**
     * DIÁŘ: Smaže událost
     *
     * @return string
     */
    public static function deleteDiary(): string
    {
      return <<<SQL
UPDATE `blog_diary` SET `deleted`=1,`deleted_at`=NOW(),`deleted_by`=? WHERE `ID`=?
SQL;
    }


    /**
     * DIÁŘ: Smaže událost
     *
     * @return string
     */
    public static function deleteDiaryByLekceId(): string
    {
      return <<<SQL
UPDATE
  `blog_diary`
SET
  `deleted`=1,
  `deleted_at`=NOW(),
  `deleted_by`=?
WHERE
  `lekce_id`=?
  AND `date`>=?
SQL;
    }


    /**
     * AMBULANCE: Upraví objednávku
     *
     * @return string
     */
    public static function updateOrder(): string
    {
      return <<<SQL
UPDATE `blog_orders` SET `ID_USER`=?,`date`=?,`hour_from`=?,`min_from`=?,`hour_to`=?,`min_to`=?,`desc`=?,`updated_at`=NOW(),`updated_by`=? WHERE `ID`=?
SQL;
    }


    /**
     * DIÁŘ: Upraví událost
     *
     * @return string
     */
    public static function updateDiary(): string
    {
      return <<<SQL
UPDATE
  `blog_diary`
SET
  `aktivita_id`=?,
  `nazev`=?,
  `popis`=?,
  `lektor_id`=?,
  `date`=?,
  `hour_from`=?,
  `min_from`=?,
  `hour_to`=?,
  `min_to`=?,
  `desc`=?,
  `color`=?,
  `updated_at`=NOW(),
  `updated_by`=?
WHERE
  `ID`=?
SQL;
    }


    /**
     * DIÁŘ: Upraví událost
     *
     * @return string
     */
    public static function updateDiaryByLekceId(): string
    {
      return <<<SQL
UPDATE
  `blog_diary`
SET
  `aktivita_id`=?,
  `nazev`=?,
  `popis`=?,
  `lektor_id`=?,
  `hour_from`=?,
  `min_from`=?,
  `hour_to`=?,
  `min_to`=?,
  `desc`=?,
  `color`=?,
  `updated_at`=NOW(),
  `updated_by`=?
WHERE
  `lekce_id`=?
  AND `ID`>=?
SQL;
    }


    /**
     * AMBULANCE: Vloží novou objednávku
     *
     * @return string
     */
    public static function insertPatient(): string
    {
      return <<<SQL
INSERT INTO `blog_patients` (`surname`,`name`,`phone`,`created_by`,`unreliable`) VALUES (?,?,?,?,?);
SQL;
    }


    /**
     * AMBULANCE: Smaže kontakt pacienta
     *
     * @return string
     */
    public static function deletePatient(): string
    {
      return <<<SQL
UPDATE `blog_patients` SET `deleted`=1,`deleted_at`=NOW(),`deleted_by`=? WHERE `ID`=?
SQL;
    }


    /**
     * AMBULANCE: Upraví kontakt pacienta
     *
     * @return string
     */
    public static function updatePatient(): string
    {
      return <<<SQL
UPDATE `blog_patients` SET `surname`=?,`name`=?,`phone`=?,`updated_at`=NOW(),`updated_by`=?,`unreliable`=? WHERE `ID`=?
SQL;
    }


    /**
     * DEBTS: Vybere všechny záznamy pohledávky / zakázky
     *
     * @return string
     */
    public static function getAllDebts(): string
    {
      return <<<SQL
SELECT * FROM blog_debts WHERE deleted=0 ORDER BY created_at DESC
SQL;
    }


    /**
     * DEBT: Vybere všechny záznamy pohledávky / zakázky podle typu
     *
     * @return string
     */
    public static function getAllDebtsByType(): string
    {
      return <<<SQL
SELECT * FROM blog_debts WHERE type=? AND deleted=0 ORDER BY created_at DESC
SQL;
    }


    /**
     * DEBT: Vloží nový záznam pohledávky / zakázky
     *
     * @return string
     */
    public static function insertDebt(): string
    {
      return <<<SQL
INSERT INTO `blog_debts` (`debt`,`description`,`amount`,`type`,`created_by`) VALUES (?,?,?,?,?);
SQL;
    }


    /**
     * DEBT: Upraví záznam pohledávky / zakázky
     *
     * @return string
     */
    public static function updateDebt(): string
    {
      return <<<SQL
UPDATE `blog_debts` SET `debt`=?,`description`=?,`amount`=?,`type`=?,`updated_at`=NOW(),`updated_by`=? WHERE `ID`=?
SQL;
    }


    /**
     * DEBT: Smaže záznam pohledávky / zakázky
     *
     * @return string
     */
    public static function deleteDebt(): string
    {
      return <<<SQL
UPDATE `blog_debts` SET `deleted`=1,`deleted_at`=NOW(),`deleted_by`=? WHERE `ID`=?
SQL;
    }


    /**
     * Získá seznam položek ze sloupce typu 'ENUM'
     *
     * @return string
     */
    public static function getListFromEnum(): string
    {
      return <<<SQL
SELECT
  COLUMN_TYPE
FROM
  INFORMATION_SCHEMA.COLUMNS
WHERE
  TABLE_SCHEMA = ?
  AND TABLE_NAME = ?
  AND COLUMN_NAME = ?
  AND DATA_TYPE IN ('enum')
SQL;
    }


    /**
     * PRODEJ: Zobrazí nákupy za aktivitu
     *
     *  - seskupení podle user_id a aktivita_id,
     *  - v každé skupině jsou řádky seřazené podle datum_prodeje ASC
     *  - počítají dvě sumy: SUM(vstupy_celkem) a SUM(vstupy_aktualni) v rámci skupiny,
     *
     * @return string
     */
    public static function getNakup(): string
    {
      return <<<SQL
SELECT
  user_id,
  aktivita_id,
  aktivita_name,
  ID,
  datum_prodeje,
  datum_konce,
  vstupy_celkem,
  vstupy_aktualni,
  SUM(vstupy_celkem) OVER (PARTITION BY user_id, aktivita_id) AS vstupy_celkem_skupina,
  SUM(vstupy_aktualni) OVER (PARTITION BY user_id, aktivita_id) AS vstupy_aktualni_skupina,
  created_by
FROM
  blog_sales
WHERE
  user_id = ?
  AND datum_konce >= ?
  AND COALESCE(deleted, 0) = 0
ORDER BY
  user_id,
  aktivita_id,
  datum_prodeje ASC
SQL;
    }


    /**
     * DIÁŘ: Vrací informaci o lekci podle ID lekce v diáři
     *
     * @return string
     */
    public static function getLekceById(): string
    {
      return <<<SQL
SELECT
  a.`ID` AS `ID`,
  a.`lekce_id` AS `lekce_id`,
  a.`aktivita_id` AS `aktivita_id`,
  a.`nazev` AS `nazev`,
  a.`popis` AS `popis`,
  a.`lektor_id` AS `lektor_id`,
  a.`lektor` AS `lektor`,
  a.`date` AS `date`,
  a.`hour_from` AS `hour_from`,
  a.`min_from` AS `min_from`,
  a.`hour_to` AS `hour_to`,
  a.`min_to` AS `min_to`,
  a.`desc` AS `desc`,
  a.`color` AS `color`,
  b.`nazev` AS `aktivita_nazev`,
  b.`vstupy_min` AS `aktivita_vstupy_min`,
  b.`vstupy_max` AS `aktivita_vstupy_max`,
  COALESCE(c.`total`, 0) AS `aktivita_vstupy_aktualni`,
  COALESCE(n.`total`, 0) AS `aktivita_nahradnici_aktualni`,
  b.`zruseni_zdarma` AS `aktivita_zruseni_zdarma`,
  b.`zruseni_zdarma_ts` AS `aktivita_zruseni_zdarma_ts`,
  b.`zruseni_neucast` AS `aktivita_zruseni_neucast`,
  b.`zruseni_neucast_ts` AS `aktivita_zruseni_neucast_ts`,
  b.`registrace_konec` AS `aktivita_registrace_konec`,
  b.`registrace_konec_ts` AS `aktivita_registrace_konec_ts`
FROM
  `blog_diary` AS a
LEFT JOIN `blog_activity` AS b
  ON b.`id` = a.`aktivita_id`
LEFT JOIN (
  SELECT
    diary_id,
    COUNT(*) AS total
  FROM
    blog_registration
  WHERE
    deleted = 0
    AND registration_status = 'ucastnik'
  GROUP BY
    diary_id
) AS c
  ON c.`diary_id` = a.`ID`
LEFT JOIN (
  SELECT
    diary_id,
    COUNT(*) AS total
  FROM
    blog_registration
  WHERE
    deleted = 0
    AND registration_status = 'nahradnik'
  GROUP BY
    diary_id
) AS n
  ON n.`diary_id` = a.`ID`
WHERE
  a.`ID` = ?
  AND a.`deleted` = 0
  AND b.`deleted` = 0
SQL;
    }


    /**
     * REGISTRACE: Vloží registraci na lekci
     *
     * @return string
     */
    public static function insertRegistrace(): string
    {
      return <<<SQL
INSERT IGNORE INTO blog_registration
(
  user_id,
  diary_id,
  aktivita_id,
  registration_status,
  created_at,
  created_by,
  sales_id
)
VALUES
  (?,?,?,?,NOW(),?,?);
SQL;
    }


    /**
     * REGISTRACE: Zruší registraci na lekci
     *
     * @return string
     */
    public static function deleteRegistrace(): string
    {
      return <<<SQL
UPDATE blog_registration
SET
  `deleted`=1,
  `deleted_at`=NOW(),
  `deleted_by`=?
WHERE
  `user_id`=?
  AND `diary_id`=?
  AND `deleted`=0;
SQL;
    }


    /**
     * REGISTRACE: Zruší konkrétní registraci na lekci
     *
     * @return string
     */
    public static function deleteRegistraceByID(): string
    {
      return <<<SQL
UPDATE blog_registration
SET
  `deleted`=1,
  `deleted_at`=NOW(),
  `deleted_by`=?
WHERE
  `ID`=?
  AND `user_id`=?
  AND `diary_id`=?
  AND `deleted`=0;
SQL;
    }


    /**
     * REGISTRACE: Ochrani pocitadlo obsazenosti pred odectem stareho triggeru pri ruseni nahradnika
     *
     * @return string
     */
    public static function protectLekceUcastBeforeSubstituteDelete(): string
    {
      return <<<SQL
INSERT INTO mv_lekce_ucast (diary_id, total)
VALUES (?, 1)
ON DUPLICATE KEY UPDATE total = total + 1
SQL;
    }


    /**
     * REGISTRACE: Prepocita pocitadlo obsazenosti lekce podle aktivnich ucastniku
     *
     * @return string
     */
    public static function refreshLekceUcast(): string
    {
      return <<<SQL
INSERT INTO mv_lekce_ucast (diary_id, total)
SELECT
  ? AS diary_id,
  COUNT(*) AS total
FROM
  blog_registration
WHERE
  diary_id = ?
  AND deleted = 0
  AND registration_status = 'ucastnik'
ON DUPLICATE KEY UPDATE total = VALUES(total)
SQL;
    }


    /**
     * REGISTRACE: Kontrouje registraci klienta na lekci
     *
     * @return string
     */
    public static function checkIsUserIsRegistered(): string
    {
      return <<<SQL
SELECT
  COUNT(`diary_id`) AS `pocet`,
  MAX(`registration_status`) AS `registration_status`
FROM
  `blog_registration`
WHERE
  `user_id`=?
  AND `diary_id`=?
  AND `deleted` = 0
SQL;
    }


    /**
     * REGISTRACE: Vrací ID permanentky
     *
     * @return string
     */
    public static function getSalesId(): string
    {
      return <<<SQL
SELECT
  `ID` AS `registration_id`,
  `sales_id`,
  `registration_status`
FROM
  `blog_registration`
WHERE
  `user_id`=?
  AND `diary_id`=?
  AND `deleted` = 0
SQL;
    }


    /**
     * REGISTRACE: Vrací počet účastníků a náhradníků lekce
     *
     * @return string
     */
    public static function getRegistrationCounts(): string
    {
      return <<<SQL
SELECT
  COUNT(CASE WHEN `deleted` = 0 AND `registration_status` = 'ucastnik' THEN 1 END) AS `participants`,
  COUNT(CASE WHEN `deleted` = 0 AND `registration_status` = 'nahradnik' THEN 1 END) AS `substitutes`
FROM
  `blog_registration`
WHERE
  `diary_id` = ?
SQL;
    }


    /**
     * REGISTRACE: Vrací prvního náhradníka lekce
     *
     * @return string
     */
    public static function getFirstSubstituteRegistration(): string
    {
      return <<<SQL
SELECT
  a.`ID` AS `registration_id`,
  a.`user_id` AS `user_id`,
  a.`diary_id` AS `diary_id`,
  a.`aktivita_id` AS `aktivita_id`,
  a.`registration_status` AS `registration_status`,
  a.`sales_id` AS `sales_id`,
  a.`created_at` AS `created_at`,
  b.`nazev` AS `diary_nazev`,
  b.`date` AS `diary_date`,
  b.`hour_from` AS `diary_hour_from`,
  b.`min_from` AS `diary_min_from`,
  DATE_FORMAT(STR_TO_DATE(b.`date`, '%Y%m%d'), '%d.%m.%Y') AS `lekce_datum`,
  TIME_FORMAT(MAKETIME(b.`hour_from`, b.`min_from`, 0), '%H:%i') AS `lekce_cas`,
  c.`surname` AS `user_surname`,
  c.`firstname` AS `user_firstname`,
  c.`mobil_number` AS `user_mobil_number`
FROM
  `blog_registration` AS a
LEFT JOIN
  `blog_diary` AS b ON b.`ID` = a.`diary_id`
LEFT JOIN
  `blog_users` AS c ON c.`id` = a.`user_id`
WHERE
  a.`diary_id` = ?
  AND a.`deleted` = 0
  AND a.`registration_status` = 'nahradnik'
  AND b.`deleted` = 0
  AND c.`deleted` = 0
ORDER BY
  a.`created_at`,
  a.`ID`
LIMIT 1
SQL;
    }


    /**
     * REGISTRACE: Povýší náhradníka mezi účastníky lekce
     *
     * @return string
     */
    public static function promoteSubstituteToParticipant(): string
    {
      return <<<SQL
UPDATE blog_registration
SET
  `registration_status` = 'ucastnik',
  `updated_at` = NOW(),
  `updated_by` = ?
WHERE
  `ID` = ?
  AND `diary_id` = ?
  AND `deleted` = 0
  AND `registration_status` = 'nahradnik'
SQL;
    }


    /**
     * REGISTRACE: Vrací seznam registrací klienta podle jeho user ID
     *
     * @return string
     */
    public static function getRegistraceByUserID(): string
    {
      return <<<SQL
SELECT
  a.`ID` AS `ID`,
  a.`user_id` AS `user_id`,
  a.`diary_id` AS `diary_id`,
  a.`aktivita_id` AS `aktivita_id`,
  a.`registration_status` AS `registration_status`,
  b.`nazev` AS `lekce_nazev`,
  b.`date` AS `lekce_date`,
  DATE_FORMAT(STR_TO_DATE(b.`date`, '%Y%m%d'), '%d.%m.%Y') AS `lekce_datum`,
  UNIX_TIMESTAMP(STR_TO_DATE(b.`date`, '%Y%m%d')) AS `lekce_ts`,
  b.`hour_from` AS `lekce_hour_from`,
  b.`min_from` AS `lekce_min_from`,
  TIME_FORMAT(MAKETIME(b.`hour_from`, b.`min_from`, 0), '%H:%i') AS `lekce_cas`,
  b.`lektor_id` AS `lektor_id`
FROM
  `blog_registration` AS a
LEFT JOIN
  `blog_diary` AS b ON a.`diary_id` = b.`ID`
WHERE
  a.`user_id`=?
  AND a.`deleted`=0
ORDER BY
  b.`date`,
  b.`hour_from`,
  b.`min_from`
SQL;
    }


    /**
     * REGISTRACE: Vrací registraci podle jejího ID
     *
     * @return string
     */
    public static function getRegistraceByID(): string
    {
      return <<<SQL
SELECT
  a.`ID` AS `registration_id`,
  a.`user_id` AS `user_id`,
  a.`diary_id` AS `diary_id`,
  a.`aktivita_id` AS `aktivita_id`,
  a.`registration_status` AS `registration_status`,
  a.`sales_id` AS `sales_id`,
  b.`date` AS `diary_date`,
  b.`hour_from` AS `hour_from`,
  b.`min_from` AS `min_from`,
  d.`zruseni_zdarma_ts` AS `zruseni_zdarma_ts`
FROM
  `blog_registration` AS a
LEFT JOIN
  `blog_diary` AS b ON b.`ID` = a.`diary_id`
LEFT JOIN
  `blog_activity` AS d ON d.`id` = a.`aktivita_id`
WHERE
  a.`ID` = ?
  AND a.`deleted` = 0
  AND b.`deleted` = 0
  AND d.`deleted` = 0
SQL;
    }


	    /**
     * LEKCE: Vrací detail lekce podle diary_id
     *
     * @return string
     */
    public static function getLekceDetail(): string
    {
      return <<<SQL
SELECT
  a.`ID` as `registrace_id`,
  a.`registration_status` as `registration_status`,
  a.`ucast` as `registrace_ucast`,
  a.`desc` as `registrace_desc`,
  a.`created_at` as `registrace_created_at`,
  a.`created_by` as `registrace_created_by`,
  a.`deleted` as `registrace_deleted`,
  a.`deleted_at` as `registrace_deleted_at`,
  a.`deleted_by` as `registrace_deleted_by`,
  a.`sales_id` as `sales_id`,
  b.`ID` as `diary_id`,
  b.`nazev` as `diary_nazev`,
  b.`popis` as `diary_popis`,
  b.`date` as `diary_date`,
  b.`hour_from` as `diary_hour_from`,
  b.`min_from` as `diary_min_from`,
  b.`hour_to` as `diary_hour_to`,
  b.`min_to` as `diary_`,
  b.`desc` as `diary_desc`,
  c.`id` as `user_id`,
  c.`surname` as `user_surname`,
  c.`firstname` as `user_firstname`,
  c.`email` as `user_email`,
  c.`mobil_number` as `user_mobil_number`,
  d.`id` as `aktivita_id`,
  d.`nazev` as `aktivita_nazev`,
  d.`vstupy_min` as `aktivita_vstupy_min`,
  d.`vstupy_max` as `aktivita_vstupy_max`,
  d.`zruseni_zdarma` as `aktivita_zruseni_zdarma`,
  d.`zruseni_zdarma_ts` as `aktivita_zruseni_zdarma_ts`,
  d.`zruseni_neucast` as `aktivita_zruseni_neucast`,
  d.`zruseni_neucast_ts` as `aktivita_zruseni_neucast_ts`,
  d.`registrace_konec` as `aktivita_registrace_konec`,
  d.`registrace_konec_ts` as `aktivita_registrace_konec_ts`,
  e.`id` as `lektor_id`,
  e.`surname` as `lektor_surname`,
  e.`firstname` as `lektor_firstname`,
  UNIX_TIMESTAMP(STR_TO_DATE(b.`date`, '%Y%m%d')) AS `ts_diary_date`
FROM
  `blog_registration`AS a
LEFT JOIN
  `blog_diary` AS b ON b.`ID` = a.`diary_id`
 LEFT JOIN
  `blog_users` AS c ON a.`user_id` = c.`id`
LEFT JOIN
  `blog_activity` AS d ON b.`aktivita_id` = d.`id`
LEFT JOIN
  `blog_users` AS e ON b.`lektor_id` = e.`id`
WHERE
  b.`ID` = ?
  AND c.`id` > 0
ORDER BY
  a.`deleted`,
  CASE WHEN a.`registration_status` = 'ucastnik' THEN 0 ELSE 1 END,
  CASE WHEN a.`registration_status` = 'nahradnik' THEN a.`created_at` END,
  CASE WHEN a.`registration_status` = 'nahradnik' THEN a.`ID` END,
  a.ID desc
SQL;
    }


    /**
     * LEKCE: Vrací informaci lekce podle diary_id
     *
     * @return string
     */
    public static function getLekceInfo(): string
    {
      return <<<SQL
SELECT
  b.`ID` as `diary_id`,
  b.`nazev` as `diary_nazev`,
  b.`popis` as `diary_popis`,
  b.`date` as `diary_date`,
  b.`hour_from` as `diary_hour_from`,
  b.`min_from` as `diary_min_from`,
  b.`hour_to` as `diary_hour_to`,
  b.`min_to` as `diary_`,
  b.`desc` as `diary_desc`,
  d.`id` as `aktivita_id`,
  d.`nazev` as `aktivita_nazev`,
  d.`vstupy_min` as `aktivita_vstupy_min`,
  d.`vstupy_max` as `aktivita_vstupy_max`,
  d.`zruseni_zdarma` as `aktivita_zruseni_zdarma`,
  d.`zruseni_zdarma_ts` as `aktivita_zruseni_zdarma_ts`,
  d.`zruseni_neucast` as `aktivita_zruseni_neucast`,
  d.`zruseni_neucast_ts` as `aktivita_zruseni_neucast_ts`,
  d.`registrace_konec` as `aktivita_registrace_konec`,
  d.`registrace_konec_ts` as `aktivita_registrace_konec_ts`,
  e.`id` as `lektor_id`,
  e.`surname` as `lektor_surname`,
  e.`firstname` as `lektor_firstname`,
  UNIX_TIMESTAMP(STR_TO_DATE(b.`date`, '%Y%m%d')) AS `ts_diary_date`,
  DATE_FORMAT(STR_TO_DATE(b.`date`, '%Y%m%d'), '%d.%m.%Y') AS `lekce_datum`,
  TIME_FORMAT(MAKETIME(b.`hour_from`, b.`min_from`, 0), '%H:%i') AS `lekce_cas_od`,
  TIME_FORMAT(MAKETIME(b.`hour_to`, b.`min_to`, 0), '%H:%i') AS `lekce_cas_do`

FROM
  `blog_diary` as b
LEFT JOIN
  `blog_activity` AS d ON b.`aktivita_id` = d.`id`
LEFT JOIN
  `blog_users` AS e ON b.`lektor_id` = e.`id`
WHERE
  b.`ID` = ?
SQL;
    }


    /**
     * LEKCE: Upraví potvrzení účasti klienta na lekci
     *
     * @return string
     */
    public static function updateUcast(): string
    {
      return <<<SQL
UPDATE blog_registration
SET
  `ucast`= ?,
  `updated_at` = NOW(),
  `updated_by` = ?
 WHERE
   `ID`=?
SQL;
    }
  }
