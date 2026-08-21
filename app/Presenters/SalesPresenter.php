<?php

  declare(strict_types=1);

	  namespace App\Presenters;

	  use Nette\Application\UI\Form;
	  use Nette\Application\UI\Multiplier;

  /**
   * Třída presenteru pro permanentky
   */
  final class SalesPresenter extends BasePresenter
  {


    /**
     * Inicalizace presenteru
     *
     * @return void
     */
	    public function startup(): void
	    {
	      parent::startup();

	      $this->requireStaff();
	    }


    /**
     * Seznam všech uživatelů
     *
     * @return void
     */
    public function renderDefault(): void
    {
      $this->template->data = $this->salesManager->getAllProdej();
      $eventlogSaleId = (int) $this->getParameter('eventlogSaleId');
      $this->template->eventlogSaleId = $eventlogSaleId > 0 ? $eventlogSaleId : 0;
    }


    /**
     * Mazání prodeje
     *
     * @param int $id ID prodeje
     * @param int $user_id ID klienta
     * @param int $vstupy_aktualni Počet aktuálních vstupů
     * @param int $aktivita_id ID aktivity
     * @return void
     */
	    public function renderDelete($id,$user_id,$vstupy_aktualni,$aktivita_id): void
	    {
	      $this->requireAdmin();

	      $this->error('Mazání prodeje je nutné provést formulářem.',405);
	    }


	    /**
	     * Formuláře pro mazání prodeje.
	     *
	     * @return Multiplier
	     */
	    protected function createComponentDeleteSalesForm(): Multiplier
	    {
	      $this->requireAdmin();

	      return new Multiplier(function (string $id)
	      {
	        $form = new Form;
	        $form->setHtmlAttribute('style','display:inline;');
	        $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');
	        $form->addHidden('id',$id);
	        $form->addSubmit('send',\Nette\Utils\Html::el('i')->setAttribute('class','bi bi-trash'))
	          ->renderAsButton()
	          ->setHtmlAttribute('class','btn btn-sm btn-danger')
	          ->setHtmlAttribute('title','Smazat')
	          ->setHtmlAttribute('aria-label','Smazat')
	          ->setHtmlAttribute('onclick',"return confirm('Opravdu chcete záznam odstranit?');");
	        $form->onSuccess[] = [$this,'deleteSalesFormSucceeded'];

	        return $form;
	      });
	    }


	    /**
	     * Akce po odeslání formuláře pro mazání prodeje.
	     *
	     * @param Form $form Objekt formuláře
	     * @param type $data Data z formuláře
	     * @return void
	     */
	    public function deleteSalesFormSucceeded(Form $form,$data): void
	    {
	      $this->requireAdmin();

		      $data = self::array_to_object(
		        [
		          'id' => $data->id,
		          'deleted_by' => $this->userName,
		          'updated_by' => $this->userName,
		        ]
		      );
		      $saleLabel = $this->logSaleLabelById((int) $data->id);
		      $saleErrorLabel = ucfirst($saleLabel);

	      try
	      {
        $rst = $this->salesManager->deleteProdej($data);
      }
      catch (\Throwable $e)
      {
	        $_msg = sprintf('Chyba! %s nebyl smazán.',$saleErrorLabel);
        $this->eventlog('sale',sprintf('%s DB chyba: %s',$_msg,$e->getMessage()));
        $this->flashMessage($_msg,'danger');
        $this->redirect('Sales:default');
      }

      if ($rst !== \App\Model\SalesManager::DELETE_OK)
      {
        if ($rst === \App\Model\SalesManager::DELETE_HAS_REGISTRATIONS)
          $_msg = sprintf('Chyba! %s nelze smazat, protože jsou na něj navázané registrace klienta.',$saleErrorLabel);
        elseif ($rst === \App\Model\SalesManager::DELETE_HAS_USED_CREDITS)
          $_msg = sprintf('Chyba! %s nelze smazat, protože už má změněný počet aktuálních vstupů.',$saleErrorLabel);
        elseif ($rst === \App\Model\SalesManager::DELETE_NOT_FOUND)
          $_msg = sprintf('Chyba! %s nebyl nalezen, nebo už byl smazán.',$saleErrorLabel);
        else
          $_msg = sprintf('Chyba! %s nebyl smazán.',$saleErrorLabel);

        $this->eventlog('sale',$_msg);
        $this->flashMessage($_msg,'danger');
        $this->redirect('Sales:default');
      }

	      $_msg = sprintf('%s byl smazán.',$saleErrorLabel);
      $this->flashMessage($_msg);
      $this->eventlog('sale',$_msg);
      $this->redirect('Sales:default');
    }


    /**
     * Editace prodeje
     *
     * @param int $id ID permanentky
     * @return void
     */
    public function actionEdit(int $id = 0): void
    {
      $this->error('Editace prodeje není podporována.',404);
    }


    /**
     * Formuláře pro prodej
     *
     * @return Form
     */
	    protected function createComponentSalesForm(): Form
	    {
	      $this->requireStaff();

	      // seznam permanentek
      $_permanentky = $this->membershipCardManager->getListAktivniPermanentka();

      // seznam klientů (uživatelů)
      $_users = $this->userManager->getListUsers();

      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('id')
        ->setDefaultValue(0)
        ->setHtmlAttribute('id','frm-salesForm-id');

      $form->addSelect('permanentka_id','Permanentka:',$_permanentky)
        ->setHtmlAttribute('class','form-control')
        ->setRequired('Vyberte permanentku.')
        ->addRule(Form::NotEqual,'Vyberte permanentku.',0);

      $form->addSelect('user_id','Klient:',$_users)
        ->setHtmlAttribute('class','form-control')
        ->setRequired('Vyberte klienta.')
        ->addRule(Form::NotEqual,'Vyberte klienta.',0);

      $form->addTextArea('desc','Poznámka:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->addRule(Form::MaxLength,'Poznámka může mít maximálně 255 znaků.',255);

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formSalesSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro editaci
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
	    public function formSalesSucceeded(Form $form,$data): void
	    {
	      $this->requireStaff();

	      if ((int) $data->id !== 0)
	      {
	        $_msg = sprintf('Chyba! Neplatná operace pro prodej ID=%d.',(int) $data->id);
	        $this->flashMessage($_msg,'danger');
	        $this->eventlog('sale',$_msg);
	        $this->redirect('Sales:default');
	      }

	      $rst_perm = $this->salesManager->getPermanentkaActivitaById($data);
      $rst_user = $this->salesManager->getUserById($data);

      if (!$rst_perm)
      {
        $_msg = sprintf('Chyba! Vybraná permanentka ID=%d nebyla nalezena, nebo není aktivní.',(int) $data->permanentka_id);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sale',$_msg);
        $this->redirect('Sales:default');
      }

      if (!$rst_user)
      {
        $_msg = sprintf('Chyba! Vybraný klient ID=%d nebyl nalezen.',(int) $data->user_id);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sale',$_msg);
        $this->redirect('Sales:default');
      }

      $data->user_id = $rst_user->id;
      $data->username_full = sprintf('%s %s (%s)',$rst_user->surname,$rst_user->firstname,$rst_user->username);
      $data->permanentka_id = $rst_perm->id;
      $data->aktivita_id = $rst_perm->aktivita_id;
      $data->aktivita_name = sprintf('%s - %s', $rst_perm->nazev_aktivity,$rst_perm->nazev);
      $data->cena = $rst_perm->cena;
      $data->vstupy_celkem = $rst_perm->vstupy;
      $userLabel = $this->logUserLabelById((int) $data->user_id);
      $cardLabel = $this->logMembershipCardLabelById((int) $data->permanentka_id);
      $activityLabel = $this->logActivityLabelById((int) $data->aktivita_id);

      // načtu kredity klienta
      $rst_kredit = $this->factoryManager->getKredityKlienta($data);
      $kredit = $rst_kredit ? (int) $rst_kredit['kredity'] : 0;

      // pokud je klient s kredity v mínusu, odečtu mínusové kredity z aktualních vstupů na permanentce
      if ($kredit < 0)
        $data->vstupy_aktualni = (int) $rst_perm->vstupy + $kredit;
      else
        $data->vstupy_aktualni = (int) $rst_perm->vstupy;

      if ($data->vstupy_aktualni < 0)
      {
        $_msg = sprintf('Chyba! Nový prodej pro klienta %s, permanentka %s, aktivita %s, by měl záporný počet vstupů.',$userLabel,$cardLabel,$activityLabel);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sale',$_msg);
        $this->redirect('Sales:default');
      }

      $data->datum_prodeje = time();

      $_ts_datum_konce = $data->datum_prodeje + $rst_perm->platnost_ts;
      $ts_datum_konce = strtotime(date('Ymd 23:59:59',$_ts_datum_konce));
      $data->datum_konce = $ts_datum_konce;

      $data->created_by = $this->userName;
      $data->reset_kredit = $kredit < 0;
      $data->kredit_zmena = 0;

      try
      {
        $saleId = $this->salesManager->insertProdej($data);
      }
      catch (\Throwable $e)
      {
	      $_msg = sprintf('Chyba! Nový prodej pro klienta %s, permanentka %s, aktivita %s, nebyl uložen.',$userLabel,$cardLabel,$activityLabel);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sale',sprintf('%s DB chyba: %s',$_msg,$e->getMessage()));
        $this->redirect('Sales:default');
      }

	    $_msg = sprintf('Nový prodej ID=%d pro klienta %s, permanentka %s, byl uložen.',$saleId,$userLabel,$cardLabel);
      $this->flashMessage($_msg);
      $this->eventlog('sale',$_msg);
      $this->redirect('Sales:default');
    }
  }
