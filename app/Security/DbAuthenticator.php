<?php

// app/Security/DbAuthenticator.php

  declare(strict_types=1);

  namespace App\Security;

  use Nette\Database\Explorer;
  use Nette\Security\AuthenticationException;
  use Nette\Security\Authenticator;
  use Nette\Security\Passwords;
  use Nette\Security\SimpleIdentity;
  use App\Model\UserManager;
  use Nette\Utils\Arrays;

  final class DbAuthenticator implements Authenticator
  {


    public function __construct(private Passwords $passwords,private UserManager $userManager)
    {
      $this->userManager = $userManager;
    }


    /**
     * @param array{username:string,password:string} $credentials
     */
    public function authenticate(string $user,string $password): SimpleIdentity
    {
      $data = new \stdClass;
      $params = ["username" => $user,"password" => $password];
      Arrays::toObject($params,$data);

      $rst = $this->userManager->getUser($data);

      if (!$rst)
        throw new AuthenticationException('Uživatel neexistuje!');

      if (count($rst) > 1)
        throw new AuthenticationException('Existuje více uživatelů se stejným username.');

      if (!$this->passwords->verify($password,$rst[0]['password_hash']))
        throw new AuthenticationException('Heslo není platné!');

      // (volitelné) rehash podle aktuálního costu
      /*
        if ($this->passwords->needsRehash($rst['password_hash'])) {
        $this->db->table('users')->where('id', $row->id)
        ->update(['password_hash' => $this->passwords->hash($password)]);
        }
       *
       */

      // data pro identitu – role můžete využít v autorizaci
      return new SimpleIdentity(
        $rst[0]['id'],
        $rst[0]['role'],
        [
          'id' => $rst[0]['id'],
          'username' => $rst[0]['username'],
          'firstname' => $rst[0]['firstname'],
          'surname' => $rst[0]['surname'],
          'role' => $rst[0]['role'],
          'email' => $rst[0]['email'],
          'mobil_number' => $rst[0]['mobil_number'],
          'benefit_card' => $rst[0]['benefit_card'],
        ],
      );
    }
  }
