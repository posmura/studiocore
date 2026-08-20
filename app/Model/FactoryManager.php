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
     * REGISTRACE: Vyvtvoří registraci klienta na lekci a upraví kredity klienta
     *
     * @param object $data Data pro registraci
     * @return int
     */
    public function insertRegistrace($data): int
    {
      $registrationStatus = $data->registration_status ?? 'ucastnik';

      $this->database->beginTransaction();
      try {
        $res = $this->database->query(
          SqlCommands::insertRegistrace(),
          $data->user_id,
          $data->diary_id,
          $data->aktivita_id,
          $registrationStatus,
          $data->created_by,
          $data->sales_id
        );

        $affected = $res->getRowCount();  // 0 nebo 1

        if ($affected === 0)
        {
          // registrace klienta na lekci již existuje
          $this->database->rollBack();
          return 9999;
        }
        elseif ($affected !== 1)
        {
          $this->database->rollBack();
          return 9998;
        }

        // Náhradníkovi se kredit odečte až při povýšení mezi účastníky.
        if ($registrationStatus === 'ucastnik' && (int) $data->sales_id === 0)
        {
          $creditUpdate = $this->database->query(SqlCommands::updateKredityKlienta(),$data->kredit_zmena,$data->created_by,$data->user_id,$data->aktivita_id);
          if ($creditUpdate->getRowCount() !== 1)
          {
            $this->database->rollBack();
            return 5;
          }
        }

        if ($registrationStatus === 'ucastnik' && (int) $data->sales_id > 0)
        {
          $creditUpdate = $this->database->query(SqlCommands::updateKredityAktivniPermanentka(),$data->kredit_zmena,$data->created_by,$data->sales_id,$data->kredit_zmena);
          if ($creditUpdate->getRowCount() !== 1)
          {
            $this->database->rollBack();
            return 5;
          }
        }

        $this->database->commit();
        return 0;
      }
      catch (\Nette\Database\DriverException $e)
      {
        $this->database->rollBack();
        // kód 4025 = MariaDB CHECK constraint violation (kredity >= -1)
        if ($e->getDriverCode() == 4025)
          return 4;
        return 1;
      }
    }


    /**
     * REGISTRACE: Zruší registraci klienta na lekci a upraví kredity klienta
     *
     * @param object $data Data pro zrušení egistrace
     * @return int
     */
    public function deleteRegistrace($data): int
    {
      $isSubstitute = ($data->registration_status ?? 'ucastnik') === 'nahradnik';

      $this->database->beginTransaction();
      try {
        if ($isSubstitute)
        {
          $this->database->query(SqlCommands::protectLekceUcastBeforeSubstituteDelete(),$data->diary_id);
        }

        if ((int) ($data->registration_id ?? 0) > 0)
        {
          $res = $this->database->query(SqlCommands::deleteRegistraceByID(),$data->deleted_by,$data->registration_id,$data->user_id,$data->diary_id);
        }
        else
        {
          $res = $this->database->query(SqlCommands::deleteRegistrace(),$data->deleted_by,$data->user_id,$data->diary_id);
        }

        if ($res->getRowCount() !== 1)
        {
          $this->database->rollBack();
          return 9999;
        }

        if ($isSubstitute)
        {
          $this->database->query(SqlCommands::refreshLekceUcast(),$data->diary_id,$data->diary_id);
        }

        // když není permice, vrátím jen kredit
        if ((int) $data->sales_id === 0 && $data->kredit_zmena != 0)
        {
          $creditUpdate = $this->database->query(SqlCommands::updateKredityKlienta(),$data->kredit_zmena,$data->deleted_by,$data->user_id,$data->aktivita_id);
          if ($creditUpdate->getRowCount() !== 1)
          {
            $this->database->rollBack();
            return 5;
          }
        }

        // když je permice, vrátím jen permici
        if ((int) $data->sales_id > 0 && $data->kredit_zmena != 0)
        {
          $creditUpdate = $this->database->query(SqlCommands::updateKredityAktivniPermanentka(),$data->kredit_zmena,$data->deleted_by,$data->sales_id,$data->kredit_zmena);
          if ($creditUpdate->getRowCount() !== 1)
          {
            $this->database->rollBack();
            return 5;
          }
        }

        $this->database->commit();
        return 0;
      }
      catch (\Nette\Database\DriverException $e)
      {
        $this->database->rollBack();
        $data->delete_error = $e->getMessage();
        return 1;
      }
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
     * KREDITY: Ruční změna kreditu klienta pro konkrétní aktivita_id
     *
     * Záporná změna nejdříve odečítá vstupy z aktivních permanentek, stejně jako
     * registrace na lekci. Teprve zbytek změny se zapíše do ručních kreditů.
     *
     * @param object $data Data pro kredity
     * @return array
     */
    public function updateKredit($data): array
    {
      $kreditZmena = (int) $data->kredit;

      if ($kreditZmena === 0)
        return array(
          'kredit_zmena' => 0,
          'sources' => array(),
        );

      $userId = (int) $data->user_id;
      $aktivitaId = (int) $data->aktivita_id;
      $updatedBy = $data->updated_by;
      $sources = array();

      $this->database->beginTransaction();
      try
      {
        if ($kreditZmena < 0)
        {
          $zbyvaOdecist = abs($kreditZmena);
          $datumKonce = strtotime('today 23:59:59');
          $permanentky = $this->database->fetchAll(SqlCommands::getAktivniPermanentkyID(),$userId,$aktivitaId,$datumKonce);

          foreach ($permanentky as $permanentka)
          {
            if ($zbyvaOdecist <= 0)
              break;

            $dostupneVstupy = (int) $permanentka['vstupy_aktualni'];
            if ($dostupneVstupy <= 0)
              continue;

            $odecist = min($dostupneVstupy,$zbyvaOdecist);
            $res = $this->database->query(SqlCommands::updateKredityAktivniPermanentka(),-$odecist,$updatedBy,(int) $permanentka['ID'],-$odecist);
            if ($res->getRowCount() !== 1)
              throw new \RuntimeException('Vstupy aktivní permanentky nebyly změněny.');

            $sources[] = array(
              'type' => 'permanentka',
              'zmena' => -$odecist,
              'sales_id' => (int) $permanentka['ID'],
              'permanentka_id' => (int) $permanentka['permanentka_id'],
              'aktivita_name' => (string) $permanentka['aktivita_name'],
              'vstupy_pred' => $dostupneVstupy,
              'vstupy_po' => $dostupneVstupy - $odecist,
              'datum_konce' => (int) $permanentka['datum_konce'],
            );

            $zbyvaOdecist -= $odecist;
          }

          if ($zbyvaOdecist > 0)
          {
            $res = $this->database->query(SqlCommands::updateKredityKlienta(),-$zbyvaOdecist,$updatedBy,$userId,$aktivitaId);
            if ($res->getRowCount() !== 1)
              throw new \RuntimeException('Kredity klienta nebyly změněny.');

            $sources[] = array(
              'type' => 'kredit',
              'zmena' => -$zbyvaOdecist,
            );
          }
        }
        else
        {
          $res = $this->database->query(SqlCommands::updateKredityKlienta(),$kreditZmena,$updatedBy,$userId,$aktivitaId);
          if ($res->getRowCount() !== 1)
            throw new \RuntimeException('Kredity klienta nebyly změněny.');

          $sources[] = array(
            'type' => 'kredit',
            'zmena' => $kreditZmena,
          );
        }

        $this->database->commit();
        return array(
          'kredit_zmena' => $kreditZmena,
          'sources' => $sources,
        );
      }
      catch (\Throwable $e)
      {
        $this->database->rollBack();
        throw $e;
      }
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
     * KREDITY: Vyresetuje kredity klienta pro danou aktivitu
     *
     * @param object $data Data pro kredity
     * @return bool
     */
    public function resetKredit($data)
    {
      return $this->database->query(SqlCommands::resetKredit(),
          $data->kredit_zmena,
          $data->created_by,
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
     * REGISTRACE: Vrací ID permanentky
     *
     * @param object $data Data pro registraci
     * @return bool
     */
    public function getSalesId($data)
    {
      return $this->database->fetch(SqlCommands::getSalesId(),$data->user_id,$data->diary_id);
    }


    /**
     * REGISTRACE: Vrací počet účastníků a náhradníků lekce.
     *
     * @param int $diary_id ID lekce
     * @return mixed
     */
    public function getRegistrationCounts(int $diary_id)
    {
      return $this->database->fetch(SqlCommands::getRegistrationCounts(),$diary_id);
    }


    /**
     * REGISTRACE: Vrací prvního náhradníka lekce.
     *
     * @param int $diary_id ID lekce
     * @return mixed
     */
    public function getFirstSubstituteRegistration(int $diary_id)
    {
      return $this->database->fetch(SqlCommands::getFirstSubstituteRegistration(),$diary_id);
    }


    /**
     * REGISTRACE: Povýší náhradníka mezi účastníky a odečte mu kredit.
     *
     * @param object $data Data pro povýšení náhradníka
     * @return int
     */
    public function promoteSubstituteToParticipant($data): int
    {
      $this->database->beginTransaction();
      try {
        $res = $this->database->query(
          SqlCommands::promoteSubstituteToParticipant(),
          $data->updated_by,
          $data->registration_id,
          $data->diary_id
        );

        if ($res->getRowCount() !== 1)
        {
          $this->database->rollBack();
          return 9999;
        }

        if ((int) $data->sales_id === 0)
        {
          $creditUpdate = $this->database->query(SqlCommands::updateKredityKlienta(),$data->kredit_zmena,$data->updated_by,$data->user_id,$data->aktivita_id);
          if ($creditUpdate->getRowCount() !== 1)
          {
            $this->database->rollBack();
            return 5;
          }
        }

        if ((int) $data->sales_id > 0)
        {
          $creditUpdate = $this->database->query(SqlCommands::updateKredityAktivniPermanentka(),$data->kredit_zmena,$data->updated_by,$data->sales_id,$data->kredit_zmena);
          if ($creditUpdate->getRowCount() !== 1)
          {
            $this->database->rollBack();
            return 5;
          }
        }

        $this->database->commit();
        return 0;
      }
      catch (\Nette\Database\DriverException $e)
      {
        $this->database->rollBack();
        if ($e->getDriverCode() == 4025)
          return 4;
        return 1;
      }
    }


    /**
     * REGISTRACE: Vrací registraci podle jejího ID.
	     *
	     * @param int $registration_id ID registrace
	     * @return mixed
	     */
	    public function getRegistraceByID(int $registration_id)
	    {
	      return $this->database->fetch(SqlCommands::getRegistraceByID(),$registration_id);
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


    /**
     * LEKCE: Vrací detail lekce podle diary_id
     *
     * @return string
     */
    public function getLekceDetail($data): array
    {
      return $this->database->fetchAll(SqlCommands::getLekceDetail(),$data->diary_id);
    }


    /**
     * LEKCE: Vrací info lekce podle diary_id
     *
     * @return string
     */
    public function getLekceInfo($data): array
    {
      return $this->database->fetchAll(SqlCommands::getLekceInfo(),$data->diary_id);
    }


    /**
     * LEKCE: Upraví potvrzení účasti klienta na lekci
     *
     * @return string
     */
    public function updateUcast($data)
    {
      return $this->database->query(SqlCommands::updateUcast(),$data->ucast, $data->updated_by,$data->ID);
    }

  }
