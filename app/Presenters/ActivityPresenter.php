<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Application\UI\Form;

  /**
   * Třída presenteru pro aktivity
   */
  final class ActivityPresenter extends BasePresenter
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
      $this->template->data = $this->activityManager->getAllAktivita();
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
        $this->activityManager->deleteAktivita($data);
      }
      catch (\Exception $e)
      {
        $_msg = sprintf('Chyba! Aktivita ID=%s nebyla smazána.',$data->id);
        $this->eventlog('activity',$_msg);
        $this->flashMessage($_msg,'danger');
        $this->redirect('Activity:default');
      }

      $_msg = sprintf('Aktivita ID=%s byla smazána.',$data->id);
      $this->flashMessage($_msg);
      $this->eventlog('activity',$_msg);
      $this->redirect('Activity:default');
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

      $data = $this->activityManager->getAktivita($params);

      $this->template->data = $data;

      $this->eventlog('activity','Editace aktivity ID='.$id.'.');
    }


    /**
     * Formuláře pro aktivitu
     *
     * @return Form
     */
    protected function createComponentAktivitaForm(): Form
    {
      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

      $form->addHidden('id')
        ->setDefaultValue(0)
        ->setHtmlAttribute('ID','frm-aktivitaForm-id');

      $form->addText('nazev','Název:')
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadováno!');

      $form->addText('vstupy_min','Počet vstupů - min:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('vstupy_max','Počet vstupů - max:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('zruseni_zdarma','Doba zrušení klientem zdarma [hod]:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('zruseni_neucast','Doba zrušení pro neúčast [hod]:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addText('registrace_konec','Konec registace před lekcí [hod]:')
        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
        ->addRule($form::MIN,'%label musí být větší než %d!',0)
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder','')
        ->setRequired('%label je vyžadována!');

      $form->addSubmit('send','Odeslat')
        ->setHtmlAttribute('class','btn btn-success');

      $form->onSuccess[] = [$this,'formAktivitaSucceeded'];

      return $form;
    }


    /**
     * Akce po odeslání formuláře pro editaci
     *
     * @param Form $form Objekt formuláře
     * @param type $data Data z formuláře
     * @return void
     */
    public function formAktivitaSucceeded(Form $form,$data): void
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
        $_msg = sprintf('Chyba! Neplatná operace pro aktivitu ID=%s.',$data->id);
        $this->flashMessage($_msg,'danger');
        $this->eventlog('activity',$_msg);
        $this->redirect('Activity:default');
        die();
      }

      $data->zruseni_zdarma_ts = $data->zruseni_zdarma * 3600;
      $data->zruseni_neucast_ts = $data->zruseni_neucast * 3600;
      $data->registrace_konec_ts = $data->registrace_konec * 3600;


      if ($operace == 'insert')
      {
        $data->created_by = $this->userName;

        try
        {
          $this->activityManager->insertAktivita($data);
        }
        catch (\Exception $e)
        {
          $_msg = sprintf('Chyba! Nová aktivita nebyla uložena.');
          $this->flashMessage($_msg,'danger');
          $this->eventlog('activity',$_msg);
          $this->redirect('Activity:default');
        }

        $_msg = sprintf('Nová aktivita byla uložena.');
        $this->flashMessage($_msg);
        $this->eventlog('activity',$_msg);
        $this->redirect('Activity:default');
      }

      if ($operace == 'update')
      {
        $data->updated_by = $this->userName;
        try
        {
          $this->activityManager->updateAktivita($data);
        }
        catch (\Exception $e)
        {
          $_msg = sprintf('Chyba! Aktivita ID=%s nebyla uložena.',$data->id);
          $this->flashMessage($_msg,'danger');
          $this->eventlog('activity',$_msg);
          $this->redirect('Activity:default');
        }

        $_msg = sprintf('Aktivita ID=%s byla uložena.',$data->id);
        $this->flashMessage($_msg);
        $this->eventlog('activity',$_msg);
        $this->redirect('Activity:default');
      }
    }
  }
