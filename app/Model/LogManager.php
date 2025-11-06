<?php

  declare(strict_types=1);

  namespace App\Model;

  use App\Model\DatabaseManager;


  /**
   * Správce logu
   */
  class LogManager extends DatabaseManager
  {
    /**
     * Vloží záznam do eventlog
     *
     * @param array $data Data eventlogu
     * @return bool
     */
    public function insertEvenlog($data)
    {
      return $this->database->query(SqlCommands::insertEvenlog(),$data['username'],$data['presenter'],$data['action'],$data['remote_ip']);
    }


    /**
     * Vrací všechny záznamy událostí
     *
     * @return type
     */
    public function getEventlog()
    {
      return $this->database->fetchAll(SqlCommands::getEventlog());
    }


    /**
     * Vrací daný počet záznamů pro daný offset
     *
     * @param int $offset Offset
     * @param int $limit Počet záznamů na stránku
     * @return type
     */
    public function getEventlogByPage($offset,$limit)
    {
      $sql_limit = sprintf('LIMIT %d,%d',$limit,$offset);
      return $this->database->fetchAll(SqlCommands::getEventlogByPage($sql_limit));
    }


    /**
     * Vrací počet všech záznamů v tabulce eventlogu
     *
     * @return int
     */
    public function getEventlogCount()
    {
      $rst = $this->database->fetch(SqlCommands::getCountEventlog());

      return (int) $rst['pocet'];
    }
  }
