<?php

  declare(strict_types=1);

	  namespace App\Presenters;

	  use Nette\Application\UI\Form;
	  use Nette\Application\UI\Multiplier;

  /**
   * Třída presenteru pro permanentky
   */
  final class MembershipCardPresenter extends BasePresenter
  {

    /**
     * Povolenmé metody, kdy uživatel nemusí být přihlášem
     * @var array
     */
    private array $allowedActions = ['priceList'];


    /**
     * Inicalizace presenteru
     *
     * @return void
     */
	    public function startup(): void
	    {
	      parent::startup();

	      if (!in_array($this->getAction(),$this->allowedActions,true))
	        $this->requireStaff();
	    }


    /**
     * Seznam všech permanentek pro administraci
     *
     * @return void
     */
    public function renderDefault(): void
    {
      $this->template->data = $this->membershipCardManager->getAllPermanentka();
    }


    /**
     * Seznam všech permanentek pro ceník
     *
     * @return void
     */
    public function renderPriceList(): void
    {
      $this->template->data = $this->membershipCardManager->getAllAktivniPermanentkaOrderByActivity();
    }


    /**
     * Mazání permanentky
     *
     * @param int $id ID permanentky
     * @return void
     */
	    public function renderDelete($id): void
	    {
	      $this->requireAdmin();

	      $this->error('Mazání permanentky je nutné provést formulářem.',405);
	    }


	    /**
	     * Formuláře pro mazání permanentky.
	     *
	     * @return Multiplier
	     */
	    protected function createComponentDeletePermanentkaForm(): Multiplier
	    {
	      $this->requireAdmin();

	      return new Multiplier(function (string $id)
	      {
	        $form = new Form;
	        $form->setHtmlAttribute('style','display:inline;');
	        $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');
	        $form->addHidden('id',$id);
	        $form->addSubmit('send','Odstranit')
	          ->setHtmlAttribute('class','btn btn-sm btn-danger')
	          ->setHtmlAttribute('onclick',"return confirm('Opravdu chcete záznam odstranit?');");
	        $form->onSuccess[] = [$this,'deletePermanentkaFormSucceeded'];

	        return $form;
	      });
	    }


	    /**
	     * Akce po odeslání formuláře pro mazání permanentky.
	     *
	     * @param Form $form Objekt formuláře
	     * @param type $data Data z formuláře
	     * @return void
	     */
	    public function deletePermanentkaFormSucceeded(Form $form,$data): void
	    {
	      $this->requireAdmin();

		      $data = self::array_to_object(['id' => $data->id, 'deleted_by' => $this->userName]);
		      $cardLabel = $this->logMembershipCardLabelById((int) $data->id);
      $deleteUserLabel = $this->logUserLabelById((int) $this->userID);

	      try
	      {
        $rst = $this->membershipCardManager->deletePermanentka($data);
        if ($rst !== \App\Model\MembershipCardManager::DELETE_OK)
        {
          if ($rst === \App\Model\MembershipCardManager::DELETE_HAS_SALES)
          {
            $salesUsage = $this->formatPermanentkaSalesUsage((int) $data->id);
            $_msg = sprintf('Chyba! Permanentku %s nelze smazat uživatelem %s, protože už byla použita v prodeji. %sPermanentku deaktivujte.',$cardLabel,$deleteUserLabel,$salesUsage);
          }
          elseif ($rst === \App\Model\MembershipCardManager::DELETE_NOT_FOUND)
            $_msg = sprintf('Chyba! Permanentka %s nebyla nalezena, nebo už byla smazána uživatelem %s.',$cardLabel,$deleteUserLabel);
          else
            $_msg = sprintf('Chyba! Permanentka %s nebyla smazána uživatelem %s.',$cardLabel,$deleteUserLabel);

          $this->eventlog('membership_card',$_msg);
          $this->flashMessage($_msg,'danger');
          $this->redirect('MembershipCard:default');
        }
      }
      catch (\Throwable $e)
      {
        $dbError = trim($e->getMessage()) !== '' ? $e->getMessage() : 'neuvedena';
	        $_msg = sprintf('Chyba! Permanentka %s nebyla smazána uživatelem %s. DB chyba: %s',$cardLabel,$deleteUserLabel,$dbError);
        $this->eventlog('membership_card',$_msg);
        $this->flashMessage(sprintf('Chyba! Permanentka %s nebyla smazána.',$cardLabel),'danger');
        $this->redirect('MembershipCard:default');
      }

	      $_msg = sprintf('Permanentka %s byla smazána uživatelem %s.',$cardLabel,$deleteUserLabel);
      $this->flashMessage($_msg);
      $this->eventlog('membership_card',$_msg);
      $this->redirect('MembershipCard:default');
    }


    /**
     * Editace permanentky
     *
     * @param int $id ID permanentky
     * @return void
     */
	    public function renderEdit(int $id = 0): void
	    {
	      $this->requireAdmin();

	      if ($id === 0)
	        return;

	      $params = self::array_to_object(array('id' => $id));

      $data = $this->membershipCardManager->getPermanentka($params);
      if (!$data)
      {
        $_msg = sprintf('Chyba! Permanentka ID=%d nebyla nalezena, nebo je smazaná.',$id);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
      }

      $this->template->data = $data;

	      $this->eventlog('membership_card',sprintf('Zobrazena editace permanentky %s.',$this->logMembershipCardLabel($data,$id)));
    }


    /**
     * Formuláře pro permanentku
     *
     * @return Form
     */
	    protected function createComponentPermanentkaForm(): Form
	    {
	      $this->requireAdmin();

	      // seznam uživatelských rolí
      $_aktivni = $this->userManager->getListFromEnum(
        array(
          'db' => self::DB_NAME,
          'tbl' => 'blog_membership_card',
          'col' => 'aktivni',
        )
      );

      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('id')
        ->setDefaultValue(0)
        ->setHtmlAttribute('id','frm-permanentkaForm-id');

      $form->addSelect('aktivita_id','Aktivita:',$this->aktivita)
        ->setHtmlAttribute('class','form-control')
        ->setRequired('Vyberte aktivitu.')
        ->addRule(Form::NotEqual,'Vyberte aktivitu.',0);

      $form->addText('nazev','Název:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->addRule(Form::MaxLength,'%label může mít maximálně %d znaků.',255)
        ->setRequired('%label je vyžadováno!');

      $form->addText('cena','Cena:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být alespoň %d!',1)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('platnost','Platnost (dny):')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být alespoň %d!',1)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('vstupy','Počet vstupů:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být alespoň %d!',1)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadován!');

      $form->addSelect('aktivni','Je aktivní?',$_aktivni)
        ->setHtmlAttribute('class','form-control');

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formPermanentkaSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro editaci
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
	    public function formPermanentkaSucceeded(Form $form,$data): void
	    {
	      $this->requireAdmin();

	      if ($data->id == 0)
      {
        $operace = 'insert';
      }
      elseif ($data->id > 0)
      {
        $operace = 'update';
      }
      else
      {
	        $_msg = sprintf('Chyba! Neplatná operace pro permanentku %s.',$this->logMembershipCardLabel($data,(int) $data->id));
        $this->flashMessage($_msg,'danger');
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
        die();
      }

      $data->platnost_ts = $data->platnost * 86400;
      ;

      if ($operace == 'insert')
      {
        $data->created_by = $this->userName;

        try
        {
          $data->id = $this->membershipCardManager->insertPermanentka($data);
        }
        catch (\Throwable $e)
        {
	          $_msg = sprintf('Chyba! Nová permanentka %s nebyla uložena.',$this->logMembershipCardLabel($data));
          $this->flashMessage($_msg,'danger');
          $this->eventlog('membership_card',sprintf('%s DB chyba: %s',$_msg,$e->getMessage()));
          $this->redirect('MembershipCard:default');
        }

	        $_msg = sprintf('Nová permanentka %s byla uložena.',$this->logMembershipCardLabel($data));
        $this->flashMessage($_msg);
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
      }

      if ($operace == 'update')
      {
        $oldData = $this->membershipCardManager->getPermanentka($data);
        if (!$oldData)
        {
          $_msg = sprintf('Chyba! Permanentka ID=%d nebyla nalezena, nebo je smazaná.',(int) $data->id);
          $this->flashMessage($_msg,'danger');
          $this->eventlog('membership_card',$_msg);
          $this->redirect('MembershipCard:default');
        }

        $data->updated_by = $this->userName;
        try
        {
          $this->membershipCardManager->updatePermanentka($data);
        }
        catch (\Throwable $e)
        {
	          $_msg = sprintf('Chyba! Permanentka %s nebyla uložena.',$this->logMembershipCardLabel($data,(int) $data->id));
          $this->flashMessage($_msg,'danger');
          $this->eventlog('membership_card',sprintf('%s DB chyba: %s',$_msg,$e->getMessage()));
          $this->redirect('MembershipCard:default');
        }

	        $_msg = sprintf('Permanentka %s byla uložena.',$this->logMembershipCardLabel($data,(int) $data->id));
        $this->flashMessage($_msg);
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
      }
    }


    /**
     * Popisek souvisejících prodejů pro chybu mazání permanentky.
     */
    private function formatPermanentkaSalesUsage(int $permanentkaId): string
    {
      try
      {
        $sales = $this->membershipCardManager->getSalesByPermanentkaId($permanentkaId);
      }
      catch (\Throwable $e)
      {
        return '';
      }

      if (!$sales)
        return '';

      $items = array();
      foreach (array_slice($sales,0,3) as $sale)
      {
        $saleId = (int) $this->logValue($sale,'ID',$this->logValue($sale,'id',0));
        $userId = (int) $this->logValue($sale,'user_id',0);
        $saleLabel = $saleId > 0 ? sprintf('prodej ID=%d',$saleId) : 'neznámý prodej';
        $items[] = sprintf('%s, klient %s',$saleLabel,$this->logUserLabelById($userId));
      }

      $remaining = count($sales) - count($items);
      if ($remaining > 0)
        $items[] = sprintf('další prodeje: %d',$remaining);

      return sprintf('Související %s: %s. ',count($sales) === 1 ? 'prodej' : 'prodeje',implode('; ',$items));
    }
  }
