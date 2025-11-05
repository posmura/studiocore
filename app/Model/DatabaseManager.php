<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Database\Connection;

class DatabaseManager
{
  /**
   * Object Connection
   * @var object
   */
  public $database;


  /**
   * Konstruktor třídy
   *
   * @param Connection $database
   */
  public function __construct(Connection $database)
  {
    $this->database = $database;
  }
}
