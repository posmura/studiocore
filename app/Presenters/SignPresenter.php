<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette;
  use Nette\Application\UI\Form;
  use Nette\Security\Passwords;
  use App\Model\UserManager;
  use Tracy\Debugger;
  use Nette\Mail\Mailer;
  use Nette\Mail\Message;
  use Nette\Mail\SendmailMailer;
  use Nette\Utils\Arrays;
  use BulkGate\Sdk\Sender;
  use BulkGate\Sdk\Message\Sms;

  /**
   * Třída pro přihlášení a registraci uživatele
   */
  final class SignPresenter extends BasePresenter
  {
    /** @var Sender @inject */
    public $sender;

    /**
     * Pole přihlašovacích jmen uživatelů
     * @var array
     */
    public $userNames;

    /**
     * Onjekt třídy UserManager
     * @var object
     */
    public $userManager;

    /**
     * Výchozí uživatelské jméno pro formulář
     * @var string
     */
    public $userNameForm;


    /**
     * Po spuštění
     * @return void
     */
    public function startup(): void
    {
      parent::startup();
      $this->setAllUsernames();
    }


    /**
     * Výchozí obrazovka
     *
     * @return void
     */
    public function renderDefault(): void
    {
      if ($this->getUser()->isLoggedIn())
      {
        $this->redirect('Homepage:');
      }
      else
      {
        $this->redirect('Sign:in');
      }
      die;
    }


    /**
     * Přihlášení uživatele
     *
     * @param string $userNameForm Jméno uživatele
     * @return void
     */
    public function renderIn($userNameForm = ''): void
    {
      $this->userNameForm = $userNameForm;
    }


    /**
     * Registrace uživatele
     *
     * @return void
     */
    public function renderUp(): void
    {

    }


    /**
     * Odhlášení uživatele
     *
     * @return void
     */
    public function renderOut(): void
    {
      $this->eventlog('sign','Uživatel byl odhlášen.');
      $this->getUser()->logout();
      //$this->redirect('Sign:in');
      $this->redirect('Homepage:');
    }


    /**
     * Obnova hesla
     *
     * @return void
     */
    public function renderRecovery(): void
    {

    }


    /**
     * Aktualizace hesla
     *
     * @return void
     */
    public function renderUpdatepassword(): void
    {

    }


    /**
     * Definice formuláře pro přihlášení
     *
     * @return Form
     */
    protected function createComponentSigninForm(): Form
    {
      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addText('username','Uživatelské jméno:')
        ->addRule($form::PATTERN,'%label může obsahovat pouze písmena anglické abecedy a číslice, délka '.$this->userManager::USERNAME_MIN_LENGTH.' až '.$this->userManager::USERNAME_MAX_LENGTH.' znaků.','^[A-Za-z0-9]{'.$this->userManager::USERNAME_MIN_LENGTH.','.$this->userManager::USERNAME_MAX_LENGTH.'}$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addPassword('password','Heslo:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addCheckbox('remember','Zapamatovat')
        ->setHtmlAttribute('class','form-check-input');

      $form->addSubmit('send','Přihlášení')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formSigninSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro přihlášení
     *
     * @param Form $form Objekt formuláže
     * @param type $data Data z formuláře
     * @return void
     */
    public function formSigninSucceeded(Form $form,$data): void
    {
      try
      {
        $user = $this->getUser();
        $this->user->setExpiration($data->remember ? '14 days' : '20 minutes',!$data->remember);
        $this->user->login($data->username,$data->password);
        $this->eventlog('sign','Uživatel \''.$data->username.'\' byl přihlášen.');
        $this->redirect('Homepage:default');
      }
      catch (Nette\Security\AuthenticationException $e)
      {
        $_msg = $e->getMessage();
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sign',$_msg);
        $this->redirect('Sign:in');
      }
      /*
        catch (\Throwable $e)
        {
        // neočekávané chyby (DB, síť…): zalogovat a být hodný na uživatele
        Debugger::log($e);                     // uloží do logu
        //$form->addError('Něco se pokazilo, zkuste to prosím znovu.');
        $this->flashMessage('2 Neplatné uživatelské jméno nebo heslo!','danger');
        $this->eventlog('sign','2 Neplatné uživatelské jméno nebo heslo!');
        $this->redirect('Sign:in');
        }
       *
       */
    }


    /**
     * Definice formuláře pro registraci
     *
     * @return Form
     */
    protected function createComponentSignupForm(): Form
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

      $form->addText('username','Uživatelské jméno:')
        ->addRule($form::PATTERN,'%label může obsahovat pouze písmena anglické abecedy a číslice, délka '.$this->userManager::USERNAME_MIN_LENGTH.'–'.$this->userManager::USERNAME_MAX_LENGTH.' znaků.','^[A-Za-z0-9]{'.$this->userManager::USERNAME_MIN_LENGTH.','.$this->userManager::USERNAME_MAX_LENGTH.'}$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addText('surname','Příjmení:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addText('firstname','Jméno:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addPassword('password','Heslo:')
        ->addRule([$this->userManager::class,'validate_strong_password'],
          sprintf(
            'Heslo musí mít %d – %d znaků, obsahovat alespoň jedno malé písmeno, jedno velké písmeno, číslici a speciální znak.',
            $this->userManager::PASSWORD_MIN_LENGTH,
            $this->userManager::PASSWORD_MAX_LENGTH
          )
        )
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addPassword('password_verify','Potvrzení hesla:')
        ->addRule($form::EQUAL,"%label se neshoduje!",$form['password'])
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setOmitted()
        ->setRequired("%label je vyžadováno!");

      $form->addText('email','E-mail:')
        ->addRule($form::EMAIL,"%label není validní!")
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadován!");

      $form->addText('email_verify','Potvrzení e-mailu:')
        ->addRule($form::EQUAL,"%label se neshoduje!",$form['email'])
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addText('mobil_number','Mobilní telefon:')
        ->addRule(Form::PATTERN,'%label může obsahovat jen čísla a nepovinně znak + na začátku.','^\+?[0-9]+$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadován!");

      $form->addText('mobil_number_verify','Potvrzení mobilního telefonu:')
        ->addRule($form::EQUAL,'%label se neshoduje!',$form['mobil_number'])
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      $form->addSelect('benefit_card','Benefitní karta:',$_benefit_card)
        ->setHtmlAttribute('class','form-control');

      $form->addSubmit('send','Registrovat')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formSignupSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro registraci
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
    public function formSignupSucceeded(Form $form,$data): void
    {
      $data = $this->userManager->checkData($data);

      // nastavení hesla
      $passwords = new Passwords();
      $data->password_hash = $passwords->hash($data->password);

      // nastavení role
      $data->role = 'klient';

      if ($this->checkUsername($data->username))
      {
        $this->flashMessage('Uživateslké jméno \''.$data->username.'\' již existuje!','danger');
        $this->eventlog('sign','Uživateslké jméno \''.$data->username.'\' již existuje!');
        $this->redirect('Sign:up');
      }

      try
      {
        $this->userManager->insertUser($data);
        //$this->flashMessage('Váš uživatelský účet \''.$data->username.'\' byl vytvořen. Můžete se přihlásit.');
      }
      catch (\Exception $e)
      {
        $this->flashMessage('Chyba! Uživatelský účet \''.$data->username.'\' nebyl vytvořen!','danger');
        $this->eventlog('sign','Chyba! Uživatel \''.$data->username.'\' nebyl vytvořen!');
        $this->redirect('Sign:up');
      }

      $this->flashMessage('Uživatelský účet byl \''.$data->username.'\' vytvořen! Můžete se přihlásit.');
      $this->eventlog('sign','Uživatelský účet \''.$data->username.'\' byl vytvořen!');
      $this->redirect('Sign:in',$data->username);
    }


    /**
     * Definice formuláře pro obnovu hesla
     *
     * @return Form
     */
    protected function createComponentSignrecoveryForm(): Form
    {
      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addText('username','Uživatelské jméno:')
        ->addRule($form::PATTERN,'%label může obsahovat pouze písmena anglické abecedy a číslice, délka '.$this->userManager::USERNAME_MIN_LENGTH.'–'.$this->userManager::USERNAME_MAX_LENGTH.' znaků.','^[A-Za-z0-9]{'.$this->userManager::USERNAME_MIN_LENGTH.','.$this->userManager::USERNAME_MAX_LENGTH.'}$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addText('mobil_number','Mobilní telefon:')
        ->addRule(Form::PATTERN,'%label může obsahovat jen čísla a nepovinně znak + na začátku.','^\+?[0-9]+$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadován!");

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formSignrecoverySucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro obnovení hesla
     *
     * @param Form $form Objekt formuláže
     * @param type $data Data z formuláře
     * @return void
     */
    public function formSignrecoverySucceeded(Form $form,$data): void
    {
      $user = $this->userManager->getUserByMobilNumber($data);

      if (!$user || count($user) !== 1)
      {
        $this->flashMessage('Chyba! Uživatelské jméno nebo číslo mobilního telefonu nejsou platná!','danger');
        $this->eventlog('sign','Chyba! Uživatelské jméno nebo číslo mobilního telefonu nejsou platná');
        $this->redirect('Sign:recovery');
      }

      $pin = UserManager::generatePin();

      // uložím pin k uživatelovi
      $_params = array(
        "id" => $user[0]['id'],
        "username" => $user[0]['username'],
        "mobil_number" => $user[0]['mobil_number'],
        "password_recovery_pin" => $pin,
      );
      $_data = $this->array_to_object($_params);
      $this->userManager->updatePasswordRecoveryPin($_data);

      // připravím text pro SMS
      $pin_text = sprintf("Rezervační sytém STUDIO CORE: PIN pro obnovu hesla je %s",$pin);
      //$this->flashMessage($pin_text);

      $this->eventlog('sign',$pin_text);

      // validace telefonního čísla
      if (!$this->checkSmsPhone($_data->mobil_number))
      {
        $_msg = sprintf('Chyba! Chybný formát telefonního čísla \'%s\'. SMS pro \'%s\' nebyla odeslána.',
          $_data->mobil_number,
          $_data->username,
        );
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sign',$_msg);

        $this->redirect('Sign:Recovery');
      }

      // odeslání SMS
      if ($this->sender->send(new Sms($_data->mobil_number,$pin_text)))
      {
        $_msg = sprintf('SMS \'%s\' (%s) byla předána k odeslání.',
          $_data->mobil_number,
          $_data->username,
        );
        $this->flashMessage($_msg);
        $this->eventlog('sing',$_msg);

        $this->redirect('Sign:Recoverypassword');
      }
      else
      {
        $_msg = sprintf('Chyba! SMS \'%s\' (%s) nemohla být předána k odeslání.',
          $_data->mobil_number,
          $_data->username,
        );

        $this->flashMessage($_msg,'danger');
        $this->eventlog('sing',$_msg);

        $this->redirect('Sign:Recovery');
      }
    }


    /**
     * Definice formuláře pro aktualizaci hesla
     *
     * @return Form
     */
    protected function createComponentSignupdatepasswordForm(): Form
    {
      $form = new Form;

      $form->addText('username','Uživatelské jméno:')
        //->setValue($this->userNameForm)
        ->addRule($form::PATTERN,'%label může obsahovat pouze písmena anglické abecedy a číslice, délka '.$this->userManager::USERNAME_MIN_LENGTH.'–'.$this->userManager::USERNAME_MAX_LENGTH.' znaků.','^[A-Za-z0-9]{'.$this->userManager::USERNAME_MIN_LENGTH.','.$this->userManager::USERNAME_MAX_LENGTH.'}$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addText('password_recovery_pin','PIN pro obnovu hesla:')
        ->addRule($form::PATTERN,'%label může obsahovat pouze písmena anglické abecedy a číslice, délka '.$this->userManager::PASSWORD_RECOVERY_PIN_LENGTH.' znaků.','^[A-Z0-9]{'.$this->userManager::PASSWORD_RECOVERY_PIN_LENGTH.'}$')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addPassword('password','Nové heslo:')
        ->addRule([$this->userManager::class,'validate_strong_password'],
          sprintf(
            'Heslo musí mít %d – %d znaků, obsahovat alespoň jedno malé písmeno, jedno velké písmeno, číslici a speciální znak.',
            $this->userManager::PASSWORD_MIN_LENGTH,
            $this->userManager::PASSWORD_MAX_LENGTH
          )
        )
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired("%label je vyžadováno!");

      $form->addPassword('password_verify','Potvrzení nového hesla:')
        ->addRule($form::EQUAL,"%label se neshoduje!",$form['password'])
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setOmitted()
        ->setRequired("%label je vyžadováno!");

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formSignupdatepasswordSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře s aktualizací hesla
     *
     * @param Form $form Objekt formuláže
     * @param type $data Data z formuláře
     * @return void
     */
    public function formSignupdatepasswordSucceeded(Form $form,$data): void
    {
      $user = $this->userManager->getUserByPasswordRecoveryPin($data);

      if (!$user || count($user) !== 1)
      {
        $this->flashMessage('Chyba! Uživatelské jméno nebo PIN pro obnovu hesla nejsou platná!','danger');
        $this->eventlog('sign','Chyba! Uživatelské jméno nebo PIN pro obnovu hesla nejsou platná');
        $this->redirect('Sign:Recoverypassword');
      }

      $data->id = $user[0]['id'];

      // nastavení hesla
      $passwords = new Passwords();
      $data->password_hash = $passwords->hash($data->password);

      // změna hesla
      $rst = $this->userManager->updatePassword($data);
      if (!$rst)
      {
        $this->flashMessage('Chyba! Heslo pro '.$data->username.' nebylo změněno!','danger');
        $this->eventlog('sign','Chyba! Heslo pro '.$data->username.' nebylo změněno!');
        $this->redirect('Sign:Recoverypassword');
      }

      $this->flashMessage('Heslo pro '.$data->username.' bylo změněno.');
      $this->eventlog('sign','Heslo pro '.$data->username.' bylo změněno.');
      $this->redirect('Sign:In');
    }


    /**
     * Nastaví pole přihlašovacích jmen uživatelů
     *
     * @return void
     */
    public function setAllUsernames(): void
    {
      $this->userNames = $this->userManager->getAllUsernames();
    }


    /**
     * Vrací pole přihlašovacích jmen uživatelů
     *
     * @return array
     */
    public function getAllUsernames(): array
    {
      return $this->userNames;
    }


    /**
     * Test na existenci uživatelského jména
     *
     * @param type $userName Přihlašovací jméno uživatele
     * @return bool
     */
    public function checkUsername($userName): bool
    {
      return in_array(mb_strtolower($userName),$this->userNames);
    }
  }
