<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;

  /**
   * Správce permanentek
   */
  class MembershipCardManager extends DatabaseManager
  {
    const DELETE_OK = 0;
    const DELETE_NOT_FOUND = 1;
    const DELETE_HAS_SALES = 2;


    /**
     * PERMANENTKA: Vrací všechny permanentky
     *
     * @return array
     */
    public function getAllPermanentka()
    {
      return $this->database->fetchAll(SqlCommands::getAllPermanentka());
    }


    /**
     * PERMANENTKA: Vrací všechny permanentky setříděné podle aktivity a ceny
     *
     * @return array
     */
    public function getAllPermanentkaOrderByActivity()
    {
      return $this->database->fetchAll(SqlCommands::getAllPermanentkaOrderByActivity());
    }


    /**
     * PERMANENTKA: Vrací všechny aktivní permanentky setříděné podle aktivity a ceny
     *
     * @return array
     */
    public function getAllAktivniPermanentkaOrderByActivity()
    {
      return $this->database->fetchAll(SqlCommands::getAllAktivniPermanentkaOrderByActivity());
    }


    /**
     * PERMANENTKA: Vrací seznam všech permanentek
     *
     * @return array
     */
    public function getListPermanentka(): array
    {
      $rst = array(0 => '--- zvolte ---');

      $data = $this->getAllPermanentkaOrderByActivity();
      foreach ($data as $key => $item)
        $rst[$item['id']] = sprintf('%s - %s - %s,-',$item['nazev_aktivity'],$item['nazev'],$item['cena']);

      return $rst;
    }


    /**
     * PERMANENTKA: Vrací seznam aktivních permanentek pro prodej
     *
     * @return array
     */
    public function getListAktivniPermanentka(): array
    {
      $rst = array(0 => '--- zvolte ---');

      $data = $this->getAllAktivniPermanentkaOrderByActivity();
      foreach ($data as $key => $item)
        $rst[$item['id']] = sprintf('%s - %s - %s,-',$item['nazev_aktivity'],$item['nazev'],$item['cena']);

      return $rst;
    }


    /**
     * PERMANENTKA: Vrací permannetku podle ID
     *
     * @param object $data Data permanentky
     * @return array
     */
    public function getPermanentka($data)
    {
      return $this->database->fetch(SqlCommands::getPermanentka(),$data->id);
    }


    /**
     * PERMANENTKA: Vloží permannetku
     *
     * @param object $data Data permanentky
     * @return int
     */
    public function insertPermanentka($data): int
    {
      $this->database->query(SqlCommands::insertPermanentka(),
        $data->aktivita_id,
        $data->nazev,
        $data->cena,
        $data->platnost,
        $data->platnost_ts,
        $data->aktivni,
        $data->vstupy,
        $data->created_by
      );

      return (int) $this->database->getInsertId();
    }


    /**
     * PERMANENTKA: Vloží permannetku
     *
     * @param object $data Data permanentky
     * @return bool
     */
    public function updatePermanentka($data)
    {
      return $this->database->query(SqlCommands::updatePermanentka(),
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
     * PERMANENTKA: Smaže permannetku podle ID
     *
     * @param object $data Data permanentky
     * @return int
     */
    public function deletePermanentka($data)
    {
      $this->database->beginTransaction();
      try
      {
        $card = $this->database->fetch(SqlCommands::getPermanentka(),$data->id);
        if (!$card)
        {
          $this->database->rollBack();
          return self::DELETE_NOT_FOUND;
        }

        $sales = $this->database->fetch(SqlCommands::getSalesCountByPermanentkaId(),$data->id);
        if ((int) $sales['total'] > 0)
        {
          $this->database->rollBack();
          return self::DELETE_HAS_SALES;
        }

        $res = $this->database->query(SqlCommands::deletePermanentka(),
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
