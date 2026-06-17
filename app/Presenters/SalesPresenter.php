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
	        $form->addSubmit('send','Odstranit')
	          ->setHtmlAttribute('class','btn btn-sm btn-danger')
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

	      try
	      {
        // odstraním prodej
        $this->salesManager->deleteProdej($data);

        // aktualizuji kredity
        //$this->factoryManager->updateKredityKlienta($data);

      }
      catch (\Exception $e)
      {
	        $_msg = sprintf('Chyba! %s nebyl smazán.',$saleLabel);
        $this->eventlog('sale',$_msg);
        $this->flashMessage($_msg,'danger');
        $this->redirect('Sales:default');
      }

      /*
      // odečtu vstupy
      $data = self::array_to_object(['id' => $user_id,'vstupy_minus' => $vstupy_aktualni,'updated_by' => $this->userName]);
      try
      {
        $this->salesManager->updateUserVstupy($data);
      }
      catch (Exception $ex)
      {
        $_msg = sprintf('Chyba! Vstupy uživatele ID=%s nebyly aktualizováíny.',$data->user_id);
        $this->eventlog('user',$_msg);
        $this->flashMessage($_msg,'danger');
      }
      $_msg = sprintf('Vstupy uživatele ID=%s byly aktualizováíny.',$data->user_id);
      $this->eventlog('user',$_msg);
      $this->flashMessage($_msg);
       *
       */

	      $_msg = sprintf('%s byl smazán.',$saleLabel);
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
    public function renderEdit(int $id = 0): void
    {
      $params = self::array_to_object(array('id' => $id));

      $data = $this->salesManager->getProdej($params);

      $this->template->data = $data;

	      $this->eventlog('sales',sprintf('Zobrazena editace %s.',$this->logSaleLabel($data,$id)));
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
      $_permanentky = $this->membershipCardManager->getListPermanentka();

      // seznam klientů (uživatelů)
      $_users = $this->userManager->getListUsers();

      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('id')
        ->setDefaultValue(0)
        ->setHtmlAttribute('ID','frm-salesForm-id');

      $form->addSelect('permanentka_id','Permanetka:',$_permanentky)
        ->setHtmlAttribute('class','form-control');

      $form->addSelect('user_id','Klient:',$_users)
        ->setHtmlAttribute('class','form-control');

      $form->addTextArea('desc','Poznámka:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','');

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

	      $rst_perm = $this->salesManager->getPermanentkaActivitaById($data);
      $rst_user = $this->salesManager->getUserById($data);


      $data->user_id = $rst_user->id;
      $data->username_full = sprintf('%s %s (%s)',$rst_user->surname,$rst_user->firstname,$rst_user->username);
      $data->permanentka_id = $rst_perm->id;
      $data->aktivita_id = $rst_perm->aktivita_id;
      $data->aktivita_name = sprintf('%s - %s', $rst_perm->nazev_aktivity,$rst_perm->nazev);
      $data->cena = $rst_perm->cena;
      $data->vstupy_celkem = $rst_perm->vstupy;

      // načtu kredity klienta
      $rst_kredit = $this->factoryManager->getKredityKlienta($data);

      // pokud je klient s kredity v mínusu, odečtu mínusové kredity z aktualních vstupů na permanentce
      if ($rst_kredit['kredity'] < 0)
        $data->vstupy_aktualni = $rst_perm->vstupy + $rst_kredit['kredity'];
      else
        $data->vstupy_aktualni = $rst_perm->vstupy;

      $data->datum_prodeje = time();

      $_ts_datum_konce = $data->datum_prodeje + $rst_perm->platnost_ts;
      $ts_datum_konce = strtotime(date('Ymd 23:59:59',$_ts_datum_konce));
      $data->datum_konce = $ts_datum_konce;

      $data->created_by = $this->userName;

      if ($data->id == 0)
      {
        $operace = 'insert';
      }
      else
      {
	        $_msg = sprintf('Chyba! Neplatná operace pro %s.',$this->logSaleLabel($data,(int) $data->id));
        $this->flashMessage($_msg,'danger');
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
        die();
      }

      if ($operace == 'insert')
      {
        try
        {
          $this->salesManager->insertProdej($data);

          // pokud jsou kredity v mínusu, nastavím hodnotu kreditu na 0
          // hodnota záporných kreditů byla použita pro ponížení aktuálních vstupů na nové permanentce
          if ($rst_kredit['kredity'] < 0)
          {
            $data->updated_by = $this->userName;
            $data->kredit_zmena = 0;
            $this->factoryManager->resetKredit($data);
          }

        }
        catch (\Exception $e)
        {
	          $_msg = sprintf('Chyba! Nový prodej pro klienta %s, permanentka %s, nebyl uložen.',$data->username_full,$data->aktivita_name);
          $this->flashMessage($_msg,'danger');
          $this->eventlog('sale',$_msg);
          $this->redirect('Sales:default');
        }

	        $_msg = sprintf('Nový prodej pro klienta %s, permanentka %s, byl uložen.',$data->username_full,$data->aktivita_name);
        $this->flashMessage($_msg);
        $this->eventlog('sale',$_msg);
        $this->redirect('Sales:default');
      }

      /*
      if ($operace == 'update')
      {
        $data->updated_by = $this->userName;
        try
        {
          $this->salesManager->updateProdej($data);
        }
        catch (\Exception $e)
        {
          $_msg = sprintf('Chyba! Prodej ID=%s nebyl uložen.',$data->id);
          $this->flashMessage($_msg,'danger');
          $this->eventlog('sale',$_msg);
          $this->redirect('Sales:default');
        }

        $_msg = sprintf('Prodej ID=%s byl uložena.',$data->id);
        $this->flashMessage($_msg);
        $this->eventlog('sale',$_msg);
        $this->redirect('Sales:default');
      }
       *
       */
    }
  }
