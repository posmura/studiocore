<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;

  /**
   * Správce prodeje
   */
  class SalesManager extends DatabaseManager
  {
    const DELETE_OK = 0;
    const DELETE_NOT_FOUND = 1;
    const DELETE_HAS_REGISTRATIONS = 2;
    const DELETE_HAS_USED_CREDITS = 3;


    /**
     * PRODEJ: Vrací všechny prodeje
     *
     * @return array
     */
    public function getAllProdej()
    {
      return $this->database->fetchAll(SqlCommands::getAllProdej());
    }


    /**
     * PRODEJ: Vrací prodej podle ID
     *
     * @param object $data Data prodeje
     * @return array
     */
    public function getProdej($data)
    {
      return $this->database->fetch(SqlCommands::getProdej(),$data->id);
    }


    /**
     * PRODEJ: Vloží prodej
     *
     * @param object $data Data prodeje
     * @return int
     */
    public function insertProdej($data): int
    {
      $this->database->beginTransaction();
      try
      {
        $res = $this->database->query(SqlCommands::insertProdej(),
          $data->user_id,
          $data->username_full,
          $data->permanentka_id,
          $data->aktivita_id,
          $data->aktivita_name,
          $data->cena,
          $data->vstupy_celkem,
          $data->vstupy_aktualni,
          $data->datum_prodeje,
          $data->datum_konce,
          $data->desc,
          $data->created_by
        );

        if ($res->getRowCount() !== 1)
        {
          throw new \RuntimeException('Prodej nebyl uložen.');
        }

        $saleId = (int) $this->database->getInsertId();

        if (($data->reset_kredit ?? false) === true)
        {
          $reset = $this->database->query(SqlCommands::resetKredit(),
            $data->kredit_zmena,
            $data->created_by,
            $data->user_id,
            $data->aktivita_id
          );

          if ($reset->getRowCount() !== 1)
          {
            throw new \RuntimeException('Kredit klienta nebyl resetován.');
          }
        }

        $this->database->commit();
        return $saleId;
      }
      catch (\Throwable $e)
      {
        $this->database->rollBack();
        throw $e;
      }
    }


    /**
     * PRODEJ: Smaže prodej podle ID
     *
     * @param object $data Data prodeje
     * @return int
     */
	    public function deleteProdej($data)
	    {
	      $this->database->beginTransaction();
	      try
	      {
	        $sale = $this->database->fetch(SqlCommands::getProdej(),$data->id);
	        if (!$sale)
	        {
	          $this->database->rollBack();
	          return self::DELETE_NOT_FOUND;
	        }

	        if ((int) $sale['vstupy_aktualni'] !== (int) $sale['vstupy_celkem'])
	        {
	          $this->database->rollBack();
	          return self::DELETE_HAS_USED_CREDITS;
	        }

	        $registrations = $this->database->fetch(SqlCommands::getRegistrationCountBySalesId(),$data->id);
	        if ((int) $registrations['total'] > 0)
	        {
	          $this->database->rollBack();
	          return self::DELETE_HAS_REGISTRATIONS;
	        }

		      $res = $this->database->query(SqlCommands::deleteProdej(),
	          $data->deleted_by,
	          $data->id
        );

	        if ($res->getRowCount() !== 1)
	        {
	          $this->database->rollBack();
	          return self::DELETE_NOT_FOUND;
	        }

	        $this->database->commit();
	        return self::DELETE_OK;
	      }
	      catch (\Throwable $e)
	      {
	        $this->database->rollBack();
	        throw $e;
	      }
    }


    /**
     * PRODEJ: Vybere permanentku a odpovídající aktivitu podle ID peramanentky
     *
     * @param object $data Data prodeje
     * @return bool
     */
    public function getPermanentkaActivitaById($data)
    {
      return $this->database->fetch(SqlCommands::getPermanentkaActivitaById(),$data->permanentka_id);
    }


    /**
     * PRODEJ: Vybere uživatele podle ID uživatele
     *
     * @param object $data Data prodeje
     * @return bool
     */
    public function getUserById($data)
    {
      return $this->database->fetch(SqlCommands::getUserById(),$data->user_id);
    }


    /**
     * PRODEJ: Zobrazí nákupy za aktivitu
     *
     *  - seskupení podle user_id a aktivita_id,
     *  - v každé skupině jsou řádky seřazené podle datum_prodeje ASC
     *  - počítají dvě sumy: SUM(vstupy_celkem) a SUM(vstupy_aktualni) v rámci skupiny,
     *
     * @return bool
     */
    public function getNakup($data)
    {
      return $this->database->fetchAll(SqlCommands::getNakup(),
        $data->id,
        $data->ts
      );
    }
  }
