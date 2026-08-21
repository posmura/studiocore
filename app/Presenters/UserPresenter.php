<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;
  use Nette\Application\UI\Multiplier;
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
     * Seznam aktivit
     * @var array
     */
    public $_aktivity;


    /**
     * Inicalizace presenteru
     *
     * @return void
     */
    public function startup(): void
    {
      parent::startup();

      $this->requireLogin();

      // načtu seznam aktivit
      $this->_aktivity = $this->aktivita;
      if (isset($this->_aktivity[0]))
        unset($this->_aktivity[0]);
      ksort($this->_aktivity);
    }


    /**
     * Seznam všech uživatelů
     *
     * @return void
     */
    public function renderDefault(): void
    {
      $this->requireStaff();

      // načtu klienty
      $data = $this->userManager->getAllUsers();

      // doplním kredity pro jednotlivé aktivity klienta
      foreach ($data as $key => $items)
      {
        $kredity = $this->userManager->getKredityKlienta($items['id'],$this->userName);
        $data[$key]['kredity'] = $kredity;
      }

      $this->template->users = $data;

      // načtu názvy aktivit pro zobrazní v šabloně
      $this->template->aktivita = $this->aktivita;
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

      $this->requireUserAccess((int) $userID);

      $ts = strtotime(date('Y-m-d 23:59:59'));

      $data = self::array_to_object(
          [
            'id' => (int) $userID,
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

      // kredity - seznam aktivit
      $this->template->_aktivity = $this->_aktivity;

      // kredity - seznam hpdnot kreditů jednotlivých aktivit
      $this->template->_kredity = $this->userManager->getKredityKlienta($data->id,$this->userName);
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
      $this->requireAdmin();

      $this->error('Mazání uživatele je nutné provést formulářem.',405);
    }


    /**
     * Formuláře pro mazání uživatele.
     *
     * @return Multiplier
     */
    protected function createComponentDeleteUserForm(): Multiplier
    {
      $this->requireAdmin();

      return new Multiplier(function (string $id)
      {
        $form = new Form;
        $form->setHtmlAttribute('style','display:inline;');
        $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');
        $form->addHidden('userId',$id);
        $form->addSubmit('send',\Nette\Utils\Html::el('i')->setAttribute('class','bi bi-trash'))
          ->renderAsButton()
          ->setHtmlAttribute('class','btn btn-sm btn-danger')
          ->setHtmlAttribute('title','Smazat')
          ->setHtmlAttribute('aria-label','Smazat')
          ->setHtmlAttribute('onclick',"return confirm('Opravdu chcete záznam odstranit?');");
        $form->onSuccess[] = [$this,'deleteUserFormSucceeded'];

        return $form;
      });
    }


    /**
     * Akce po odeslání formuláře pro mazání uživatele.
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
    public function deleteUserFormSucceeded(Form $form,$data): void
    {
      $this->requireAdmin();

	      $userId = (int) $data->userId;
	      $deleteBy = $this->userName;
	      $userLabel = $this->logUserLabelById($userId);

	      try
	      {
        $this->userManager->deleteUser($userId,$deleteBy);
      }
	      catch (\Exception $e)
	      {
	        $this->eventlog('user','Chyba! Uživatel '.$userLabel.' nebyl odstraněn uživatelem '.$deleteBy.'.');
	        $this->flashMessage('Chyba! Uživatel '.$userLabel.' nebyl odstraněn uživatelem '.$deleteBy.'.');
	        $this->redirect('User:');
	      }

	      $this->eventlog('user','Uživatel '.$userLabel.' byl odstraněn uživatelem '.$deleteBy.'.');
	      $this->flashMessage('Uživatel '.$userLabel.' byl odstraněn uživatelem '.$deleteBy.'.');

      $this->redirect('User:');
    }


    /**
     * Formulář pro editaci uživatele
     *
     * @return Form
     */
    protected function createComponentUserForm(): Form
    {
      $this->requireLogin();

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
        ->addRule(Form::PATTERN,'%label může obsahovat jen čísla a nepovinně znak + na začátku.','^\+?[0-9]+$')
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
      $this->requireUserAccess((int) $data->id);

      if (!$this->isAdmin())
      {
        $userData = $this->userManager->getUserByID(self::array_to_object(['id' => (int) $data->id]));
        if (!$userData)
          $this->denyAccess();

        $data->role = $userData['role'];
      }

      $data->updated_by = $this->userName;

      try
      {
        $this->userManager->updateUser($data);
      }
      catch (\Exception $e)
      {
	        $this->flashMessage('Chyba! Uživatelský účet \''.$data->username.'\' nebyl aktualizován!','danger');
	        $this->eventlog('sign',sprintf('Chyba! Uživatel %s nebyl aktualizován!',$this->logUserLabel($data,(int) $data->id)));
	        $this->redirect('User:user',$data->id);
	      }

	      $this->flashMessage('Uživatelský účet \''.$data->username.'\' byl aktualizován.');
	      $this->eventlog('sign',sprintf('Uživatelský účet %s byl aktualizován!',$this->logUserLabel($data,(int) $data->id)));
      $this->redirect('User:user',$data->id);
    }


    /**
     * Definice formuláře pro změnu hesla uživatele
     *
     * @return Form
     */
    protected function createComponentUserpasswordForm(): Form
    {
      $this->requireLogin();

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
      $this->requireUserAccess((int) $data->id);

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
	        $this->eventlog('sign',sprintf('Chyba! Heslo pro uživatelský účet %s nebylo změněno!',$this->logUserLabelById((int) $data->id)));
	        $this->redirect('User:user',$data->id);
	      }

	      $this->flashMessage('Heslo pro uživatelský účet \''.$data->username.'\' bylo změněno!');
	      $this->eventlog('sign',sprintf('Heslo pro uživatelský účet %s bylo změněno!',$this->logUserLabelById((int) $data->id)));
      $this->redirect('User:user',$data->id);
    }


    /**
     * Definice formuláře pro změnu kreditu
     *
     * @return Form
     */
    protected function createComponentKreditForm(): Form
    {
      $this->requireStaff();

      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('user_id')
        ->setHtmlAttribute('ID','frm-kreditForm-user_id');

      foreach ($this->_aktivity as $key => $items)
      {
        $form->addInteger('kredit_'.$key,$items.':')
          ->addRule(Form::PATTERN,'Kredit pro aktivitu %label může obsahovat jen číslice a nepovinně znak + nebo - na začátku.','[+-]?[0-9]+')
          ->setValue(0)
          ->setHtmlAttribute('class','form-control form-control-sm')
          ->setHtmlAttribute('placeholder','')
          ->setHtmlAttribute('inputmode','numeric')
          ->setHtmlAttribute('pattern','[+-]?[0-9]+')
          ->setHtmlAttribute('title','Zadejte celé číslo, například -1, 0 nebo +1.')
          ->setRequired("Kredit pro aktivitu %label je vyžadován!");
      }

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success btn-sm');

      $form->onSuccess[] = [$this,'formKreditFormSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro změnu kreditu
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
    public function formKreditFormSucceeded(Form $form,$data): void
    {
      $this->requireStaff();

      $data->updated_by = $this->userName;

      $params = array();

      // vytvořím data pro aktualizace
      foreach ($data as $key => $items)
      {
        if (substr($key,0,7) == 'kredit_')
        {
          $_kredit = explode('_',$key);
          $_aktivita_id = $_kredit[1];

          $params[$_aktivita_id] = array(
            'user_id' => $data->user_id,
            'aktivita_id' => $_aktivita_id,
            'kredit' => $items,
            'updated_by' => $this->userName,
          );
        }
      }

      // vlastní aktualizace kreditu
      $_updates = array();
      foreach ($params as $key => $items)
      {
        if (!$items['kredit'] || $items['kredit'] == 0)
          continue;

        $_data = self::array_to_object($items);

        try
        {
          $_updates[(int) $_data->aktivita_id] = $this->factoryManager->updateKredit($_data);
        }
        catch (\Exception $e)
        {
	          $_msg = sprintf('Chyba! Kredity pro uživatele %s, aktivita %s, nebyly změněny o %s.',$this->logUserLabelById((int) $data->user_id),$this->logActivityLabelById((int) $_data->aktivita_id),$_data->kredit);
          $this->flashMessage($_msg,'danger');
          $this->eventlog('sign',$_msg);
          $this->redirect('User:user',$data->user_id);
          }
      }

	      $_changes = array();
	      foreach ($params as $items)
	      {
	        if (!$items['kredit'] || $items['kredit'] == 0)
	          continue;
	        $_sources = $_updates[(int) $items['aktivita_id']]['sources'] ?? array();
	        $_source_info = $this->formatKreditUpdateSources($_sources);
	        $_changes[] = sprintf(
	          '%s: %+d%s',
	          $this->logActivityLabelById((int) $items['aktivita_id']),
	          (int) $items['kredit'],
	          $_source_info !== '' ? sprintf(' (%s)',$_source_info) : ''
	        );
	      }
	      if (!$_changes)
	      {
	        $_msg = sprintf('Kredity pro uživatele %s nebyly změněny, nebyla zadána žádná nenulová hodnota.',$this->logUserLabelById((int) $data->user_id));
	        $this->flashMessage($_msg,'info');
	        $this->redirect('User:user',$data->user_id);
	      }
	      $_msg = sprintf('Kredity pro uživatele %s byly změněny (%s).',$this->logUserLabelById((int) $data->user_id),implode(', ',$_changes));
      $this->flashMessage($_msg);
      $this->eventlog('sign',$_msg);
      $this->redirect('User:user',$data->user_id);
    }


    /**
     * Vrací textový popis zdrojů ruční změny kreditu.
     */
    private function formatKreditUpdateSources(array $sources): string
    {
      if (!$sources)
        return '';

      $_items = array();
      foreach ($sources as $source)
      {
        $_zmena = (int) ($source['zmena'] ?? 0);
        $_count = $this->formatKreditEntryCount(abs($_zmena));

        if (($source['type'] ?? '') === 'permanentka')
        {
          $_sale_id = (int) ($source['sales_id'] ?? 0);
          $_label = trim((string) ($source['aktivita_name'] ?? ''));
          if ($_label === '')
            $_label = $_sale_id > 0 ? sprintf('prodej ID=%d',$_sale_id) : 'aktivní permanentka';
          elseif ($_sale_id > 0)
            $_label = sprintf('%s, prodej ID=%d',$_label,$_sale_id);

          $_details = array();
          if (!empty($source['datum_konce']))
            $_details[] = sprintf('platná do %s',date('d.m.Y',(int) $source['datum_konce']));
          if (array_key_exists('vstupy_po',$source))
            $_details[] = sprintf('zbývá %s',$this->formatKreditEntryCount((int) $source['vstupy_po']));

          $_items[] = sprintf(
            'odečteno %s z aktivní permanentky %s%s',
            $_count,
            $_label,
            $_details ? sprintf(' [%s]',implode(', ',$_details)) : ''
          );
        }
        elseif (($source['type'] ?? '') === 'kredit')
        {
          $_items[] = $_zmena < 0
            ? sprintf('odečteno %s z ručního kreditu',$_count)
            : sprintf('přidáno %s do ručního kreditu',$_count);
        }
      }

      return implode('; ',$_items);
    }


    /**
     * Vrací správný tvar textu pro počet vstupů.
     */
    private function formatKreditEntryCount(int $count): string
    {
      $count = abs($count);

      if ($count === 1)
        return '1 vstup';

      if ($count >= 2 && $count <= 4)
        return sprintf('%d vstupy',$count);

      return sprintf('%d vstupů',$count);
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


    /**
     * Vrací příznak, zda může přihlášený uživatel pracovat s profilem.
     */
    private function canAccessUser(int $userID): bool
    {
      return $this->isStaff() || $userID === (int) $this->userID;
    }


    /**
     * Vyžaduje přístup k danému profilu.
     */
    private function requireUserAccess(int $userID): void
    {
      $this->requireLogin();

      if ($this->canAccessUser($userID))
        return;

      $this->denyAccess();
    }
  }
