<?php

  declare(strict_types=1);

  namespace App\Model;

  use Nette\Security\User;
  use Nette\Database\Connection;
  use App\Model\DatabaseManager;
  use App\Model\ActivityManager;
  use App\Model\FactoryManager;
  use Nette\Forms\Controls\TextInput;
  use Nette\Security\Passwords;

  final class UserManager extends DatabaseManager
  {

    /**
     * Minimální délka uživatelského jména
     */
    const USERNAME_MIN_LENGTH = 5;

    /**
     * Maximální délka uživatelského jména
     */
    const USERNAME_MAX_LENGTH = 20;

    /**
     * Minimální délka hesla
     */
    const PASSWORD_MIN_LENGTH = 8;

    /**
     * Maximální délka hesla
     */
    const PASSWORD_MAX_LENGTH = 50;

    /**
     * Délka PIN pro obnovení hesla
     */
    const PASSWORD_RECOVERY_PIN_LENGTH = 8;

    /**
     * Objekt ActivityManager
     * @var object
     */
    public $activityManager;

    /**
     * Objekt FactoryManager
     * @var object
     */
    public $factoryManager;


    /**
     * Konstruktor třídy
     *
     * @param Connection $database objekt Nette\Database\Connection
     * @param ActivityManager $activityManager object App\Model\ActivityManager
     */
    public function __construct(
      Connection $database,
      ActivityManager $activityManager,
      FactoryManager $factoryManager
    )
    {
      parent::__construct($database);

      $this->activityManager = $activityManager;
      $this->factoryManager = $factoryManager;
    }


    /**
     * Vrací všechny uživatele
     *
     * @return array
     */
    public function getAllUsers()
    {
      return $this->database->fetchAll(SqlCommands::getAllUsers());
    }


    /**
     * Vrací všechny uživatele setříděné podle příjmení, jména a username
     *
     * @return array
     */
    public function getAllUsersOrderBySurname()
    {
      return $this->database->fetchAll(SqlCommands::getAllUsersOrderBySurname());
    }


    /**
     * Vrací seznam všech uživatelů
     *
     * @return array
     */
    public function getListUsers(): array
    {
      $rst = array(0 => '--- zvolte ---');

      $data = $this->getAllUsersOrderBySurname();
      foreach ($data as $key => $item)
        $rst[$item['id']] = sprintf('%s %s (%s)',$item['surname'],$item['firstname'],$item['username']);

      return $rst;
    }


    /**
     * Vrací všechny lektory
     *
     * @return array
     */
    public function getAllLektor(): array
    {
      return $this->database->fetchAll(SqlCommands::getAllLektor());
    }


    /**
     * Vrací pole křestních jmen lektorů
     *
     * @return array
     */
    public function getLektorName(): array
    {
      $rst = array();

      $data = $this->getAllLektor();
      foreach ($data as $key => $item)
        $rst[$item['id']] = $item['firstname'];

      return $rst;
    }


    /**
     * Vrací seznam lektorů
     *
     * @return array
     */
    public function getListLektor(): array
    {
      $rst = array(0 => '--- zvolte ---');

      $data = $this->getAllLektor();
      foreach ($data as $key => $item)
        $rst[$item['id']] = sprintf('%s %s',$item['surname'],$item['firstname']);

      return $rst;
    }


    /**
     * Vrací jména uživatelů
     *
     * @return array
     */
    public function getAllUsernames()
    {
      return $this->database->fetchPairs(SqlCommands::getAllUsernames());
    }


    /**
     * Vrací uživatele podle username uživatele
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function getUser($data)
    {
      return $this->database->fetchAll(SqlCommands::getUser(),$data->username);
    }


    /**
     * Vrací uživatele podle ID
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function getUserByID($data)
    {
      $rst = $this->database->fetchAll(SqlCommands::getUserByID(),$data->id);

      if (!$rst || !isset($rst[0]))
        return false;

      return $rst[0];
    }


    /**
     * Vrací uživatele podle e-mailové adresy
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function getUserByEmail($data)
    {
      return $this->database->fetchAll(SqlCommands::getUserByEmail(),$data->username,$data->email);
    }


    /**
     * Vrací uživatele podle čísla mobilního telefonu
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function getUserByMobilNumber($data)
    {
      return $this->database->fetchAll(SqlCommands::getUserByMobilNumber(),$data->username,$data->mobil_number);
    }


    /**
     * Vrací uživatele podle e-mailové adresy
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function getUserByPasswordRecoveryPin($data)
    {
      return $this->database->fetchAll(SqlCommands::getUserByPasswordRecoveryPin(),$data->username,$data->password_recovery_pin);
    }


    /**
     * Aktualizuje PIN pro obnovení hesla
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function updatePasswordRecoveryPin($data)
    {
      return $this->database->query(SqlCommands::updatePasswordRecoveryPin(),$data->password_recovery_pin,$data->username,$data->id);
    }


    /**
     * Aktualizuje heslo uživatele
     *
     * @param object $data Data uživatele
     * @return array
     */
    public function updatePassword($data)
    {
      return $this->database->query(SqlCommands::updatePassword(),$data->password_hash,$data->username,$data->id);
    }


    /**
     * Vloží uživatele
     *
     * @param object $data Data uživatele
     * @return bool
     */
    public function insertUser($data)
    {
      return $this->database->query(SqlCommands::insertUser(),
          $data->username,
          $data->surname,
          $data->firstname,
          $data->password_hash,
          $data->email,
          $data->mobil_number,
          $data->benefit_card,
          $data->role,
      );
    }


    /**
     * Upraví uživatele
     *
     * @param object $data Data uživatele
     * @return bool
     */
    public function updateUser($data)
    {
      return $this->database->query(SqlCommands::updateUser(),
          $data->surname,
          $data->firstname,
          $data->email,
          $data->mobil_number,
          $data->role,
          $data->benefit_card,
          $data->updated_by,
          $data->id
      );
    }


    /**
     * Upraví heslo uživatele
     *
     * @param object $data Data uživatele
     * @return bool
     */
    public function updateUserPassword($data)
    {
      return $this->database->query(SqlCommands::updateUserPassword(),
          $data->password_hash,
          $data->updated_by,
          $data->id
      );
    }


    /**
     * Smaže uživatele
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser($userId,$deleteBy)
    {
      return $this->database->query(SqlCommands::deleteUser(),$deleteBy,$userId);
    }


    /**
     * Kontrola duplicity uživatele
     *
     * @param type $data
     * @return type
     */
    public function checkData($data)
    {
      $data->username = mb_strtolower($data->username);
      return $data;
    }


    /**
     * Získá seznam položek ze sloupce typu 'ENUM'
     *
     * @return array
     */
    public function getListFromEnum($params): array
    {
      $data = $this->database->fetchPairs(SqlCommands::getListFromEnum(),$params['db'],$params['tbl'],$params['col']);

      if (!$data || !is_array($data) || !isset($data[0]))
        return array('???' => 'nebyla nalezena žádná položka');

      $enumStr = str_replace(["enum(",")","'"],"",$data[0]);
      $enumValues = explode(",",$enumStr);

      $rst = array();
      foreach ($enumValues as $item)
      {
        $rst[$item] = $item;
      }

      return $rst;
    }


    /**
     * Ověření hesla
     *
     * @param TextInput $input heslo z formuláře
     * @return bool
     */
    public static function validate_strong_password(TextInput $input): bool
    {
      if (!$input)
        return false;

      $v = (string) $input->getValue();

      // délku u Unicode řeším přes mb_strlen
      $len = mb_strlen($v,'UTF-8');
      if ($len < self::PASSWORD_MIN_LENGTH || $len > self::PASSWORD_MAX_LENGTH)
        return false;

      // malé písmeno (Unicode)
      if (!preg_match('/\p{Ll}/u',$v))
        return false;

      // velké písmeno (Unicode)
      if (!preg_match('/\p{Lu}/u',$v))
        return false;

      // číslice (Unicode)
      if (!preg_match('/\p{Nd}/u',$v))
        return false;

      // speciální znak (ne písmeno, ne číslice)
      if (!preg_match('/[^\p{L}\p{Nd}]/u',$v))
        return false;

      return true;
    }


    /**
     * Vygeneruje PIN
     *
     * @param int $length Délka výsledného PIN
     * @return string
     */
    public static function generatePin(int $length = null): string
    {
      if (!$length)
        $length = self::PASSWORD_RECOVERY_PIN_LENGTH;

      $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      $maxIndex = strlen($chars) - 1;
      $pin = '';
      for ($i = 0; $i < $length; $i++)
      {
        $pin .= $chars[random_int(0,$maxIndex)];
      }
      return $pin;
    }


    /**
     * Vrací počet kreditů (vstupů) klienta
     *
     * @param int $userID ID klienta
     * @param string $userName Username klienta, který se na kredity dotazuje
     * @return array
     */
    public function XXXgetKredityKlienta($userID,$userName): array
    {
      $rst = array();

      // načtu seznam aktivit a nastavím výchozí hodnoty kreditů
      $data = $this->database->fetchAll(SqlCommands::getAllAktivita());
      foreach ($data as $key => $items)
      {
        $rst[$items['id']] = array(
          'nazev' => $items['nazev'],
          'kredity' => 0,
        );
      }
      ksort($rst);

      $time_do = strtotime('today 23:59:59');

      // načtu kredity pro jednotlivé aktivity
      foreach ($rst as $key => $items)
      {
        // načtu kredity z tabulky kreditů, pokud záznamy neexistujím vytvořím výchozí prázdný
        $res_sel = $this->database->fetch(SqlCommands::getKredityKlienta(),$userID,$key);
        if (!$res_sel)
        {
          // záznam neexistuje, vytvořím nový
          $res_sel['kredity'] = 0;
          $res_ins = $this->database->query(SqlCommands::createKredityKlienta(),$userID,$key,$res_sel['kredity'],$userName);
        }
        else
        {
          $rst[$key]['kredity'] = $res_sel['kredity'];
        }

        // načtu kredity z platných permanentek
        $pkredit = 0;
        $res_perm = $this->database->fetchAll(SqlCommands::getPermanentkaProKredit(),$userID,$key,$time_do);
        foreach ($res_perm as $pkey => $pitems)
        {
          $pkredit += $pitems['vstupy_aktualni'];
        }


        if ($rst[$key]['kredity'] == 0 && $pkredit == 0)
        {
          $rst[$key]['kredity'] = $pkredit;
        }
        elseif ($rst[$key]['kredity'] < 0 && $pkredit == 0)
        {
          $rst[$key]['kredity'] = $res_sel['kredity'];
        }
        else
        {
          $rst[$key]['kredity'] = $pkredit;
        }
      }

      return $rst;
    }


    /**
     * Vrací počet kreditů z tabulky blog_credits pro user_id a aktivita_id
     *
     * @param int $user_id ID uživatele
     * @param int $aktivita_id ID aktivity
     * @return int
     */
    public function getKredity(int $user_id,int $aktivita_id): int
    {
      // načtu kredity z tabulky kreditů, pokud záznamy neexistujím vytvořím výchozí prázdný
      $data = $this->database->fetch(SqlCommands::getKredityKlienta(),$user_id,$aktivita_id);
      if (!$data)
      {
        // záznam neexistuje, vytvořím nový
        $rst = 0;

        $rst_ins = $this->database->query(SqlCommands::createKredityKlienta(),$user_id,$aktivita_id,$rst,'system');
      }
      else
      {
        $rst = $data['kredity'];
      }

      return $rst;
    }


    /**
     * Vrací počet id aktuální aktivní permanentky, počet kreditů na ní a počet kreditů všech aktivních permanentek
     *
     * Pozn.: Aktivní permanentka je ta, která má aktuální počet kreditů > 0 a konec platnosti <= aktuální den
     *
     * @param type $user_id ID uživatele
     * @param type $user_name Username přihlášeného uživatele, který se dotazuje
     * @return array
     */
    public function getKredityKlienta($user_id,$user_name): array
    {
      // načtu seznam aktivit
      $aktivity = $this->activityManager->getListAktivita();
      if (is_array($aktivity) && isset($aktivity[0]))
      {
        // odstraním hodnotu s nabídkou seznamu
        unset($aktivity[0]);
      }
      ksort($aktivity);

      // nastavím datum konce pro výběr aktivní permanentky (může jich být více aktivních)
      $datum_konce = strtotime('today 23:59:59');

      foreach ($aktivity as $aktivita_id => $aktivita_nazev)
      {
        // nastavím výchotí hodnoty pro kredit dané aktivity
        $ret[$aktivita_id] = array(
          'user_id' => $user_id,
          'aktivita_id' => $aktivita_id,
          'aktivita_nazev' => $aktivita_nazev,
          'kredity' => 0,
          'perm_id' => null,
          'perm_vstupy_aktualni' => 0,
          'perm_all_vstupy_aktualni' => 0,
          'perm_all_vstupy_celkem' => 0,
          );

        // načtu data z aktivních permanentek
        $data = $this->database->fetchAll(SqlCommands::getAktivniPermanentkyID(),$user_id,$aktivita_id,$datum_konce);
        if ($data)
        {
          foreach ($data as $key => $items)
          {
            if ($key == 0)
            {
              $ret[$aktivita_id]['perm_id'] = $items['ID'];
              $ret[$aktivita_id]['perm_vstupy_aktualni'] = $items['vstupy_aktualni'];
            }

            $ret[$aktivita_id]['perm_all_vstupy_aktualni'] += $items['vstupy_aktualni'];
            $ret[$aktivita_id]['perm_all_vstupy_celkem'] += $items['vstupy_celkem'];
          }
        }

        // načtu výslednou hodnotu kreditů z tabulky blog_credits
        $kredity = $this->getKredity($user_id,$aktivita_id);

        if ($kredity < 0)
        {
          // když jsou kredity <= 0, odečtu je od aktuálních vstupů permanentek
          $ret[$aktivita_id]['kredity'] = $kredity + $ret[$aktivita_id]['perm_all_vstupy_aktualni'];
        }
        else
        {
          // když jsou kredity > 0, nastavím aktuální vstupy permanentek
          $ret[$aktivita_id]['kredity'] = $ret[$aktivita_id]['perm_all_vstupy_aktualni'];
        }

        $refresh = $this->database->query(SqlCommands::refreshKredityKlienta(), $ret[$aktivita_id]['kredity'],$user_name,  $user_id, $aktivita_id);

      }

      return $ret;
    }


    /**
     * Vrací počet kreditů všech klientů
     *
     * @return array
     */
    public function getAllKredityKlienta(): array
    {
      $rst = $this->database->fetchAll(SqlCommands::getAllKredityKlienta());
      return $rst;
    }


    /**
     * Seznam permanentek pro načtení a úpravu kreditů
     *
     * @return array
     */
    public function getPermanentkaProKredit(): array
    {
      $rst = $this->database->fetchAll(SqlCommands::getAllKredityKlienta());
      return $rst;
    }


    /**
     * Seznam registrací koienta
     *
     * @return array
     */
    public function getRegistraceByUserID($data): array
    {
      $rst = $this->database->fetchAll(SqlCommands::getRegistraceByUserID(),$data->id);
      return $rst;
    }



    /**
     * Vrací pole ID aktivních permanentek
     *
     * @param int $user_id ID uživatele
     * @param int $aktivita_id ID aktivity
     * @return array
     */
    public function getAktivniPermanentkyID(int $user_id): array
    {
      // načtu seznam aktivit
      $aktivity = $this->activityManager->getListAktivita();
      if (is_array($aktivity) && isset($aktivity[0]))
      {
        // odstraním hodnotu s nabídkou seznamu
        unset($aktivity[0]);
      }
      ksort($aktivity);

      $datum_konce = strtotime('today 23:59:59');

      $rst = array();

      foreach ($aktivity as $aktivita_id => $aktivita_nazev)
      {
        $data = $this->database->fetchAll(SqlCommands::getAktivniPermanentkyID(),$user_id, $aktivita_id, $datum_konce);

        if ($data && isset($data[0]['ID']))
          $rst[$aktivita_id] = $data[0]['ID'];
        else
          $rst[$aktivita_id] = null;
      }
      return $rst;
    }
  }
