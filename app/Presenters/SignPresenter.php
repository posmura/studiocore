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
    const LOGIN_MAX_ATTEMPTS = 5;
    const LOGIN_WINDOW_SECONDS = 900;
    const LOGIN_LOCK_SECONDS = 900;
    const RECOVERY_REQUEST_MAX_ATTEMPTS = 3;
    const RECOVERY_REQUEST_WINDOW_SECONDS = 900;
    const RECOVERY_REQUEST_LOCK_SECONDS = 900;
    const RECOVERY_PIN_SESSION_MAX_ATTEMPTS = 5;
    const RECOVERY_PIN_SESSION_WINDOW_SECONDS = 900;
    const RECOVERY_PIN_SESSION_LOCK_SECONDS = 900;

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
	      $this->requireLogin();
	      $this->error('Odhlášení je nutné provést formulářem.',405);
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
      $data->username = mb_strtolower((string) $data->username);
      $blockedSeconds = $this->getRateLimitRemaining('login',$data->username);
      if ($blockedSeconds > 0)
      {
        $_msg = sprintf('Příliš mnoho neúspěšných pokusů o přihlášení. Zkuste to prosím znovu za %d minut.',(int) ceil($blockedSeconds / 60));
        $this->flashMessage($_msg,'danger');
        $this->eventlog('sign',sprintf('Přihlášení uživatele "%s" bylo dočasně blokováno kvůli opakovaným neúspěšným pokusům.',$data->username));
        $this->redirect('Sign:in');
      }

      try
      {
        $this->user->setExpiration($data->remember ? '14 days' : '30 minutes',true);
        $this->user->login($data->username,$data->password);
        $this->clearRateLimit('login',$data->username);
	        $this->eventlog('sign',sprintf('Uživatel %s byl přihlášen.',$this->logUserLabel($this->getUser()->identity)));
        $this->redirect('Homepage:default');
	      }
	      catch (Nette\Security\AuthenticationException $e)
	      {
        $remaining = $this->registerRateLimitFailure(
          'login',
          $data->username,
          self::LOGIN_MAX_ATTEMPTS,
          self::LOGIN_WINDOW_SECONDS,
          self::LOGIN_LOCK_SECONDS
        );
	        $_msg = 'Neplatné uživatelské jméno nebo heslo!';
	        $this->flashMessage($_msg,'danger');
	        $this->eventlog('sign',sprintf('Neúspěšné přihlášení uživatele "%s".',$data->username));
	        if ($remaining > 0)
	          $this->flashMessage(sprintf('Další pokusy jsou dočasně blokované. Zkuste to prosím znovu za %d minut.',(int) ceil($remaining / 60)),'warning');
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
        $this->flashMessage('Registraci se nepodařilo dokončit. Zkontrolujte prosím zadané údaje nebo zkuste jiné přihlašovací jméno.','danger');
	        $this->eventlog('sign','Registrace nového uživatele nebyla dokončena kvůli duplicitním nebo neplatným údajům.');
        $this->redirect('Sign:up');
      }

      try
      {
        $this->userManager->insertUser($data);
        //$this->flashMessage('Váš uživatelský účet \''.$data->username.'\' byl vytvořen. Můžete se přihlásit.');
      }
      catch (\Exception $e)
      {
        $this->flashMessage('Registraci se nepodařilo dokončit. Zkontrolujte prosím zadané údaje nebo zkuste jiné přihlašovací jméno.','danger');
	        $this->eventlog('sign','Registrace nového uživatele nebyla dokončena.');
        $this->redirect('Sign:up');
      }

      $this->flashMessage('Uživatelský účet byl \''.$data->username.'\' vytvořen! Můžete se přihlásit.');
	      $this->eventlog('sign',sprintf('Uživatelský účet %s byl vytvořen!',$this->logUserLabel($data)));
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
      $data->username = mb_strtolower((string) $data->username);
      $genericMsg = 'Pokud zadané údaje odpovídají existujícímu účtu, bude na uložené telefonní číslo odeslána SMS s PIN pro obnovu hesla.';
      $blockedSeconds = $this->getRateLimitRemaining('recovery-request',$data->username);
      if ($blockedSeconds > 0)
      {
        $this->flashMessage(sprintf('Žádost o obnovu hesla je dočasně blokovaná. Zkuste to prosím znovu za %d minut.',(int) ceil($blockedSeconds / 60)),'warning');
        $this->eventlog('sign',sprintf('Žádost o obnovu hesla pro uživatele "%s" byla dočasně blokována kvůli opakovaným pokusům.',$data->username));
        $this->redirect('Sign:recovery');
      }

      $user = $this->userManager->getUserByMobilNumber($data);

      if (!$user || count($user) !== 1)
      {
        $remaining = $this->registerRateLimitFailure(
          'recovery-request',
          $data->username,
          self::RECOVERY_REQUEST_MAX_ATTEMPTS,
          self::RECOVERY_REQUEST_WINDOW_SECONDS,
          self::RECOVERY_REQUEST_LOCK_SECONDS
        );
        $this->flashMessage($genericMsg);
        $this->eventlog('sign',sprintf('Žádost o obnovu hesla pro uživatele "%s" nebyla ověřena.',$data->username));
        if ($remaining > 0)
          $this->eventlog('sign',sprintf('Žádosti o obnovu hesla pro uživatele "%s" byly dočasně blokovány.',$data->username));
        $this->redirect('Sign:Recoverypassword');
      }

      $pin = UserManager::generatePin();
      $passwords = new Passwords();

      // uložím pin k uživatelovi
      $_params = array(
        "id" => $user[0]['id'],
        "username" => $user[0]['username'],
        "mobil_number" => $user[0]['mobil_number'],
        "password_recovery_pin" => $passwords->hash($pin),
      );
      $_data = $this->array_to_object($_params);
      $this->userManager->updatePasswordRecoveryPin($_data);
      $this->clearRateLimit('recovery-request',$data->username);

	      // připravím text pro SMS
	      $pin_text = sprintf("STUDIO CORE | Rezervacni system: PIN pro obnovu hesla je %s",$pin);

		      $this->eventlog('sign',sprintf('Byl vygenerován PIN pro obnovu hesla uživatele %s.',$this->logUserLabel($user[0],(int) $user[0]['id'])));

      // validace telefonního čísla
      if (!$this->checkSmsPhone($_data->mobil_number))
      {
        $this->userManager->clearPasswordRecoveryPin($_data);
        $_msg = sprintf('Chyba! Chybný formát telefonního čísla \'%s\'. SMS pro \'%s\' nebyla odeslána.',
          $_data->mobil_number,
	          $this->logUserLabel($user[0],(int) $user[0]['id']),
        );
        $this->flashMessage('Žádost o obnovu hesla se nepodařilo dokončit. Zkuste to prosím později.','danger');
        $this->eventlog('sign',$_msg);

        $this->redirect('Sign:Recovery');
      }

      // odeslání SMS
      $smsException = null;
      if ($this->sendSmsSafely($this->sender,$_data->mobil_number,$pin_text,$smsException))
      {
        $_msg = sprintf('SMS \'%s\' (%s) byla předána k odeslání.',
          $_data->mobil_number,
	          $this->logUserLabel($user[0],(int) $user[0]['id']),
	        );
	        $this->flashMessage($genericMsg);
	        $this->eventlog('sign',$_msg);

	        $this->redirect('Sign:Recoverypassword');
      }
      else
      {
        $this->userManager->clearPasswordRecoveryPin($_data);
        $_msg = $smsException
          ? sprintf('Chyba! SMS \'%s\' (%s) nelze odeslat, protože telefonní číslo není validní. Výjimka: %s',
            $_data->mobil_number,
	            $this->logUserLabel($user[0],(int) $user[0]['id']),
            $smsException->getMessage(),
          )
          : sprintf('Chyba! SMS \'%s\' (%s) nemohla být předána k odeslání.',
            $_data->mobil_number,
	            $this->logUserLabel($user[0],(int) $user[0]['id']),
          );

	        $this->flashMessage('Žádost o obnovu hesla se nepodařilo dokončit. Zkuste to prosím později.','danger');
	        $this->eventlog('sign',$_msg);

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

	      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

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
      $data->username = mb_strtolower((string) $data->username);
      $blockedSeconds = $this->getRateLimitRemaining('recovery-pin',$data->username);
      if ($blockedSeconds > 0)
      {
        $this->flashMessage(sprintf('Ověření PIN je dočasně blokované. Zkuste to prosím znovu za %d minut.',(int) ceil($blockedSeconds / 60)),'warning');
        $this->eventlog('sign',sprintf('Ověření PIN pro obnovu hesla uživatele "%s" bylo dočasně blokováno kvůli opakovaným pokusům.',$data->username));
        $this->redirect('Sign:Recoverypassword');
      }

      $user = $this->userManager->getUserByPasswordRecoveryPin($data);

      if (!$user || count($user) !== 1)
      {
        $this->registerRateLimitFailure(
          'recovery-pin',
          $data->username,
          self::RECOVERY_PIN_SESSION_MAX_ATTEMPTS,
          self::RECOVERY_PIN_SESSION_WINDOW_SECONDS,
          self::RECOVERY_PIN_SESSION_LOCK_SECONDS
        );
        $this->flashMessage('Chyba! Uživatelské jméno nebo PIN pro obnovu hesla nejsou platná!','danger');
        $this->eventlog('sign','Neúspěšné ověření PIN pro obnovu hesla.');
        $this->redirect('Sign:Recoverypassword');
      }

      $passwords = new Passwords();
      $userLabel = $this->logUserLabel($user[0],(int) $user[0]['id']);
      $attempts = (int) ($user[0]['password_recovery_pin_attempts'] ?? 0);
      if ($attempts >= UserManager::PASSWORD_RECOVERY_PIN_MAX_ATTEMPTS)
      {
        $_data = $this->array_to_object(['id' => $user[0]['id'], 'username' => $user[0]['username']]);
        $this->userManager->clearPasswordRecoveryPin($_data);
        $this->flashMessage('PIN pro obnovu hesla již není platný. Vytvořte prosím novou žádost.','danger');
        $this->eventlog('sign',sprintf('PIN pro obnovu hesla uživatele %s byl zneplatněn kvůli překročení počtu pokusů.',$userLabel));
        $this->redirect('Sign:Recovery');
      }

      if ($this->isPasswordRecoveryPinExpired($user[0]['password_recovery_pin_created_at'] ?? null))
      {
        $_data = $this->array_to_object(['id' => $user[0]['id'], 'username' => $user[0]['username']]);
        $this->userManager->clearPasswordRecoveryPin($_data);
        $this->flashMessage('PIN pro obnovu hesla již není platný. Vytvořte prosím novou žádost.','danger');
        $this->eventlog('sign',sprintf('PIN pro obnovu hesla uživatele %s vypršel.',$userLabel));
        $this->redirect('Sign:Recovery');
      }

      if (!$passwords->verify((string) $data->password_recovery_pin,(string) $user[0]['password_recovery_pin']))
      {
        $_data = $this->array_to_object(['id' => $user[0]['id'], 'username' => $user[0]['username']]);
        $this->userManager->increasePasswordRecoveryPinAttempts($_data);
        if ($attempts + 1 >= UserManager::PASSWORD_RECOVERY_PIN_MAX_ATTEMPTS)
          $this->userManager->clearPasswordRecoveryPin($_data);
        $this->registerRateLimitFailure(
          'recovery-pin',
          $data->username,
          self::RECOVERY_PIN_SESSION_MAX_ATTEMPTS,
          self::RECOVERY_PIN_SESSION_WINDOW_SECONDS,
          self::RECOVERY_PIN_SESSION_LOCK_SECONDS
        );
        $this->flashMessage('Chyba! Uživatelské jméno nebo PIN pro obnovu hesla nejsou platná!','danger');
        $this->eventlog('sign',sprintf('Neúspěšné ověření PIN pro obnovu hesla uživatele %s.',$userLabel));
        $this->redirect('Sign:Recoverypassword');
      }

      $data->id = $user[0]['id'];

      // nastavení hesla
      $data->password_hash = $passwords->hash($data->password);

      // změna hesla
      $rst = $this->userManager->updatePassword($data);
      if (!$rst)
      {
        $this->flashMessage('Chyba! Heslo pro '.$data->username.' nebylo změněno!','danger');
	        $this->eventlog('sign',sprintf('Chyba! Heslo pro %s nebylo změněno!',$this->logUserLabel($user[0],(int) $user[0]['id'])));
        $this->redirect('Sign:Recoverypassword');
      }

      $this->clearRateLimit('recovery-pin',$data->username);
      $this->flashMessage('Heslo pro '.$data->username.' bylo změněno.');
	      $this->eventlog('sign',sprintf('Heslo pro %s bylo změněno.',$this->logUserLabel($user[0],(int) $user[0]['id'])));
      $this->redirect('Sign:In');
    }


    /**
     * Vrací zbyvající dobu blokace pro danou akci.
     */
    private function getRateLimitRemaining(string $scope,string $identifier): int
    {
      $key = $this->getRateLimitKey($scope,$identifier);

      try
      {
        $record = $this->userManager->getSecurityRateLimit($key);
        if (!$record)
          return 0;

        $blockedUntil = $this->rateLimitTimestamp($record['blocked_until'] ?? null);
        if ($blockedUntil <= time())
          return 0;

        return $blockedUntil - time();
      }
      catch (\Throwable $e)
      {
        return $this->getSessionRateLimitRemaining($scope,$identifier);
      }
    }


    /**
     * Zaznamená neúspěšný pokus a vrátí případnou délku blokace.
     */
    private function registerRateLimitFailure(string $scope,string $identifier,int $maxAttempts,int $windowSeconds,int $lockSeconds): int
    {
      $key = $this->getRateLimitKey($scope,$identifier);
      $now = time();

      try
      {
        $record = $this->userManager->getSecurityRateLimit($key);
        $firstAt = $record ? $this->rateLimitTimestamp($record['first_at'] ?? null) : $now;
        $blockedUntil = $record ? $this->rateLimitTimestamp($record['blocked_until'] ?? null) : 0;
        $attempts = $record ? (int) ($record['attempts'] ?? 0) : 0;

        if (!$record || $now - $firstAt > $windowSeconds)
        {
          $firstAt = $now;
          $blockedUntil = 0;
          $attempts = 0;
        }

        if ($blockedUntil > $now)
          return $blockedUntil - $now;

        $attempts++;
        if ($attempts >= $maxAttempts)
        {
          $blockedUntil = $now + $lockSeconds;
          $attempts = 0;
          $firstAt = $now;
        }

        $this->userManager->saveSecurityRateLimit($this->array_to_object([
          'rate_key' => $key,
          'scope' => $scope,
          'identifier' => mb_substr(mb_strtolower(trim($identifier)),0,255),
          'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
          'attempts' => $attempts,
          'first_at' => date('Y-m-d H:i:s',$firstAt),
          'blocked_until' => $blockedUntil > 0 ? date('Y-m-d H:i:s',$blockedUntil) : null,
        ]));

        return $blockedUntil > $now ? $blockedUntil - $now : 0;
      }
      catch (\Throwable $e)
      {
        return $this->registerSessionRateLimitFailure($scope,$identifier,$maxAttempts,$windowSeconds,$lockSeconds);
      }
    }


    /**
     * Vymaže počitadlo neúspěšných pokusů.
     */
    private function clearRateLimit(string $scope,string $identifier): void
    {
      $key = $this->getRateLimitKey($scope,$identifier);

      try
      {
        $this->userManager->clearSecurityRateLimit($key);
      }
      catch (\Throwable $e)
      {
        $this->clearSessionRateLimit($scope,$identifier);
      }
    }


    /**
     * Vrací zbyvající dobu blokace ze session fallbacku.
     */
    private function getSessionRateLimitRemaining(string $scope,string $identifier): int
    {
      $section = $this->getSession('securityRateLimit');
      $key = $this->getRateLimitKey($scope,$identifier);
      $record = $section[$key] ?? null;
      if (!is_array($record))
        return 0;

      $blockedUntil = (int) ($record['blocked_until'] ?? 0);
      if ($blockedUntil <= time())
        return 0;

      return $blockedUntil - time();
    }


    /**
     * Zaznamená neúspěšný pokus do session fallbacku.
     */
    private function registerSessionRateLimitFailure(string $scope,string $identifier,int $maxAttempts,int $windowSeconds,int $lockSeconds): int
    {
      $section = $this->getSession('securityRateLimit');
      $key = $this->getRateLimitKey($scope,$identifier);
      $now = time();
      $record = $section[$key] ?? array('attempts' => 0, 'first_at' => $now, 'blocked_until' => 0);

      if (!is_array($record) || $now - (int) ($record['first_at'] ?? $now) > $windowSeconds)
        $record = array('attempts' => 0, 'first_at' => $now, 'blocked_until' => 0);

      if ((int) ($record['blocked_until'] ?? 0) > $now)
        return (int) $record['blocked_until'] - $now;

      $record['attempts'] = (int) ($record['attempts'] ?? 0) + 1;
      if ($record['attempts'] >= $maxAttempts)
      {
        $record['blocked_until'] = $now + $lockSeconds;
        $record['attempts'] = 0;
        $record['first_at'] = $now;
      }

      $section[$key] = $record;

      return (int) ($record['blocked_until'] ?? 0) > $now ? (int) $record['blocked_until'] - $now : 0;
    }


    /**
     * Vymaže počitadlo neúspěšných pokusů ze session fallbacku.
     */
    private function clearSessionRateLimit(string $scope,string $identifier): void
    {
      $section = $this->getSession('securityRateLimit');
      unset($section[$this->getRateLimitKey($scope,$identifier)]);
    }


    /**
     * Klíč pro rate limit podle akce, uživatele a IP.
     */
    private function getRateLimitKey(string $scope,string $identifier): string
    {
      $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

      return sha1($scope.'|'.mb_strtolower(trim($identifier)).'|'.$remoteIp);
    }


    /**
     * Převede DB hodnotu času rate limitu na timestamp.
     */
    private function rateLimitTimestamp($value): int
    {
      if (!$value)
        return 0;

      if ($value instanceof \DateTimeInterface)
        return $value->getTimestamp();

      $timestamp = strtotime((string) $value);

      return $timestamp ?: 0;
    }


    /**
     * Ověří expiraci PIN pro obnovu hesla.
     */
    private function isPasswordRecoveryPinExpired($createdAt): bool
    {
      if (!$createdAt)
        return true;

      if ($createdAt instanceof \DateTimeInterface)
        $createdAtTs = $createdAt->getTimestamp();
      else
        $createdAtTs = strtotime((string) $createdAt);

      if (!$createdAtTs)
        return true;

      return $createdAtTs < time() - (UserManager::PASSWORD_RECOVERY_PIN_EXPIRATION_MINUTES * 60);
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
