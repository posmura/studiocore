<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;

  /**
   * Správce aktivit
   */
  class ActivityManager extends DatabaseManager
  {
    const DELETE_OK = 0;
    const DELETE_NOT_FOUND = 1;
    const DELETE_HAS_RELATIONS = 2;



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
     * AKTIVITA: Vrací počty vazeb na aktivitu podle ID.
     *
     * @param int $id ID aktivity
     * @return array
     */
    public function getAktivitaUsageCounts(int $id): array
    {
      $data = $this->database->fetch(SqlCommands::getAktivitaUsageCounts(),$id,$id,$id,$id,$id);
      if (!$data)
        return array(
          'diary_total' => 0,
          'membership_card_total' => 0,
          'sales_total' => 0,
          'registration_total' => 0,
          'credits_total' => 0,
        );

      return array(
        'diary_total' => (int) $data['diary_total'],
        'membership_card_total' => (int) $data['membership_card_total'],
        'sales_total' => (int) $data['sales_total'],
        'registration_total' => (int) $data['registration_total'],
        'credits_total' => (int) $data['credits_total'],
      );
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
      if (!$this->database->fetch(SqlCommands::getAktivita(),$data->id))
        return false;

      $this->database->query(SqlCommands::updateAktivita(),
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

      return true;
    }


    /**
     * AKTIVITA: Smaže aktivitu podle ID
     *
     * @param object $data Data aktivity
     * @return int
     */
    public function deleteAktivita($data)
    {
      $this->database->beginTransaction();
      try
      {
        $activity = $this->database->fetch(SqlCommands::getAktivita(),$data->id);
        if (!$activity)
        {
          $this->database->rollBack();
          return self::DELETE_NOT_FOUND;
        }

        $usage = $this->getAktivitaUsageCounts((int) $data->id);
        if (array_sum($usage) > 0)
        {
          $this->database->rollBack();
          return self::DELETE_HAS_RELATIONS;
        }

        $res = $this->database->query(SqlCommands::deleteAktivita(),
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
  }
