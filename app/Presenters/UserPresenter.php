<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;
  use Nette\Security\Passwords;
  use Nette\Utils\Arrays;

  /**
   * Třída pro správu uživatelů
   */
  final class UserPresenter extends BasePresenter
  {

    /**
     * Minimální délka hesla
     */
    const PWD_MIN_LENGTH = 8;

    /**
     * Maximální délka hesla
     */
    const PWD_MAX_LENGTH = 50;


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

      $data = $this->userManager->getAllUsers();
      foreach ($data as $key => $items)
      {
        $kredity = $this->userManager->getKredityKlienta($items['id'], $this->userName);
        $data[$key]['kredity'] = $kredity;
      }

      $this->template->aktivita = $this->aktivita;
      $this->template->users = $data;
    }


    /**
     * Uživatel
     *
     * @return void
     */
    public function renderUser($userID): void
    {
      if (!$userID)
        $userID = $this->userID;

      $ts = strtotime(date('Y-m-d 23:59:59'));

      $data = self::array_to_object(
        [
          'id' => $userID,
          'ts' => $ts,
        ]
      );

      $this->template->user_id = $userID;
      $this->template->lektor = $this->lektorName;

      // data uživatele
      $this->template->user_data = $this->userManager->getUserByID($data);

      // nákupy uživatele
      $this->template->nakup_data = $this->salesManager->getNakup($data);

      // registrace uživatele
      $this->template->registrace_data = $this->userManager->getRegistraceByUserID($data);
    }


    /**
     * Smazání uživatele
     *
     * Uživatel není smazán z tabulky, ale je změněna hodnota sloupce deleted z 0 na 1
     *
     * @return void
     */
    public function renderDelete($userId,$deleteBy): void
    {
      try
      {
        $this->userManager->deleteUser($userId,$deleteBy);
      }
      catch (\Exception $e)
      {
        $this->eventlog('user','Chyba! Uživatel ID='.$userId.' nebyl odstraněn uživatelem '.$deleteBy.'.');
        $this->flashMessage('Chyba! Uživatel ID='.$userId.' nebyl odstraněn uživatelem '.$deleteBy.'.');
      }

      $this->eventlog('user','Uživatel ID='.$userId.' byl odstraněn uživatelem '.$deleteBy.'.');
      $this->flashMessage('Uživatel ID='.$userId.' byl odstraněn uživatelem '.$deleteBy.'.');

      $this->redirect('User:');
    }


    /**
     * Formulář pro editaci uživatele
     *
     * @return Form
     */
    protected function createComponentUserForm(): Form
    {
      // seznam uživatelských rolí
      $_roles = $this->userManager->getListFromEnum(
        array(
          'db' => self::DB_NAME,
          'tbl' => 'blog_users',
          'col' => 'role',
        )
      );

      // seznam benefitů
      $_benefit_card = $this->userManager->getListFromEnum(
        array(
          'db' => self::DB_NAME,
          'tbl' => 'blog_users',
          'col' => 'benefit_card',
        )
      );

      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('id')
        ->setHtmlAttribute('ID','frm-userForm-id');

      $form->addHidden('username')
        ->setHtmlAttribute('ID','frm-userForm-username');

      $form->addText('surname','Příjmení:')
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      $form->addText('firstname','Jméno:')
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      if ($this->role === 'admin')
      {
        $form->addSelect('role','Oprávnění:',$_roles)
          ->setHtmlAttribute('class','form-control form-control-sm');
      }
      else
      {
        $form->addText('role','Oprávnění:')
          ->setHtmlAttribute('class','form-control form-control-sm')
          ->setHtmlAttribute('placeholder','')
          ->setHtmlAttribute('readonly','readonly')
          ->setHtmlAttribute('style','background: #f3f3f3;')
          ->setHtmlAttribute('title','Automaticky vyplňované pole')
          ->setHtmlAttribute('onclick','alert("Automaticky vyplňované pole");')
          ->setRequired('%label je vyžadováno!');
      }

      $form->addText('email','E-mail:')
        ->addRule($form::EMAIL,'%label není validní!')
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadován!');

      $form->addText('email_verify','Potvrzení e-mailu:')
        ->addRule($form::EQUAL,'%label se neshoduje!',$form['email'])
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      $form->addText('mobil_number','Mobilní telefon:')
        ->addRule(Form::PATTERN, '%label může obsahovat jen čísla a nepovinně znak + na začátku.', '^\+?[0-9]+$')
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadován!');

      $form->addText('mobil_number_verify','Potvrzení mobilního telefonu:')
        ->addRule($form::EQUAL,'%label se neshoduje!',$form['mobil_number'])
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      $form->addSelect('benefit_card','Benefitní karta:',$_benefit_card)
        ->setHtmlAttribute('class','form-control form-control-sm');

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success btn-sm');

      $form->onSuccess[] = [$this,'formUserFormSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro editaci
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
    public function formUserFormSucceeded(Form $form,$data): void
    {
      $data->updated_by = $this->userName;

      try
      {
        $this->userManager->updateUser($data);
      }
      catch (\Exception $e)
      {
        $this->flashMessage('Chyba! Uživatelský účet \''.$data->username.'\' nebyl aktualizován!','danger');
        $this->eventlog('sign','Chyba! Uživatel \''.$data->username.'\' nebyl aktualizován!');
        $this->redirect('User:user',$data->id);
      }

      $this->flashMessage('Uživatelský účet \''.$data->username.'\' byl aktualizován.');
      $this->eventlog('sign','Uživatelský účet \''.$data->username.'\' byl aktualizován!');
      $this->redirect('User:user',$data->id);
    }


    /**
     * Definice formuláře pro změnu hesla uživatele
     *
     * @return Form
     */
    protected function createComponentUserpasswordForm(): Form
    {
      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('id')
        ->setHtmlAttribute('ID','frm-userpasswordForm-id');

      $form->addHidden('username')
        ->setHtmlAttribute('ID','frm-userpasswordForm-username');

      $form->addPassword('password','Heslo:')
        ->addRule([$this->userManager::class,'validate_strong_password'],
          sprintf(
            'Heslo musí mít %d – %d znaků, obsahovat alespoň jedno malé písmeno, jedno velké písmeno, číslici a speciální znak.',
            $this->userManager::PASSWORD_MIN_LENGTH,
            $this->userManager::PASSWORD_MAX_LENGTH
          )
        )
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addPassword('password_verify','Potvrzení hesla:')
        ->addRule($form::EQUAL,"%label se neshoduje!",$form['password'])
        ->setHtmlAttribute('class','form-control form-control-sm')
        ->setHtmlAttribute('placeholder','')
        ->setOmitted()
        ->setRequired("%label je vyžadováno!");

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success btn-sm');

      $form->onSuccess[] = [$this,'formUserpasswordFormSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro registraci
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
    public function formUserpasswordFormSucceeded(Form $form,$data): void
    {
      $data->updated_by = $this->userName;

      // nastavení hesla
      $passwords = new Passwords();
      $data->password_hash = $passwords->hash($data->password);

      try
      {
        $this->userManager->updateUserPassword($data);
      }
      catch (\Exception $e)
      {
        $this->flashMessage('Chyba! Heslo pro uživatelský účet \''.$data->username.'\' nebylo změněno!','danger');
        $this->eventlog('sign','Heslo pro uživatelský účet \''.$data->username.'\' nebylo změněno!');
        $this->redirect('User:user',$data->id);
      }

      $this->flashMessage('Heslo pro uživatelský účet \''.$data->username.'\' bylo změněno!');
      $this->eventlog('sign','Heslo pro uživatelský účet \''.$data->username.'\' bylo změněno!');
      $this->redirect('User:user',$data->id);
    }


    /**
     * Načte pole všech uživatelských jmen
     *
     * @return void
     */
    public function setAllUsernames(): void
    {
      $this->userNames = $this->userManager->getAllUsernames();
    }


    /**
     * Vrací pole všech uživatelských jmen
     *
     * @return array
     */
    public function getAllUsernames(): array
    {
      return $this->userNames;
    }


    /**
     * Zkontroluje uživatelské jméno na duplicitu
     *
     * @param type $userName pole všech uživatelských jmen
     * @return bool
     */
    public function checkUsername($userName): bool
    {
      return in_array(mb_strtolower($userName),$this->userNames);
    }
  }
