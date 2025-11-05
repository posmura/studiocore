<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;

  /**
   * Správce permanentek
   */
  class MembershipCardManager extends DatabaseManager
  {

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
     * @return bool
     */
    public function insertPermanentka($data)
    {
      return $this->database->query(SqlCommands::insertPermanentka(),
        $data->aktivita_id,
        $data->nazev,
        $data->cena,
        $data->platnost,
        $data->platnost_ts,
        $data->aktivni,
        $data->vstupy,
        $data->created_by
      );
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
     * @return bool
     */
    public function deletePermanentka($data)
    {
      return $this->database->query(SqlCommands::deletePermanentka(),
        $data->deleted_by,
        $data->id
      );
    }

}
