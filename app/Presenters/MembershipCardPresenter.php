<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;

  /**
   * Třída presenteru pro permanentky
   */
  final class MembershipCardPresenter extends BasePresenter
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
        $this->flashMessage('Z důvodu nečinnosti jste byl(a) automaticky odhlášen(a) z aplikace.','danger');

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
      $this->template->data = $this->membershipCardManager->getAllPermanentka();
    }


    /**
     * Mazání permanentky
     *
     * @param int $id ID permanentky
     * @return void
     */
    public function renderDelete($id): void
    {
      $data = self::array_to_object(['id' => $id, 'deleted_by' => $this->userName]);

      try
      {
        $this->membershipCardManager->deletePermanentka($data);
      }
      catch (\Exception $e)
      {
        $_msg = sprintf('Chyba! Permanentka ID=%s nebyla smazána.',$data->id);
        $this->eventlog('membership_card',$_msg);
        $this->flashMessage($_msg,'danger');
        $this->redirect('MembershipCard:default');
      }

      $_msg = sprintf('Permanentka ID=%s byla smazána.',$data->id);
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
      $params = self::array_to_object(array('id' => $id));

      $data = $this->membershipCardManager->getPermanentka($params);

      $this->template->data = $data;

      $this->eventlog('membership_card','Editace permanenty ID='.$id.'.');
    }


    /**
     * Formuláře pro permanentku
     *
     * @return Form
     */
    protected function createComponentPermanentkaForm(): Form
    {
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
        ->setHtmlAttribute('ID','frm-permanentkaForm-id');

      $form->addSelect('aktivita_id','Aktivita:',$this->aktivita)
        ->setHtmlAttribute('class','form-control');

      $form->addText('nazev','Název:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      $form->addText('cena','Cena:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('platnost','Platnost (dny):')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('vstupy','Počet vstupů:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
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
        $_msg = sprintf('Chyba! Neplatná operace pro permanentku ID=%s.',$data->id);
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
          $this->membershipCardManager->insertPermanentka($data);
        }
        catch (\Exception $e)
        {
          $_msg = sprintf('Chyba! Nová permanentka nebyla uložena.');
          $this->flashMessage($_msg,'danger');
          $this->eventlog('membership_card',$_msg);
          $this->redirect('MembershipCard:default');
        }

        $_msg = sprintf('Nová permanentka byla uložena.');
        $this->flashMessage($_msg);
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
      }

      if ($operace == 'update')
      {
        $data->updated_by = $this->userName;
        try
        {
          $this->membershipCardManager->updatePermanentka($data);
        }
        catch (\Exception $e)
        {
          $_msg = sprintf('Chyba! Permanentka ID=%s nebyla uložena.',$data->id);
          $this->flashMessage($_msg,'danger');
          $this->eventlog('membership_card',$_msg);
          $this->redirect('MembershipCard:default');
        }

        $_msg = sprintf('Permanentka ID=%s byla uložena.',$data->id);
        $this->flashMessage($_msg);
        $this->eventlog('membership_card',$_msg);
        $this->redirect('MembershipCard:default');
      }
    }
  }
