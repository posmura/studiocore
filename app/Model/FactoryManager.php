<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;
  use Nette\Utils\Arrays;

  /**
   * Správce factory
   */
  class FactoryManager extends DatabaseManager
  {


    /**
     * POST: Vrací všechny příspěvky
     *
     * @return array
     */
    public function getAllOrders(): array
    {
      return $this->database->fetchAll(SqlCommands::getAllOrders());
    }


    /**
     * POST: Vrací všechny objednávky podle data od do
     *
     * @param $dateFrom Datum objednýávky od
     * @param int $dateTo Datum objednýávky do
     * @return array
     */
    public function getOrders($dateFrom,$dateTo = null): array
    {
      if (!$dateTo)
        $dateTo = $dateFrom;

      return $this->database->fetchAll(SqlCommands::getOrders(),$dateFrom,$dateTo);
    }


    /**
     * DIÁŘ: Vrací informaci o lekci podle ID lekce v diáři
     *
     * @param int $ID ID lekce
     * @return array
     */
    public function getLekceById($ID)
    {
      return $this->database->fetch(SqlCommands::getLekceById(),$ID);
    }


    /**
     * DIÁŘ: Vrací všechny objednávky a události podle data od do
     *
     * @param $dateFrom Datum objednýávky od
     * @param int $dateTo Datum objednýávky do
     * @return array
     */
    public function getDiaryEvents($dateFrom,$dateTo = null): array
    {
      if (!$dateTo)
        $dateTo = $dateFrom;

      $rst = $this->database->fetchAll(SqlCommands::getDiaryEvents(),$dateFrom,$dateTo);

      // upravím data o vlastní hdonoty
      foreach ($rst as $key => $item)
      {
        $rst[$key]['ts_now'] = time();

        $dateString = sprintf('%s %s',$rst[$key]['date'],$this->formatTime($rst[$key]['hour_from'],$rst[$key]['min_from']));
        $dt = \DateTime::createFromFormat("Ymd H:i",$dateString);
        $rst[$key]['ts_event'] = $dt->getTimestamp();
      }

      return $rst;
    }


    /**
     * LEKCE: Vrací počet registrací pro danou lekci
     *
     * @param int $lekce_id
     * @return type
     */
    public function getLekceUcast(int $lekce_id = 0)
    {
      return $this->database->fetch(SqlCommands::getLekceUcast(),$lekce_id);
    }


    /**
     * POST: Vrací všechny objednávky podle ID pacienta
     *
     * @param int $idUser ID pacienta
     * @return array
     */
    public function getOrdersByPatientId($idUser): array
    {
      return $this->database->fetchAll(SqlCommands::getOrdersByPatientId(),$idUser);
    }


    /**
     * POST: Vrací všechny pacienty
     *
     * @param int $unreliable Příznak nespolehlivého pacienta 0=spolehlivý, 1=nespolehlivý
     * @return array
     */
    public function getAllPatients($unreliable): array
    {
      return $this->database->fetchAll(SqlCommands::getAllPatients(),$unreliable);
    }


    /**
     * POST: Vrací příjmení všech pacientů podle řetězce příjmení
     *
     * @param string $find Vzorek řetězce příjmení
     * @param int $unreliable Příznak nespolehlivého pacienta 0=spolehlivý, 1=nespolehlivý
     * @return array
     */
    public function getAllPatientsBySurname($find = '',$unreliable = 0): array
    {
      return $this->database->fetchAll(SqlCommands::getAllPatientsBySurname(),$find,$unreliable);
    }


    /**
     * POST: Vrací příjmení všech pacientů
     *
     * @return array
     */
    public function getAllPatientSurnames(): array
    {
      $data = $this->database->fetchAll(SqlCommands::getAllPatientSurnames());
      $res = array('0' => '-');

      foreach ($data as $items)
      {
        $key = $items['ID'];
        $res[$key] = $items['surname'];
        $res_ext = '';
        if ($items['name'])
          $res_ext .= $items['name'];
        if ($items['phone'])
          $res_ext .= (strlen(trim($res_ext)) > 0 ? ', ' : '').$items['phone'];
        if (strlen(trim($res_ext)))
          $res[$key] .= ' ('.$res_ext.')';
      }

      return $res;
    }


    /**
     * POST: Vrací data pacienta podle ID
     *
     * @return array
     */
    public function getPatient($ID)
    {
      return $this->database->fetch(SqlCommands::getPatient(),$ID);
    }


    /**
     * POST: Vrací data objednávky podle ID
     *
     * @return array
     */
    public function getOrder($ID)
    {
      return $this->database->fetch(SqlCommands::getOrder(),$ID);
    }


    /**
     * DIÁŘ: Vrací data diáře podle ID
     *
     * @return array
     */
    public function getDiary($ID)
    {
      return $this->database->fetch(SqlCommands::getDiary(),$ID);
    }


    /**
     * POST: Vloží novou objednávku
     *
     * @param object $data Data objednávky
     * @return bool
     */
    public function insertOrder($data)
    {
      return $this->database->query(SqlCommands::insertOrder(),$data->ID_USER,$data->date,$data->hour_from,$data->min_from,$data->hour_to,$data->min_to,$data->desc,$data->created_by);
    }


    /**
     * DIÁŘ: Vloží novou událost
     *
     * @param object $data Data události
     * @return bool
     */
    public function insertDiary($data)
    {
      $rst = $this->database->query(SqlCommands::insertDiary(),
        $data->lekce_id,
        $data->aktivita_id,
        $data->nazev,
        $data->popis,
        $data->lektor_id,
        $data->lektor,
        $data->date,
        $data->hour_from,
        $data->min_from,
        $data->hour_to,
        $data->min_to,
        $data->desc,
        $data->color,
        $data->created_by
      );

      return $this->database->getInsertId();
    }


    /**
     * POST: Smaže objednávku podle ID
     *
     * @param int $ID ID objednávky
     * @param string $deletedBy Jméno přihlášeného uživatele
     * @return bool
     */
    public function deleteOrder($ID,$deletedBy)
    {
      return $this->database->query(SqlCommands::deleteOrder(),
          $deletedBy,
          $ID
      );
    }


    /**
     * DIÁŘ: Smaže událost podle ID
     *
     * @param int $ID ID událostu
     * @param string $deleted_by Jméno přihlášeného uživatele
     * @return bool
     */
    public function deleteDiary($ID,$deleted_by)
    {
      return $this->database->query(SqlCommands::deleteDiary(),
          $deleted_by,
          $ID
      );
    }


    /**
     * DIÁŘ: Smaže událost podle ID lekce
     *
     * @param int $lekce_id ID lekce
     * @param int $date Datum lekce
     * @param string $deleted_by Jméno přihlášeného uživatele
     * @return bool
     */
    public function deleteDiaryByLekceId($lekce_id,$date,$deleted_by)
    {
      return $this->database->query(SqlCommands::deleteDiaryByLekceId(),
          $deleted_by,
          $lekce_id,
          $date,
      );
    }


    /**
     * POST: Upraví objednávku
     *
     * @param type $data Data objednávky
     * @return bool
     */
    public function updateOrder($data)
    {
      return $this->database->query(SqlCommands::updateOrder(),
          $data->ID_USER,
          $data->date,
          $data->hour_from,
          $data->min_from,
          $data->hour_to,
          $data->min_to,
          $data->desc,
          $data->updated_by,
          $data->ID
      );
    }


    /**
     * DIÁŘ: Upraví událost
     *
     * @param type $data Data události
     * @return bool
     */
    public function updateDiary($data)
    {
      return $this->database->query(SqlCommands::updateDiary(),
          $data->aktivita_id,
          $data->nazev,
          $data->popis,
          $data->lektor_id,
          $data->date,
          $data->hour_from,
          $data->min_from,
          $data->hour_to,
          $data->min_to,
          $data->desc,
          $data->color,
          $data->updated_by,
          $data->ID
      );
    }


    /**
     * DIÁŘ: Upraví událost
     *
     * @param type $data Data události
     * @return bool
     */
    public function updateDiaryByLekceId($data)
    {
      return $this->database->query(SqlCommands::updateDiaryByLekceId(),
          $data->aktivita_id,
          $data->nazev,
          $data->popis,
          $data->lektor_id,
          $data->hour_from,
          $data->min_from,
          $data->hour_to,
          $data->min_to,
          $data->desc,
          $data->color,
          $data->updated_by,
          $data->lekce_id,
          $data->ID
      );
    }


    /**
     * POST: Vloží nový kontaktn pacienta
     * @param object $data Data kontaktu pacinta
     * @return bool
     */
    public function insertPatient($data)
    {
      return $this->database->query(SqlCommands::insertPatient(),$data->surname,$data->name,$data->phone,$data->created_by,$data->unreliable);
    }


    /**
     * POST: Smaže kontakt pacienta podle ID
     *
     * @param int $ID ID kontaktu pacienta
     * @param string $deletedBy Jméno přihlášeného uživatele
     * @return bool
     */
    public function deletePatient($ID,$deletedBy)
    {
      return $this->database->query(SqlCommands::deletePatient(),$deletedBy,$ID);
    }


    /**
     * POST: Upraví kontakt pacienta
     *
     * @param type $data Data kontaktu pacienta
     * @return bool
     */
    public function updatePatient($data)
    {
      return $this->database->query(SqlCommands::updatePatient(),$data->surname,$data->name,$data->phone,$data->updated_by,$data->unreliable,$data->ID);
    }


    /**
     * DEBT: Vrací všechny záznamy pohledávky / zakázky
     *
     * @return array
     */
    public function getAllDebts(): array
    {
      return $this->database->fetchAll(SqlCommands::getAllDebts());
    }


    /**
     * DEBT: Vrací všechny záznamy pohledávky / zakázky podle typu
     *
     * @return array
     */
    public function getAllDebtsByType($type): array
    {
      return $this->database->fetchAll(SqlCommands::getAllDebtsByType(),$type);
    }


    /**
     * DEBT: Vrací všechny záznamy pohledávky / zakázky podle typu
     *
     * @return array
     */
    public function getAllDebtsByID(): array
    {
      return $this->database->fetch(SqlCommands::getAllDebtsByType());
    }


    /**
     * DEBT: Vloží nový záznam pohledávky / zakázky
     *
     * @param object $data Data pohledávky / zakázky
     * @return bool
     */
    public function insertDebt($data)
    {
      return $this->database->query(SqlCommands::insertDebt(),$data->debt,$data->description,$data->amount,$data->type,$data->created_by);
    }


    /**
     * DEBT: Upraví záznam pohledávky / zakázky
     *
     * @param type $data Data pohledávky / zakázky
     * @return bool
     */
    public function updateDebt($data)
    {
      return $this->database->query(SqlCommands::updateDebt(),$data->debt,$data->description,$data->amount,$data->type,$data->updated_by,$data->ID);
    }


    /**
     * DEBT: Smaže záznam pohledávky / zakázky
     *
     * @param int $ID ID záznamu pohledávky / zakázky
     * @param string $deletedBy Jméno přihlášeného uživatele
     * @return bool
     */
    public function deleteDebt($ID,$deletedBy)
    {
      return $this->database->query(SqlCommands::deleteDebt(),$deletedBy,$ID);
    }


    /**
     * Získá seznam položek ze sloupce typu 'ENUM'
     *
     * @return array
     */
    public function getListFromEnum($params): array
    {
      $data = $this->database->fetchPairs(SqlCommands::getListFromEnum(),$params['db'],$params['tbl'],$params['col']);

      if (!$data || !is_array($data) || !isset($data[0]))
        return array('???' => 'nebyla nalezena žádná položka');

      $enumStr = str_replace(["enum(",")","'"],"",$data[0]);
      $enumValues = explode(",",$enumStr);

      $rst = array();
      foreach ($enumValues as $item)
      {
        $rst[$item] = $item;
      }

      return $rst;
    }


    /**
     * Vrátí korektní čas ve formátu HH:MM
     *
     * @param type $hour Hodina
     * @param type $minute Minuta
     * @return string
     */
    function formatTime($hour,$minute): string
    {
      $hour = (int) $hour;
      $minute = (int) $minute;

      return str_pad((string) $hour,2,'0',STR_PAD_LEFT).':'.str_pad((string) $minute,2,'0',STR_PAD_LEFT);
    }


    /**
     * REGISTRACE: Vloží registraci klienta na lekci a upraví kredity klienta
     *
     * @param object $data Data pro registraci
     * @return bool
     */
    public function insertRegistrace($data)
    {
      // vložím registraci
      if (!$this->database->query(SqlCommands::insertRegistrace(),$data->user_id,$data->diary_id,$data->aktivita_id,$data->created_by,$data->sales_id))
      {
        //dump(SqlCommands::insertRegistrace(),$data->user_id,$data->diary_id,$data->aktivita_id,$data->created_by,$data->sales_id);
        //die;
        return false;
      }

      // upravím kredity (-1)
      if (!$this->database->query(SqlCommands::updateKredityKlienta(),$data->kredit_zmena,$data->created_by,$data->user_id,$data->aktivita_id))
      {
        //dump(SqlCommands::updateKredityKlienta(),$data->kredit_zmena,$data->created_by,$data->user_id,$data->aktivita_id);
        //die;
        return false;
      }


      // upravím kredity v aktivní permanentce
      if (!$this->database->query(SqlCommands::updateKredityAktivniPermanentka(),$data->kredit_zmena,$data->created_by,$data->sales_id))
      {
        //dump(SqlCommands::updateKredityAktivniPermanentka(),$data->kredit_zmena,$data->created_by,$data->sales_id);
        //die;
        return false;
      }


      return true;
    }


    /**
     * REGISTRACE: Zruší registraci klienta na lekci a upraví kredity klienta
     *
     * @param object $data Data pro registraci
     * @return bool
     */
    public function deleteRegistrace($data)
    {
      // zruším registraci
      if (!$this->database->query(SqlCommands::deleteRegistrace(),$data->deleted_by,$data->user_id,$data->diary_id,$data->created_by))
        return false;

      // upravím kredity(+1)
      if (!$this->database->query(SqlCommands::updateKredityKlienta(),$data->kredit_zmena,$data->deleted_by,$data->user_id,$data->aktivita_id))
        return false;

      // upravím kredity v aktivní permanentce
      if ($data->aktivni_permanentka)
      {
        if (!$this->database->query(SqlCommands::updateKredityAktivniPermanentka(),$data->kredit_zmena,$data->deleted_by,$data->sales_id))
        return false;
      }

      return true;
    }


    /**
     * KREDITY: Upraví kredity klienta
     *
     * @param object $data Data pro kredity
     * @return bool
     */
    public function updateKredityKlienta($data)
    {
      return $this->database->query(SqlCommands::updateKredityKlienta(),
          $data->kredit_zmena,
          $data->updated_by,
          $data->user_id,
          $data->aktivita_id
      );
    }


    /**
     * KREDITY: Načte kredity klienta
     *
     * @param object $data Data pro kredity
     * @return bool
     */
    public function getKredityKlienta($data)
    {
      return $this->database->fetch(SqlCommands::getKredityKlienta(),
          $data->user_id,
          $data->aktivita_id
      );
    }


    /**
     * REGISTRACE: Test na registraci klienta na lekci
     *
     * @param object $data Data pro registraci
     * @return bool
     */
    public function checkIsUserIsRegistered($data)
    {
      return $this->database->fetch(SqlCommands::checkIsUserIsRegistered(),$data->user_id,$data->diary_id);
    }


    /**
     * Převede pole na objekt
     *
     * @param array $params Pole s parametry
     * @return object
     */
    public static function array_to_object(array $params = array()): object
    {
      $data = new \stdClass;
      Arrays::toObject($params,$data);

      return $data;
    }

  }
