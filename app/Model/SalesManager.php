<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;

  /**
   * Správce prodeje
   */
  class SalesManager extends DatabaseManager
  {


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
     * @return bool
     */
    public function insertProdej($data)
    {
      return $this->database->query(SqlCommands::insertProdej(),
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
    }


    /**
     * PRODEJ: Vloží prodej
     *
     * @param object $data Data prodeje
     * @return bool
     */
    public function updateProdej($data)
    {
      return $this->database->query(SqlCommands::updateProdej(),
          $data->aktivita_id,
          $data->nazev,
          $data->cena,
          $data->platnost,
          $data->platnost_ts,
          $data->aktivni,
          $data->vstupy,
          $data->updated_by,
          $data->id
      );
    }


    /**
     * PRODEJ: Smaže prodej podle ID
     *
     * @param object $data Data prodeje
     * @return bool
     */
    public function deleteProdej($data)
    {
      return $this->database->query(SqlCommands::deleteProdej(),
          $data->deleted_by,
          $data->id
      );
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
