<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;

  /**
   * Třída pro správu eventlogu
   */
  final class EventLogPresenter extends BasePresenter
  {

    /**
     * Počet záznamů na stránce
     */
    const SQL_LIMIT = 100;

    /**
     * Nastavení první stránky pro výpočet offsetu
     */
    const SQL_FIRST_PAGE = 1;


    /**
     * Inicalizace presenteru
     *
     * @return void
     */
	    public function startup(): void
	    {
	      parent::startup();

	      $this->requireAdmin();
	    }


    /**
     * Zobrazení logu
     *
     * @param int $page
     * @return void
     */
    public function renderDefault(): void
    {
      // načtení dat pro danou stránku
      $eventlog = $this->logManager->getEventlog();

      // předání hodonot do šablony
      $this->template->eventlog = $eventlog;
    }
  }
