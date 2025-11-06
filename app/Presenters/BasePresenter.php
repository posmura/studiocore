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
     * Vrací orderData pro jiná data
     *
     * Například předchozí datum, následující datum
     *
     * @param string $orderDate Datum ve tvaru YYYYMMDD
     * @return array
     */
    public function otherDate(string $orderDate = null): array
    {
      if (!$orderDate)
        $orderDate = $this->orderDateToday;

      $_year = (int) substr($orderDate,0,4);
      $_month = (int) substr($orderDate,4,2);
      $_day = (int) substr($orderDate,6,2);

      $_ts = mktime(0,0,0,$_month,$_day,$_year);
      $_ts_next = $_ts + 86400;
      $_ts_prev = $_ts - 86400;

      $ret['next'] = date('Ymd',$_ts_next);
      $ret['prev'] = date('Ymd',$_ts_prev);

      return $ret;
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
  }
