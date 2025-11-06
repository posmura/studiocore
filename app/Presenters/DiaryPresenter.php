<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;

  /**
   * Třída presenteru pro příspěvky
   */
  final class DiaryPresenter extends BasePresenter
  {

    /**
     * Timestamp předaného dne
     * @var int
     */
    private $tsDiaryDate;

    /**
     * Datum události
     * @var int;
     */
    private $diaryDate;

    /**
     * Jméno dne
     * @var array
     */
    private $diaryNameDay = array(
      1 => 'pondělí',
      2 => 'úterý',
      3 => 'středa',
      4 => 'čtvrtek',
      5 => 'pátek',
      6 => 'sobota',
      7 => 'neděle',
    );


    /**
     * Inicalizace presenteru
     *
     * @return void
     */
    public function startup(): void
    {
      parent::startup();
    }


    /**
     * Zobrazení diáře
     *
     * @param int $tsDiaryDate Timestamp, z kterého se vypočítají data pro víkend
     * @return void
     */
    public function renderDefault(int $tsDiaryDate = null): void
    {
      // zobrazení diáře k danému datu
      if (!$tsDiaryDate)
        $this->tsDiaryDate = $this->tsToday;
      else
        $this->tsDiaryDate = $tsDiaryDate;
      $this->diaryDate = date('Ymd',$this->tsDiaryDate);

      // datumy pro týden
      $diaryWeek = $this->getDiaryWeek();
      $this->template->diaryWeek = $diaryWeek;

      // události
      $this->template->diaryEvents = $this->getDiaryEvents($diaryWeek);

      // události přihlášeného klienta
      $userRegistered = array();
      if ($this->user->isLoggedIn())
      {
        $params = $this->factoryManager->array_to_object(['id' => $this->userID]);
        $data = $this->userManager->getRegistraceByUserID($params);
        foreach ($data as $key => $items)
        {
          $userRegistered[$items['diary_id']] = $items['diary_id'];
        }
      }
      $this->template->userRegistered = $userRegistered;

      // timestamp pro předchozí týden
      $this->template->diaryPrevWeek = $diaryWeek[1]['prevSunday'];

      // timestamp pro následujcí týden
      $this->template->diaryNextWeek = $diaryWeek[7]['nextMonday'];
    }


    /**
     * Smazání události
     *
     * @param int $ID ID události
     * @param string $lekce_ID ID lekce
     * @param int $date Datum dne lekce
     * @param int $tsDiaryDate Timestamp události
     * @param int $opakovat Počet opakování
     * @return void
     */
    public function renderDelete($ID,$lekce_id,$date,$tsDiaryDate,$opakovat): void
    {
      $deleted_by = $this->userName;

      try
      {
        if ($opakovat > 1)
        {
          $this->factoryManager->deleteDiaryByLekceId($lekce_id,$date,$deleted_by);
          $_msg = sprintf('Události lekce ID=%d byly smazány od %d a výše.',$lekce_id,$date);
        }
        else
        {
          $this->factoryManager->deleteDiary($ID,$deleted_by);
          $_msg = sprintf('Událost ID=%d byla smazána.',$ID);
        }

        $this->eventlog('diary',$_msg);
      }
      catch (\Exception $e)
      {
        $_msg = sprintf('Chyba! Událost ID=%d nebyla smazána.',$ID);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('diary',$_msg);
      }

      $this->redirect('Diary:',$tsDiaryDate);
    }


    /**
     * Vrací týden
     *
     * @return array
     */
    public function getDiaryWeek(): array
    {
      $res = [];

      // vytvořím DateTime z výchozího timestampu
      $date = (new \DateTime())->setTimestamp($this->tsDiaryDate);

      // posunu se na pondělí aktuálního týdne
      if ($date->format('N') != 1)
      {
        $date->modify('last monday');
      }

      // projdu pondělí až neděli
      for ($i = 1; $i <= 7; $i++)
      {
        $d = clone $date;
        $d->modify('+'.($i - 1).' days');

        $ts = $d->getTimestamp();

        $res[$i]['ts'] = $ts;
        $res[$i]['diaryNameDay'] = $this->diaryNameDay[$i];
        $res[$i]['diaryDate'] = $d->format('Ymd');
        $res[$i]['diaryDateFormat'] = $d->format('d.m.Y');
        $res[$i]['diaryToday'] = $d->format('Ymd') === date('Ymd',$this->tsToday);
      }

      // timestamp předchozí neděle
      $prevSunday = (clone $date)->modify('-1 day')->getTimestamp();

      // timestamp následujícího pondělí
      $nextMonday = (clone $date)->modify('+7 days')->getTimestamp();

      $res[1]['prevSunday'] = $prevSunday;
      $res[7]['nextMonday'] = $nextMonday;

      return $res;
    }


    /**
     * Vrací data událostí pro daný týden
     *
     * @param type $diaryWeek Data týden
     * @return array
     */
    public function getDiaryEvents($diaryWeek): array
    {
      $res = array();
      $i = 0;

      foreach ($diaryWeek as $diaryKey => $diaryItems)
      {
        $items = $this->factoryManager->getDiaryEvents($diaryItems['diaryDate']);
        /*
          a.`ID` AS `ID`,
          a.`lekce_id` AS `lekce_id`,
          a.`aktivita_id` AS `aktivita_id`,
          a.`nazev` AS `nazev`,
          a.`popis` AS `popis`,
          a.`lektor_id` AS `lektor_id`,
          a.`lektor` AS `lektor`,
          a.`date` AS `date`,
          a.`hour_from` AS `hour_from`,
          a.`min_from` AS `min_from`,
          a.`hour_to` AS `hour_to`,
          a.`min_to` AS `min_to`,
          a.`desc` AS `desc`,
          a.`color` AS `color`,
          b.`nazev` AS `aktivita_nazev`,
          b.`vstupy_min` AS `aktivita_vstupy_min`,
          b.`vstupy_max` AS `aktivita_vstupy_max`,
          COALESCE(c.`total`, 0) AS `aktivita_vstupy_aktualni`,
          b.`zruseni_zdarma` AS `zruseni_zdarma`,
          b.`zruseni_zdarma_ts` AS `zruseni_zdarma_ts`,
          b.`zruseni_neucast` AS `aktivita_zruseni_neucast`,
          b.`zruseni_neucast_ts` AS `aktivita_zruseni_neucast_ts`,
          b.`registrace_konec` AS `aktivita_registrace_konec`,
          b.`registrace_konec_ts` AS `aktivita_registrace_konec_ts`
         */
        foreach ($items as $itemsKey => $eventItems)
        {
          $res[$i]['aktivita_id'] = $eventItems['aktivita_id'];
          $res[$i]['lektor_id'] = $eventItems['lektor_id'];
          $res[$i]['nazev'] = $eventItems['nazev'];
          $res[$i]['popis'] = $eventItems['popis'];
          $res[$i]['aktivita_vstupy_aktualni'] = $eventItems['aktivita_vstupy_aktualni'];
          $res[$i]['aktivita_vstupy_max'] = $eventItems['aktivita_vstupy_max'];
          $res[$i]['ts_now'] = $eventItems['ts_now'];
          $res[$i]['ts_event'] = $eventItems['ts_event'];
          $res[$i]['diaryID'] = $eventItems['ID'];
          $res[$i]['diaryDate'] = $eventItems['date'];
          $res[$i]['diaryTimeFrom'] = $this->timeOrderToTimeForm($eventItems['hour_from'],$eventItems['min_from'],':');
          $res[$i]['diaryTimeTo'] = $eventItems['hour_to'] ? $this->timeOrderToTimeForm($eventItems['hour_to'],$eventItems['min_to'],':') : '';
          $res[$i]['diaryEvent'] = trim(sprintf('%s',$eventItems['desc']));
          $res[$i]['color'] = $eventItems['color'];
          $i++;
        }
      }

      return $res;
    }


    /**
     * Vrací objekt Form formuláře pro výběr datumu
     *
     * @return Form
     */
    protected function createComponentDiaryDateForm(): Form
    {
      // Definice formuláře
      $form = new Form;

      // nastavení ochrany
      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->setHtmlAttribute('class','d-flex flex-row align-items-center flex-wrap');

      // Datum
      $form->addText('date','Datum:')
        ->setValue($this->dateOrderToDateForm($this->diaryDate))
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute("placeholder","klikněte pro výběr datumu")
        ->setHtmlAttribute("style","cursor: pointer; width: 220px; background: #D1E7DD; border: 1px solid #D1E7DD; color: #0A3622;")
        ->setHtmlAttribute("readonly","readonly");

      // Tlačítko pro odeslání
      $form->addSubmit('send','OK')
        ->setHtmlAttribute('class','btn btn-success btn-sm');

      // Definice akce
      $form->onSuccess[] = [$this,'formSucceeded'];

      return $form;
    }


    /**
     * Zpracování dat odeslaných z formuláře pro výběr datumu
     *
     * @param Form $form Objekt Form formuláře
     * @param object $data Data z formuláře
     * @return void
     */
    public function formSucceeded(Form $form,$data): void
    {
      $diaryDate = $this->dateFormToDateOrder($data->date);

      $tsDiaryDate = $this->dateOrderToTsDiaryDate($diaryDate);

      if (!$tsDiaryDate)
        $tsDiaryDate = $this->tsToday;

      $this->redirect('Diary:default',$tsDiaryDate);
    }


    /**
     * Vrací objekt Form formuláře pro událost diáře
     *
     * @return Form
     */
    protected function createComponentDiaryForm(): Form
    {
      // Definice formuláře
      $form = new Form;

      // nastavení ochrany
      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      // ID události
      $form->addHidden('ID','0')
        ->setHtmlAttribute('id','frm-diaryForm-ID');

      // ID lekce
      $form->addHidden('lekce_id','')
        ->setHtmlAttribute('id','frm-diaryForm-lekce_id');

      // Aktivita ID
      $form->addSelect('aktivita_id','Aktivita:',$this->aktivita)
        ->setHtmlAttribute('class','form-select form-select-sm');

      // Lektor ID
      $form->addSelect('lektor_id','Lektor:',$this->lektor)
        ->setHtmlAttribute('class','form-select form-select-sm');

      // Název
      $form->addText('nazev','Název:')
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute("placeholder","");

      // Poznámka
      $form->addText('popis','Popis:')
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute("placeholder","");

      // Datum
      $form->addText('date','Datum:')
        ->setValue($this->dateOrderToDateForm($this->diaryDate))
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute("placeholder","klikněte pro výběr datumu")
        ->setHtmlAttribute("style","cursor: pointer; width: 220px; background: #D1E7DD; border: 1px solid #D1E7DD; color: #0A3622;")
        ->setHtmlAttribute("readonly","readonly");

      // Čas události od
      $form->addSelect('from','Od:',$this->orderHours)
        ->setDefaultValue('-')
        ->addRule($form::NotEqual,'Nebyl vybrán čas %label','-')
        ->setHtmlAttribute('class','form-select form-select-sm');

      // Čas události do
      $form->addSelect('to','Do:',$this->orderHours)
        ->setDefaultValue('-')
        ->addRule($form::NotEqual,'Nebyl vybrán čas %label','-')
        ->setHtmlAttribute('class','form-select form-select-sm');

      // Opakovat
      $form->addCheckbox('opakovat','')
        ->setHtmlAttribute('class','form-check-input');
      /*
        ->setOption('label',
        \Nette\Utils\Html::el('label')
        ->setText('Uživatelské jméno')
        ->addClass('form-check-label')
        ->title('aaaaaaa')
        ->setAttribute('id','frm-diaryForm-opakovat-label-text')
        );
       *
       */

      // Poznámka
      $form->addTextArea('desc','Poznámka:')
        ->setHtmlAttribute('class','form-control form-control-sm');

      // Barva
      $form->addText('color','Barva pozadí:')
        ->setDefaultValue('#d1e7dd')
        ->setHtmlType('color')
        ->setHtmlAttribute('class','form-control form-control-color');

      // Tlačítko pro odeslání (UPDATE)
      $form->addSubmit('send','Uložit')
        ->setHtmlAttribute('style','float:left;')
        ->setHtmlAttribute('class','btn btn-success');

      // Tlačítko pro odeslání (INSERT AS NEW ITEM)
      $form->addSubmit('sendAsNew','Uložit jako novou')
        ->setHtmlAttribute('id','frm-diaryForm-send_as_new')
        ->setHtmlAttribute('style','float:left;display:none;margin-left:5px;')
        ->setHtmlAttribute('class','btn btn-primary');

      // Tlačítko pro odeslání (DELETE)
      $form->addSubmit('sendDelete','Odstranit')
        ->setHtmlAttribute('id','frm-diaryForm-send_delete')
        ->setHtmlAttribute('style','float:right;display:none;')
        ->setHtmlAttribute('onclick',"return confirm('Opravdu si přejete odstranit záznam?')")
        ->setHtmlAttribute('class','btn btn-danger');

      // Definice akce
      $form->onSuccess[] = [$this,'diaryFormSucceeded'];

      return $form;
    }


    /**
     * Zpracování dat odeslaných z formuláře
     *
     * @param Form $form Objekt Form formuláře
     * @param array $data Data z formuláře
     * @return void
     */
    public function diaryFormSucceeded(Form $form,$data): void
    {
      // nastavení ID události pro tlačítko 'sendAsNew'
      if ($form['sendAsNew']->isSubmittedBy())
        $data->ID = '0';

      // nastavení akce
      $step = $data->ID == '0' ? 'insert' : 'update';

      // nastavení datumu události
      $data->date = $this->dateFormToDateOrder($data->date);

      // nastavení timestamp události
      $tsDiaryDate = $this->dateOrderToTsDiaryDate($data->date);

      // nastavení opakování
      if (isset($data->opakovat) && $data->opakovat)
        $opakovat = self::DIARY_LESSON_REPEAT_NUMBER;
      else
        $opakovat = 1;

      // opakování
      $tz = new \DateTimeZone('Europe/Prague');
      $start = \DateTimeImmutable::createFromFormat('Ymd H:i:s',$data->date.' 00:00:00',$tz);
      $interval = new \DateInterval('P7D');
      $period = new \DatePeriod($start,$interval,$opakovat);

      // -- nastavení akce pro tlačítko 'sendDelete'

      if ($form['sendDelete']->isSubmittedBy())
      {
        $this->redirect('Diary:delete',$data->ID,$data->lekce_id,$data->date,$tsDiaryDate,$opakovat);
        die;
      }


      // -- nastavení pro tlačítka 'send' a 'sendAsNew'
      // kontrola a nastavení časů udállstri od a do
      if ((int) $data->from > (int) $data->to)
      {
        $_msg = sprintf('Chyba! Čas události Od je vyšší než čas Do. Událost ID=%a nebyla upravena.',$data->ID);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('diary',$_msg);
        $this->redirect('Diary:',$tsDiaryDate);
        die;
      }

      $data->hour_from = substr((string) $data->from,0,2);
      $data->min_from = substr((string) $data->from,2,4);

      if ($data->to == '-' && $data->to == '0')
      {
        $data->hour_to = null;
        $data->min_to = null;
      }
      else
      {
        $data->hour_to = substr((string) $data->to,0,2);
        $data->min_to = substr((string) $data->to,2,4);
      }

      try
      {
        // insert
        if ($step == 'insert')
        {
          $data->lekce_id = date('YmdHis');
          $data->lektor = '';
          $data->created_by = $this->userName;

          if ($opakovat == 1)
          {
            $last_ID = $this->factoryManager->insertDiary($data);

            $_msg = sprintf('Událost %d (ID=%d) byla uložena.',$data->lekce_id,$last_ID);
            $this->eventlog('diary',$_msg);
          }
          elseif ($opakovat > 1)
          {
            $i = 0;
            foreach ($period as $dt)
            {
              $data->date = $dt->format('Ymd');
              $last_ID = $this->factoryManager->insertDiary($data);

              $i++;

              $_msg = sprintf('Událost %d (ID=%d) byla uložena.',$data->lekce_id,$last_ID);
              $this->eventlog('diary',$_msg);
            }
            $_msg = sprintf('Událost %d byla uložena %d x.',$data->lekce_id,$i);
            $this->eventlog('diary',$_msg);
          }
        }

        // update
        if ($step == 'update')
        {
          $data->updated_by = $this->userName;

          if ($opakovat == 1)
          {
            $this->factoryManager->updateDiary($data);

            $_msg = sprintf('Událost %d (ID=%d) byla upravena.',$data->lekce_id,$data->ID);
            $this->eventlog('diary',$_msg);
          }
          elseif ($opakovat > 1)
          {
            $this->factoryManager->updateDiaryByLekceId($data);

            $_msg = sprintf('Události %d byla upravena od ID=%d výše.',$data->lekce_id,$data->ID);
            $this->eventlog('diary',$_msg);
          }
        }
      }
      catch (\Exception $e)
      {
        $_msg = sprintf('Chyba! Událost ID=%d nebyla uložena.',$data->ID);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('diary',$_msg);
      }

      $this->redirect('Diary:',$tsDiaryDate);
    }


    /**
     * Vrací objekt Form formuláře pro výběr datumu
     *
     * @return Form
     */
    protected function createComponentRegisterForm(): Form
    {
      // Definice formuláře
      $form = new Form;

      // nastavení ochrany
      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('diary_id','')
        ->setHtmlAttribute('id','frm-registerForm-diary_id');

      $form->addHidden('aktivita_id','')
        ->setHtmlAttribute('id','frm-registerForm-aktivita_id');

      $form->addHidden('date','')
        ->setHtmlAttribute('id','frm-registerForm-date');

      $form->addHidden('user_id','')
        ->setHtmlAttribute('id','frm-registerForm-user_id');

      $form->addHidden('akce_id','')
        ->setHtmlAttribute('id','frm-registerForm-akce_id');

      $form->addHidden('sales_id','')
        ->setHtmlAttribute('id','frm-registerForm-sales_id');

      // Tlačítko pro odeslání
      $form->addSubmit('send','???')
        ->setHtmlAttribute('class','btn btn-success btn-sm')
        ->setHtmlAttribute('id','frm-registerForm-akce_desc');

      // Definice akce
      $form->onSuccess[] = [$this,'formRegisterSucceeded'];

      return $form;
    }


    /**
     * Zpracování dat odeslaných z formuláře registrace
     *
     * @param Form $form Objekt Form formuláře
     * @param object $data Data z formuláře
     * @return void
     */
    public function formRegisterSucceeded(Form $form,$data): void
    {
      $operace = $data->akce_id;

      $data['aktivni_permanentka'] = $this->aktivniPermanentkyID[$data->aktivita_id];

      try
      {
        if ($operace == 'registrovat')
        {
          $data['created_by'] = $this->userName;
          $data['kredit_zmena'] = -1;

          $rst = $this->factoryManager->insertRegistrace($data);
        }
        elseif ($operace == 'odregistrovat')
        {
          $data['deleted_by'] = $this->userName;
          $data['kredit_zmena'] = 1;

          $rst = $this->factoryManager->deleteRegistrace($data);
        }
        elseif ($operace == 'lekce_probehla' || $operace == 'neni_kredit')
        {
          // žádná akce
          $_no_action = true; // zatím proměnou nevyužívám
        }
        else
        {
          $_msg = sprintf('Chyba! aaa Registrace %s nebyla uložena.','');
          $this->flashMessage($_msg,'danger');
          $this->eventlog('diary',$_msg);
        }
      }
      catch (\Exception $e)
      {
        $_msg = sprintf('Chyba! bbb Registrace %s nebyla uložena.','');
        $this->flashMessage($_msg,'danger');
        $this->eventlog('diary',$_msg);
      }

      // jdu zpět do formuláře
      if (!$data->date)
      {
        $tsDiaryDate = $this->tsToday;
      }
      else
      {
        $_date = \DateTime::createFromFormat('Ymd',$data->date);
        $tsDiaryDate = $_date->getTimestamp();
      }

      $this->redirect('Diary:default',$tsDiaryDate);
    }


    /**
     * Handler pro ajaxové načtení dat události do formuláře Diář
     *
     * @return void
     */
    public function handleLoadDataFormOrder(): void
    {
      $ID = $this->getParameter('id_diary');

      $data = $this->factoryManager->getDiary($ID);

      $data['from'] = $this->timeOrderToTimeForm($data['hour_from'],$data['min_from']);

      if ($data['from'] == '')
        $data['from'] = '-';

      $data['to'] = $this->timeOrderToTimeForm($data['hour_to'],$data['min_to']);

      if ($data['to'] == '')
        $data['to'] = '-';

      $data['ts_now'] = time();

      $dateString = sprintf('%s %s',$data['date'],$data['from']);
      $dt = \DateTime::createFromFormat("Ymd Hi",$dateString);
      $data['ts_event'] = $dt->getTimestamp();

      $data['date'] = $this->dateOrderToDateForm($data['date']);

      $dataJson = json_encode($data);

      $this->sendJson($dataJson);

      $this->terminate();
    }


    /**
     * Handler pro ajaxové načtení dat lekce podle jejího ID do formuláře Registrace
     *
     * @return void
     */
    public function handleLoadDataLekce(): void
    {

      /*
        a.`ID` AS `ID`,
        a.`lekce_id` AS `lekce_id`,
        a.`aktivita_id` AS `aktivita_id`,
        a.`nazev` AS `nazev`,
        a.`popis` AS `popis`,
        a.`lektor_id` AS `lektor_id`,
        a.`lektor` AS `lektor`,
        a.`date` AS `date`,
        a.`hour_from` AS `hour_from`,
        a.`min_from` AS `min_from`,
        a.`hour_to` AS `hour_to`,
        a.`min_to` AS `min_to`,
        a.`desc` AS `desc`,
        a.`color` AS `color`,
        b.`nazev` AS `aktivita_nazev`,
        b.`vstupy_min` AS `aktivita_vstupy_min`,
        b.`vstupy_max` AS `aktivita_vstupy_max`,
        COALESCE(c.`total`, 0) AS `aktivita_vstupy_aktualni`,
        b.`zruseni_zdarma` AS `aktivita_zruseni_zdarma`,
        b.`zruseni_zdarma_ts` AS `aktivita_zruseni_zdarma_ts`,
        b.`zruseni_neucast` AS `aktivita_zruseni_neucast`,
        b.`zruseni_neucast_ts` AS `aktivita_zruseni_neucast_ts`,
        b.`registrace_konec` AS `aktivita_registrace_konec`,
        b.`registrace_konec_ts` AS `aktivita_registrace_konec_ts`

       */
      $ID = $this->getParameter('ID');

      $data = $this->factoryManager->getLekceById($ID);

      $rst = array();

      // informace o lekci
      $data['from'] = $this->timeOrderToTimeForm($data['hour_from'],$data['min_from']);
      if ($data['from'] == '')
        $data['from'] = '-';

      $data['to'] = $this->timeOrderToTimeForm($data['hour_to'],$data['min_to']);
      if ($data['to'] == '')
        $data['to'] = '-';

      // timestamp z data a času od
      $dateString = sprintf('%s %s',$data['date'],$data['from']);
      $dt = \DateTime::createFromFormat("Ymd Hi",$dateString);
      $data['ts_event'] = $dt->getTimestamp();
      $data['aktivita_date'] = $dt->format('d.m.Y');

      // aktualizace údajů
      $data['aktivita_from'] = $this->timeOrderToTimeForm($data['hour_from'],$data['min_from'],':');
      $data['aktivita_to'] = $this->timeOrderToTimeForm($data['hour_to'],$data['min_to'],':');

      $data['aktivita_zruseni_zdarma_ts'] = $data['ts_event'] - $data['aktivita_zruseni_zdarma_ts'];
      $data['aktivita_zruseni_zdarma'] = date('d.m.Y H:i',$data['aktivita_zruseni_zdarma_ts']);

      $data['aktivita_zruseni_neucast_ts'] = $data['ts_event'] - $data['aktivita_zruseni_neucast_ts'];
      $data['aktivita_zruseni_neucast'] = date('d.m.Y H:i',$data['aktivita_zruseni_neucast_ts']);

      $data['aktivita_registrace_konec_ts'] = $data['ts_event'] - $data['aktivita_registrace_konec_ts'];
      $data['aktivita_registrace_konec'] = date('d.m.Y H:i',$data['aktivita_registrace_konec_ts']);

      // data pro formulář registrace
      $rst['diary_id'] = $data['ID'];
      $rst['aktivita_id'] = $data['aktivita_id'];
      $rst['date'] = $data['date'];
      $rst['user_id'] = $this->userID;
      $rst['sales_id'] = $this->aktivniPermanentkyID[$rst['aktivita_id']] ?? null;

      $data['sales_id_text'] = $rst['sales_id'] ?? '-';

      $rst['lekce_info_text'] = <<<TEXT
        <div id="lekce-infotext">
          <div><strong>Název lekce:</strong> {$data['nazev']}</div>
          <div><strong>Lektor:</strong> {$this->lektorName[$data['lektor_id']]}</div>
          <div><strong>Datum a čas:</strong> {$data['aktivita_date']}, {$data['aktivita_from']} - {$data['aktivita_to']}</div>
          <div><strong>Bezplatné storno do:</strong> {$data['aktivita_zruseni_zdarma']}</div>
          <div><strong>Registrace možná do:</strong> {$data['aktivita_registrace_konec']}</div>
          <div><strong>Minimální počet klientů pro otevření lekce:</strong> {$data['aktivita_vstupy_min']}</div>
          <div><strong>Obsazenost lekce:</strong> {$data['aktivita_vstupy_aktualni']}/{$data['aktivita_vstupy_max']}</div>
          <div><strong>ID permanentky:</strong> {$data['sales_id_text']}</div>
        </div>
TEXT;

      // nastavení akce
      $_data = self::array_to_object(
        [
          'user_id' => $rst['user_id'],
          'diary_id' => $rst['diary_id']
        ]
      );

      // test na registraci klienta na lekci
      $rst_check = $this->factoryManager->checkIsUserIsRegistered($_data);
      if (!$rst_check['pocet'])
        $is_registered = 0;
      else
        $is_registered = $rst_check['pocet'];

      if ($data['aktivita_registrace_konec_ts'] <= time())
        $is_registered = -1;

      if ($is_registered == -1)
      {
        $rst['akce_id'] = 'lekce_probehla';
        $rst['akce_desc'] = 'LEKCE JIŽ PROBĚHLA';
      }
      else
      {
        if ($is_registered == 0)
        {
          // lze registrovat - zkontroluji kredity
          $kredity = $this->userManager->getKredityKlienta($rst['user_id'],$this->userName);

          $kredit = $kredity[$rst['aktivita_id']]['kredity'];
          if ($kredit < 0)
          {
            // nelze registrovat - nejsou kredity
            $rst['akce_id'] = 'neni_kredit';
            $rst['akce_desc'] = 'NEMÁTE DOSTATEČNÝ POČET VSTUPŮ';
          }
          else
          {
            // lze registrovat - jsou kredity
            $rst['akce_id'] = 'registrovat';
            $rst['akce_desc'] = 'REGISTROVAT';
          }
        }
        elseif ($is_registered > 0)
        {
          // klient je již registrován - nedovolím další registraci
          $rst['akce_id'] = 'odregistrovat';
          $rst['akce_desc'] = 'ZRUŠIT REGISTRACI';
        }
        else
        {
          // neznámý stav
          $rst['akce_id'] = '';
          $rst['akce_desc'] = '???';
        }
      }

      $dataJson = json_encode($rst);

      $this->sendJson($dataJson);

      $this->terminate();
    }
  }
