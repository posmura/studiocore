<?php

  namespace App\Model;

  class SqlCommands
  {


    /**
     * KREDITY: Seznam permanentek pro načtení a úpravu kreditů
     *
     * @return string
     */
    public static function getPermanentkaProKredit(): string
    {
      return <<<SQL
SELECT *
FROM
  `blog_sales`
WHERE
  `user_id`=?
  AND `aktivita_id` = ?
  AND `datum_konce` >= ?
  AND `deleted` = 0
ORDER BY
  `datum_konce` ASC,
  `vstupy_celkem` ASC
SQL;
    }


    /**
     * KREDITY: Seznam aktivních permanentek pro načtení a úpravu kreditů
     *
     * Pozn.: Aktivní permanentka je ta, která má aktuální počet kreditů > 0 a konec platnosti <= aktuální den
     *
     * @return string
     */
    public static function getAktivniPermanentkyID(): string
    //public static function getPermanentkaAktivni(): string
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
  `vstupy_celkem` ASC,
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
     * KREDITY: Upraví kredity klienta při změně registrace (přihlášení/odhlášení na/z lekce)
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
     * KREDITY: Upraví aktualní vstupy na aktuální pemanentce klienta při změně registrace (přihlášení/odhlášení na/z lekce)
     *
     * @return string
     */
    public static function updateKredityAktivniPermanentka(): string
    {
      return <<<SQL
UPDATE blog_sales
SET
  `vstupy_aktualni`= `vstupy_aktualni`+ ?,
  `updated_at`=NOW(),
  `updated_by`=?
 WHERE
   ID=?
   AND deleted=0
SQL;
    }


    /**
     * KREDITY: Aktualizuje kredity klienta při načtení stavu registrací
     *
     * @return string
     */
    public static function refreshKredityKlienta(): string
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
     * PRODEJ: Upraví prodej
     *
     * @return string
     */
    public static function updateProdej(): string
    {
      return <<<SQL
UPDATE blog_sales
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
UPDATE blog_sales SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=?
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
SELECT * FROM blog_membership_card WHERE id=?
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
     * PRODEJ: Vybere permanentku a odpovídající aktivitu
     *
     * @return string
     */
    public static function getPermanentkaActivita(): string
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
  AND a.id = ?
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
  AND a.id = ?
SQL;
    }


    /**
     * PERMANENTKA: Vybere permanentku podle ID
     *
     * @return string
     */
    public static function getUsersById(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE id=? AND deleted=0
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
UPDATE blog_membership_card SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=?
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
UPDATE blog_activity SET deleted=1,deleted_at=NOW(),deleted_by=? WHERE id=?
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
     * UŽIVATEL: Vybere uživatele podle uživatelského jména a e-malové adresy
     *
     * @return string
     */
    public static function getUserByEmail(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE username=? AND email=? AND deleted=0
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
SELECT * FROM blog_users WHERE username=? AND password_recovery_pin=? AND deleted=0
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
UPDATE blog_users SET password_recovery_pin=?, updated_by=?, updated_at=NOW() WHERE id=?
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
UPDATE blog_users SET password_hash=?, password_recovery_pin=NULL, updated_by=?, updated_at=NOW() WHERE id=?
SQL;
    }


    public static function xxxgetUser(): string
    {
      return <<<SQL
SELECT * FROM blog_users WHERE username=? AND password_hash=? AND deleted=0
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
    public static function XXXupdateUser(): string
    {
      return <<<SQL
UPDATE blog_users SET fullname=?,password_hash=? WHERE id=?
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
     * EVENTLOG: Vrací počet všech záznamů v tabulce eventlogu
     *
     * @return string
     */
    public static function getCountEventlog(): string
    {
      return <<<SQL
SELECT COUNT(*) AS pocet FROM blog_eventlog;
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
LEFT JOIN `mv_lekce_ucast` AS c
  ON c.`diary_id` = a.`ID`
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
  *
FROM
  `mv_lekce_ucast`
WHERE
  lekce_id = ?
SQL;
    }


    public static function XXXgetDiaryEvents(): string
    {
      return <<<SQL
SELECT
    d.*
FROM
    (
  SELECT
    a.`ID`,
    a.`date`,
    a.`hour_from`,
    a.`min_from`,
    a.`hour_to`,
    a.`min_to`,
    a.`desc`,
    b.`surname`,
    b.`name`,
    b.`phone`,
    'order' AS event_type
  FROM blog_orders AS a
  LEFT JOIN blog_patients AS b ON b.ID=a.ID_USER
  WHERE
    a.date>=?
    AND a.date <=?
    AND a.deleted=0
    AND b.deleted=0

    UNION ALL

    SELECT
      `ID`,
      `date`,
      `hour_from`,
      `min_from`,
      `hour_to`,
      `min_to`,
      `desc`,
      '' AS surname,
      '' AS name,
      '' AS phone,
      'event' AS event_type
    FROM
      `blog_diary`
    WHERE
      `date` >= ?
      AND `date` <=?
      AND `deleted` = 0

) AS d

ORDER BY
    d.date,
    d.hour_from,
    d.min_from,
    d.hour_to,
    d.min_to
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
     * DEBT: Vybere záznam pohledávky / zakázky podle ID
     *
     * @return string
     */
    public static function getAllDebtsByID(): string
    {
      return <<<SQL
SELECT * FROM blog_debts WHERE ID=? AND deleted=0 ORDER BY created_at DESC
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
  SUM(vstupy_aktualni) OVER (PARTITION BY user_id, aktivita_id) AS vstupy_aktualni_skupina
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
     *
     * @return string
     */
    public static function getPermitkyByUserAktivita(): string
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
  vstupy_aktualni
FROM
  blog_sales
WHERE
  user_id = ?
  AND aktivita_id = ?
  AND datum_konce >= ?
  AND COALESCE(deleted, 0) = 0
ORDER BY
  datum_konce ASC
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
LEFT JOIN `mv_lekce_ucast` AS c
  ON c.`diary_id` = a.`ID`
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
INSERT INTO blog_registration
(
  `user_id`,
  `diary_id`,
  `aktivita_id`,
  `created_at`,
  `created_by`,
  `sales_id`

)
VALUES
  (?,?,?,NOW(),?,?);
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
  AND `diary_id`=?;
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
  COUNT(`diary_id`) AS `pocet`
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
  `sales_id`
FROM
  `blog_registration`
WHERE
  `user_id`=?
  AND `diary_id`=?
  AND `deleted` = 0
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
  }
