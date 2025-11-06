<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;

  /**
   * Správce aktivit
   */
  class ActivityManager extends DatabaseManager
  {


    /**
     * AKTIVITA: Vrací všechny aktivity
     *
     * @return array
     */
    public function getAllAktivita()
    {
      return $this->database->fetchAll(SqlCommands::getAllAktivita());
    }


    /**
     * AKTIVITA: Vrací seznam aktivit
     *
     * @return array
     */
    public function getListAktivita()
    {
      $rst = array(0 => '--- zvolte ---');

      $data = $this->getAllAktivita();
      foreach ($data as $key => $item)
        $rst[$item['id']] = $item['nazev'];

      return $rst;
    }


    /**
     * AKTIVITA: Vrací pole ID aktivit s nazvy
     *
     * @return array
     */
    public function getAktivita_ID()
    {
      $rst = array();
      $data = $this->getAllAktivita();

      foreach ($data as $key => $items)
        $rst[$items['id']] = $items['nazev'];

      return $rst;
    }


    /**
     * AKTIVITA: Vrací aktivitu podle ID
     *
     * @param object $data Data aktivity
     * @return array
     */
    public function getAktivita($data)
    {
      return $this->database->fetch(SqlCommands::getAktivita(),$data->id);
    }


    /**
     * AKTIVITA: Vloží aktivitu
     *
     * @param object $data Data aktivity
     * @return bool
     */
    public function insertAktivita($data)
    {
      return $this->database->query(SqlCommands::insertAktivita(),
          $data->nazev,
          $data->vstupy_min,
          $data->vstupy_max,
          $data->zruseni_zdarma,
          $data->zruseni_zdarma_ts,
          $data->zruseni_neucast,
          $data->zruseni_neucast_ts,
          $data->registrace_konec,
          $data->registrace_konec_ts,
          $data->created_by
      );
    }


    /**
     * AKTIVITA: Vloží aktivitu
     *
     * @param object $data Data aktivity
     * @return bool
     */
    public function updateAktivita($data)
    {
      return $this->database->query(SqlCommands::updateAktivita(),
          $data->nazev,
          $data->vstupy_min,
          $data->vstupy_max,
          $data->zruseni_zdarma,
          $data->zruseni_zdarma_ts,
          $data->zruseni_neucast,
          $data->zruseni_neucast_ts,
          $data->registrace_konec,
          $data->registrace_konec_ts,
          $data->updated_by,
          $data->id
      );
    }


    /**
     * AKTIVITA: Smaže aktivitu podle ID
     *
     * @param object $data Data aktivity
     * @return bool
     */
    public function deleteAktivita($data)
    {
      return $this->database->query(SqlCommands::deleteAktivita(),
          $data->deleted_by,
          $data->id
      );
    }
  }
