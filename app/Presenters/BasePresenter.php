<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette;
  use Nette\Application\Attributes\Parameter;
  use Nette\Security\User;
  use App\Model\PostManager;
  use App\Model\UserManager;
  use App\Model\FactoryManager;
  use App\Model\ActivityManager;
  use App\Model\LogManager;
  use App\Model\MembershipCardManager;
  use App\Model\SalesManager;
  use Nette\Utils\Arrays;

  /**
   * Bázový presenter úvodní stránky
   */
  class BasePresenter extends Nette\Application\UI\Presenter
  {

    /**
     * Název databáze
     */
    const DB_NAME = 'd373504_rezerva';


    /**
     * Počet opakování lekce v rozvrhu při přidání, mazání atd
     * - počet sekund mezi stejným dnem, např středa, v aktuaálním a následujícím týdnu - 604800 sekund
     */
    const DIARY_LESSON_REPEAT_NUMBER = 52;

    /**
     * Role v aplikaci
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_LECTOR = 'lektor';
    const ROLE_CLIENT = 'klient';
    const ROLES_STAFF = [self::ROLE_ADMIN,self::ROLE_LECTOR];

    #[\Nette\DI\Attributes\Inject]
    public Nette\Database\Connection $dbConnection;

    /**
     * Objekt příspěvky
     * @var object
     */
    public $postManager;

    /**
     * Objekt uživatele
     * @var object
     */
    public $userManager;

    /**
     * Objekt permanentky
     * @var object
     */
    public $membershipCardManager;

    /**
     * Objekt prodeje
     * @var object
     */
    public $salesManager;

    /**
     * Objekt factory
     * @var object
     */
    public $factoryManager;

    /**
     * Objekt aktivity
     * @var object
     */
    public $activityManager;

    /**
     * Objekt logu
     * @var object
     */
    public $logManager;

    /**
     * Jméno uživatele
     * @var string
     */
    public $userName;

    /**
     * ID uživatele
     * @var int
     */
    public $userID;

    /**
     * Role uživatele
     * @var string
     */
    public $role;

    /**
     * Pole jmen uživatelů
     * @var array
     */
    public $userNames;

    /**
     * Timestap dnes
     * @var int
     */
    public $tsToday;

    /**
     * Datum objednávky dnes
     * @var string
     */
    public $orderDateToday;

    /**
     * Datum objednávky zítra
     * @var string
     */
    public $orderDateTomorrow;

    /**
     * Objednací hodiny
     * @var array
     */
    public $orderHours;

    /**
     * Text SMS zprávy
     * @var string
     */
    public $smsOrderText = "";

    /**
     * Číselník aktivit
     * @var array
     */
    public $aktivita;

    /**
     * Číselník lektorů
     * @var array
     */
    public $lektor;

    /**
     * Číselník křestních jmen lektorů, např. pro rozvrh
     * @var array
     */
    public $lektorName;

    /**
     * Počet kreditů klienty
     * @var array
     */
    public $kredity;

    /**
     * Pole aktivních permanentek pro jednotlivé aktivity
     * @var array
     */
    public $aktivniPermanentkyID;


    /**
     * Konstuktor
     *
     * @param UserManager $userManager
     * @param FactoryManager $factoryManager
     * @param MembershipCardManager $membershipCardManager
     * @param SalesManager $salesManager
     * @param ActivityManager $activityManager
     * @param LogManager $logManager
     */
    public function __construct(
      UserManager $userManager,
      FactoryManager $factoryManager,
      MembershipCardManager $membershipCardManager,
      salesManager $salesManager,
      ActivityManager $activityManager,
      LogManager $logManager
    )
    {
      parent::__construct();

      $this->userManager = $userManager;
      $this->factoryManager = $factoryManager;
      $this->membershipCardManager = $membershipCardManager;
      $this->salesManager = $salesManager;
      $this->activityManager = $activityManager;
      $this->logManager = $logManager;
    }


    /**
     * Inicializace parametrů
     *
     * @return void
     */
    public function startup(): void
    {
      parent::startup();

      if ($this->user->isLoggedIn())
      {
        // nastavím přihlašovací údaje klienta
        $this->userName = $this->getUser()->identity->username;
        $this->userID = $this->getUser()->identity->id;
        $this->role = $this->getUser()->identity->role;
        $this->userNames = $this->userManager->getAllUsernames();

        // načtu kredity klienta
        $this->kredity = $this->userManager->getKredityKlienta($this->userID,$this->userName);

        // načtu pole aktivních permanentek pro jednotlivé aktivity
        $this->aktivniPermanentkyID = $this->userManager->getAktivniPermanentkyID($this->userID);
      }

      // nastavím dnešní datum
      $this->tsToday = time();
      $this->orderDateToday = date('Ymd',$this->tsToday);

      // nastavím zítřejší datum
      $tsTomorrow = $this->tsToday + 86400;
      $this->orderDateTomorrow = date('Ymd',$tsTomorrow);

      // připravím hodiny pro objednávky
      $this->orderHours = $this->getOrderHours();

      $this->aktivita = $this->activityManager->getListAktivita();
      $this->lektor = $this->userManager->getListLektor();
      $this->lektorName = $this->userManager->getLektorName();

   }


   /**
    * Render nejvyšší šablony
    *
    * @return void
    */
   protected function beforeRender(): void
   {
     parent::beforeRender();
     $this->template->kredity = $this->kredity;

     $url = $this->getHttpRequest()->getUrl();
     $this->template->isDevEnvironment = $url->getHost() === 'localhost' && $url->getPort() === 8203;
     $this->template->isDevDatabase = str_contains($this->dbConnection->getDsn(), '127.0.0.1');
   }

    /**
     * Zápis do eventlogu aplikace
     *
     * @param string $presenter Presenter
     * @param string $action Akce presenteru
     * @return void
     */
    public function eventlog($presenter = null,$action = null): void
    {
      if ((!$action) or (!$presenter))
        return;

      $data['presenter'] = $presenter;
      $data['action'] = $action;
      $data['remote_ip'] = $_SERVER['REMOTE_ADDR'];
      $data['username'] = $this->getUser()->isLoggedIn() ? $this->getUser()->identity->username : null;

      $this->logManager->insertEvenlog($data);
    }


    /**
     * Vrací hodnotu pole/objektu pro skládání čitelných logů.
     */
    protected function logValue($data,string $key,$default = null)
    {
      if (is_array($data) && array_key_exists($key,$data))
        return $data[$key];

      if ($data instanceof \ArrayAccess && isset($data[$key]))
        return $data[$key];

      if (is_object($data) && method_exists($data,'getData'))
      {
        $identityData = $data->getData();
        if (is_array($identityData) && array_key_exists($key,$identityData))
          return $identityData[$key];
      }

      if ($key === 'id' && is_object($data) && method_exists($data,'getId'))
        return $data->getId();

      if (is_object($data) && isset($data->{$key}))
        return $data->{$key};

      return $default;
    }


    /**
     * Popisek uživatele pro eventlog.
     */
    protected function logUserLabel($user,int $fallbackId = 0): string
    {
      if (!$user)
        return $fallbackId > 0 ? sprintf('ID=%d',$fallbackId) : 'neznámý uživatel';

      $id = (int) $this->logValue($user,'id',$this->logValue($user,'user_id',$fallbackId));
      $surname = trim((string) $this->logValue($user,'surname',$this->logValue($user,'user_surname','')));
      $firstname = trim((string) $this->logValue($user,'firstname',$this->logValue($user,'user_firstname','')));
      $username = trim((string) $this->logValue($user,'username',$this->logValue($user,'user_username','')));
      $name = trim(sprintf('%s %s',$surname,$firstname));

      if ($name === '')
        $name = $username !== '' ? $username : 'neznámý uživatel';

      $suffix = array();
      if ($username !== '' && $username !== $name)
        $suffix[] = $username;
      if ($id > 0)
        $suffix[] = sprintf('ID=%d',$id);

      return $suffix ? sprintf('%s (%s)',$name,implode(', ',$suffix)) : $name;
    }


    /**
     * Popisek uživatele podle ID pro eventlog.
     */
    protected function logUserLabelById(int $userId): string
    {
      if ($userId <= 0)
        return 'neznámý uživatel';

      try
      {
        $user = $this->userManager->getUserByID(self::array_to_object(['id' => $userId]));
      }
      catch (\Throwable $e)
      {
        $user = null;
      }

      return $this->logUserLabel($user,$userId);
    }


    /**
     * Popisek aktivity pro eventlog.
     */
    protected function logActivityLabel($activity,int $fallbackId = 0): string
    {
      $id = (int) $this->logValue($activity,'id',$fallbackId);
      $name = trim((string) $this->logValue($activity,'nazev',''));
      if ($name === '' && $id > 0)
        $name = $this->aktivita[$id] ?? '';
      if ($name === '')
        $name = 'neznámá aktivita';

      return $id > 0 ? sprintf('%s (ID=%d)',$name,$id) : $name;
    }


    /**
     * Popisek aktivity podle ID pro eventlog.
     */
    protected function logActivityLabelById(int $activityId): string
    {
      if ($activityId <= 0)
        return 'neznámá aktivita';

      try
      {
        $activity = $this->activityManager->getAktivita(self::array_to_object(['id' => $activityId]));
      }
      catch (\Throwable $e)
      {
        $activity = null;
      }

      return $this->logActivityLabel($activity,$activityId);
    }


    /**
     * Popisek permanentky pro eventlog.
     */
    protected function logMembershipCardLabel($card,int $fallbackId = 0): string
    {
      $id = (int) $this->logValue($card,'id',$fallbackId);
      $activityId = (int) $this->logValue($card,'aktivita_id',0);
      $activityName = trim((string) $this->logValue($card,'nazev_aktivity',''));
      if ($activityName === '' && $activityId > 0)
        $activityName = $this->aktivita[$activityId] ?? '';
      $name = trim((string) $this->logValue($card,'nazev',''));
      $price = $this->logValue($card,'cena',null);
      $entries = $this->logValue($card,'vstupy',null);
      $label = trim(sprintf('%s%s%s',
        $activityName,
        $activityName !== '' && $name !== '' ? ' - ' : '',
        $name
      ));
      if ($label === '')
        $label = 'neznámá permanentka';

      $details = array();
      if ($id > 0)
        $details[] = sprintf('ID=%d',$id);
      if ($price !== null && $price !== '')
        $details[] = sprintf('cena=%s Kč',$price);
      if ($entries !== null && $entries !== '')
        $details[] = sprintf('vstupy=%s',$entries);

      return $details ? sprintf('%s (%s)',$label,implode(', ',$details)) : $label;
    }


    /**
     * Popisek permanentky podle ID pro eventlog.
     */
    protected function logMembershipCardLabelById(int $cardId): string
    {
      if ($cardId <= 0)
        return 'neznámá permanentka';

      try
      {
        $card = $this->membershipCardManager->getPermanentka(self::array_to_object(['id' => $cardId]));
      }
      catch (\Throwable $e)
      {
        $card = null;
      }

      return $this->logMembershipCardLabel($card,$cardId);
    }


    /**
     * Popisek prodeje pro eventlog.
     */
    protected function logSaleLabel($sale,int $fallbackId = 0): string
    {
      $id = (int) $this->logValue($sale,'ID',$this->logValue($sale,'id',$fallbackId));
      $client = trim((string) $this->logValue($sale,'username_full',''));
      $card = trim((string) $this->logValue($sale,'aktivita_name',''));
      $entries = $this->logValue($sale,'vstupy_aktualni',null);

      $label = $id > 0 ? sprintf('prodej ID=%d',$id) : 'neznámý prodej';
      $details = array();
      if ($client !== '')
        $details[] = sprintf('klient: %s',$client);
      if ($card !== '')
        $details[] = sprintf('permanentka: %s',$card);
      if ($entries !== null && $entries !== '')
        $details[] = sprintf('zbývá vstupů: %s',$entries);

      return $details ? sprintf('%s (%s)',$label,implode(', ',$details)) : $label;
    }


    /**
     * Popisek prodeje podle ID pro eventlog.
     */
    protected function logSaleLabelById(int $saleId): string
    {
      if ($saleId <= 0)
        return 'neznámý prodej';

      try
      {
        $sale = $this->salesManager->getProdej(self::array_to_object(['id' => $saleId]));
      }
      catch (\Throwable $e)
      {
        $sale = null;
      }

      return $this->logSaleLabel($sale,$saleId);
    }


    /**
     * Popisek lekce pro eventlog.
     */
    protected function logLessonLabel($lesson,int $fallbackId = 0): string
    {
      $id = (int) $this->logValue($lesson,'ID',$this->logValue($lesson,'diary_id',$fallbackId));
      $lessonId = $this->logValue($lesson,'lekce_id',null);
      $name = trim((string) $this->logValue($lesson,'nazev',$this->logValue($lesson,'diary_nazev','')));
      if ($name === '')
        $name = 'neznámá lekce';

      $date = (string) $this->logValue($lesson,'date',$this->logValue($lesson,'diary_date',''));
      $dateText = strlen($date) === 8 ? $this->dateOrderToDateForm($date) : $date;
      $hour = $this->logValue($lesson,'hour_from',$this->logValue($lesson,'diary_hour_from',null));
      $minute = $this->logValue($lesson,'min_from',$this->logValue($lesson,'diary_min_from',null));
      $timeText = $hour !== null && $minute !== null ? $this->timeOrderToTimeForm($hour,$minute,':') : '';
      $activity = trim((string) $this->logValue($lesson,'aktivita_nazev',''));
      $lector = trim(sprintf('%s %s',
        (string) $this->logValue($lesson,'lektor_surname',''),
        (string) $this->logValue($lesson,'lektor_firstname','')
      ));
      if ($lector === '' || $lector === ' ')
      {
        $lectorId = (int) $this->logValue($lesson,'lektor_id',0);
        $lector = $lectorId > 0 ? ($this->lektorName[$lectorId] ?? '') : '';
      }

      $details = array();
      if ($id > 0)
        $details[] = sprintf('ID=%d',$id);
      if ($lessonId)
        $details[] = sprintf('lekce_id=%s',$lessonId);
      if ($dateText !== '')
        $details[] = trim(sprintf('%s %s',$dateText,$timeText));
      if ($activity !== '')
        $details[] = sprintf('aktivita: %s',$activity);
      if ($lector !== '')
        $details[] = sprintf('lektor: %s',$lector);

      return $details ? sprintf('%s (%s)',$name,implode(', ',$details)) : $name;
    }


    /**
     * Popisek lekce podle ID pro eventlog.
     */
    protected function logLessonLabelById(int $diaryId): string
    {
      if ($diaryId <= 0)
        return 'neznámá lekce';

      try
      {
        $lesson = $this->factoryManager->getLekceById($diaryId);
      }
      catch (\Throwable $e)
      {
        $lesson = null;
      }

      return $this->logLessonLabel($lesson,$diaryId);
    }


    /**
     * Popisek příspěvku pro eventlog.
     */
    protected function logPostLabel($post,int $fallbackId = 0): string
    {
      $id = (int) $this->logValue($post,'id',$this->logValue($post,'ID',$fallbackId));
      $title = trim((string) $this->logValue($post,'title',''));
      if ($title === '')
        $title = 'neznámý příspěvek';

      return $id > 0 ? sprintf('"%s" (ID=%d)',$title,$id) : sprintf('"%s"',$title);
    }


    /**
     * Vrací datum ve formátu YYYYMMDD
     *
     * @param string $dateForm Datum ve formátu DD.MM.YYYY
     * @return string
     */
    public function dateFormToDateOrder($dateForm): string
    {
      $res = explode('.',$dateForm);

      return $res[2].$res[1].$res[0];
    }


    /**
     * Vrací datum ve formátu DD.MM.YYYY
     *
     * @param type $dateOrder Datum ve formátu YYYYMMDD
     * @return string
     */
    public function dateOrderToDateForm($dateOrder): string
    {
      $res = (string) $dateOrder;

      return substr($res,6,2).'.'.substr($res,4,2).'.'.substr($res,0,4);
    }


    /**
     * Vrací pole časů
     *
     * @return array
     */
    public function getOrderHours(): array
    {
      $hours = array('06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22');
      $minutes = array('00','15','30','45');
      $res = array('-' => '');

      foreach ($hours as $hour)
      {
        foreach ($minutes as $minute)
        {
          $key = $hour.$minute;
          $value = $hour.':'.$minute;
          $res[$key] = $value;
        }
      }

      return $res;
    }


    /**
     * Vrací čas ve formátu HHMM
     *
     * @param int $hour Hodina
     * @param int $min Minuta
     * @param string $delimiter Oddělovač
     * @return string
     */
    public function timeOrderToTimeForm($hour,$min,$delimiter = ''): string
    {
      $hour = (string) $hour;
      $hour = (strlen($hour) == 1 ? '0' : '').$hour;
      $min = (string) $min;
      $min = (strlen($min) == 1 ? '0' : '').$min;

      return $hour.$delimiter.$min;
    }


    /**
     * Převede datum ve formátu YYYYMMDD do timestamp
     *
     * @param string $orderDate Datum ve tvaru YYYYMMDD
     * @return int
     */
    public function dateOrderToTsDiaryDate($orderDate = null): int
    {
      if (!$orderDate)
        $orderDate = $this->orderDateToday;

      $_year = (int) substr($orderDate,0,4);
      $_month = (int) substr($orderDate,4,2);
      $_day = (int) substr($orderDate,6,2);

      $_ts = mktime(0,0,0,$_month,$_day,$_year);

      return $_ts;
    }


    /**
     * Provede test na telefonní číslo
     *
     * @param type $phone
     * @return string|null
     */
    public function checkSmsPhone($phone): ?string
    {
      $sms_phone = preg_replace('/\s+/','',$phone);

      if ((!$sms_phone) || (!is_numeric($sms_phone)))
        return null;

      return $sms_phone;
    }


    /**
     * Bezpečně odešle SMS a zachytí výjimky z SMS brány.
     *
     * @param \BulkGate\Sdk\Sender $sender SMS sender
     * @param string $smsPhone Telefonní číslo
     * @param string $smsText Text SMS
     * @param \Throwable|null $exception Zachycená výjimka
     * @return bool
     */
    protected function sendSmsSafely(\BulkGate\Sdk\Sender $sender,string $smsPhone,string $smsText,?\Throwable &$exception = null): bool
    {
      $exception = null;

      if (!$smsPhone || !$smsText)
        return false;

      try
      {
        return (bool) $sender->send(new \BulkGate\Sdk\Message\Sms($smsPhone,$smsText));
      }
      catch (\Throwable $e)
      {
        $exception = $e;
        return false;
      }
    }


    /**
     * Převede pole na objekt
     *
     * @param array $params Pole s parametry
     * @return object
     */
	    public static function array_to_object(array $params = array()): object
	    {
	      $data = new \stdClass;
	      Arrays::toObject($params,$data);

	      return $data;
	    }


	    /**
	     * Vyžaduje přihlášeného uživatele.
	     */
	    protected function requireLogin(): void
	    {
	      if ($this->getUser()->isLoggedIn())
	        return;

	      $this->flashMessage('Z důvodu nečinnosti jste byl(a) automaticky odhlášen(a) z aplikace.','danger');
	      $this->redirect('Homepage:');
	    }


	    /**
	     * Vyžaduje jednu z povolených rolí.
	     */
	    protected function requireRoles(array $roles): void
	    {
	      $this->requireLogin();

	      if (in_array($this->role,$roles,true))
	        return;

	      $this->denyAccess();
	    }


	    /**
	     * Vyžaduje roli administrátora.
	     */
	    protected function requireAdmin(): void
	    {
	      $this->requireRoles([self::ROLE_ADMIN]);
	    }


	    /**
	     * Vyžaduje administrátora nebo lektora.
	     */
	    protected function requireStaff(): void
	    {
	      $this->requireRoles(self::ROLES_STAFF);
	    }


	    /**
	     * Vrací příznak, zda je přihlášen administrátor.
	     */
	    protected function isAdmin(): bool
	    {
	      return $this->role === self::ROLE_ADMIN;
	    }


	    /**
	     * Vrací příznak, zda je přihlášen administrátor nebo lektor.
	     */
	    protected function isStaff(): bool
	    {
	      return in_array($this->role,self::ROLES_STAFF,true);
	    }


	    /**
	     * Ukončí požadavek s hláškou o nepovoleném přístupu.
	     */
	    protected function denyAccess(): void
	    {
	      $username = $this->getUser()->isLoggedIn() ? $this->logUserLabel($this->getUser()->identity) : 'nepřihlášený uživatel';
	      $this->eventlog($this->getName(),'Chyba! Pokus o neoprávněný přístup uživatelem '.$username.'.');

	      $this->flashMessage('Nemáte oprávnění pro tuto akci.','danger');
	      $this->redirect('Homepage:');
	    }


	    /**
	     * Formulář pro odhlášení uživatele.
	     */
	    protected function createComponentSignoutForm(): Nette\Application\UI\Form
	    {
	      $this->requireLogin();

	      $form = new Nette\Application\UI\Form;
	      $form->setHtmlAttribute('style','display:inline;');
	      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');
	      $form->addSubmit('send','Odhlášení')
	        ->setHtmlAttribute('class','btn btn-outline-light mx-2 px-2');
	      $form->onSuccess[] = [$this,'signoutFormSucceeded'];

	      return $form;
	    }


	    /**
	     * Odhlášení uživatele po odeslání formuláře.
	     */
	    public function signoutFormSucceeded(Nette\Application\UI\Form $form,$data): void
	    {
	      $this->requireLogin();
	      $this->eventlog('sign',sprintf('Uživatel %s byl odhlášen.',$this->logUserLabel($this->getUser()->identity)));
	      $this->getUser()->logout();
	      $this->redirect('Homepage:');
	    }
	  }
