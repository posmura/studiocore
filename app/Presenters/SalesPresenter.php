<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;

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
      if (!$this->getUser()->isLoggedIn())
      {
        $this->redirect('Homepage:');
      }
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
      $data = self::array_to_object(
        [
          'id' => $id,
          'deleted_by' => $this->userName,
          'updated_by' => $this->userName,
          'kredit_zmena' => -1 * $vstupy_aktualni,
          'user_id' => $user_id,
          'aktivita_id' => $aktivita_id,
        ]
      );

      try
      {
        // odstraním prodej
        $this->salesManager->deleteProdej($data);

        // aktualizuji kredity
        $this->factoryManager->updateKredityKlienta($data);

      }
      catch (\Exception $e)
      {
        $_msg = sprintf('Chyba! Prodej ID=%s nebyl smazán.',$data->id);
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

      $_msg = sprintf('Prodej ID=%s byl smazán.',$data->id);
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

      $this->eventlog('sales','Editace prodeje ID='.$id.'.');
    }


    /**
     * Formuláře pro prodej
     *
     * @return Form
     */
    protected function createComponentSalesForm(): Form
    {
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
        $_msg = sprintf('Chyba! Neplatná operace pro prodej ID=%s.',$data->id);
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

          // aktualizuji kredity
          $data->updated_by = $this->userName;
          $data->kredit_zmena = $data->vstupy_aktualni;
          $this->factoryManager->updateKredityKlienta($data);

        }
        catch (\Exception $e)
        {
          $_msg = sprintf('Chyba! Nový prodej nebyl uložen.');
          $this->flashMessage($_msg,'danger');
          $this->eventlog('sale',$_msg);
          $this->redirect('Sales:default');
        }

        $_msg = sprintf('Nový prodej byl uložen.');
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
