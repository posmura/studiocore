<?php

  declare(strict_types=1);

	  namespace App\Presenters;

		  use App\Model\ActivityManager;
		  use Nette\Application\UI\Form;
		  use Nette\Application\UI\Multiplier;

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

	      $this->requireStaff();
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
     * Mazání aktivity
     *
     * @param int $id ID aktivity
     * @return void
     */
		    public function renderDelete($id): void
		    {
		      $this->requireAdmin();

		      $this->error('Mazání aktivity je nutné provést formulářem.',405);
		    }


		    /**
		     * Vrací čitelný přehled vazeb aktivity.
		     *
		     * @param array $usage Počty vazeb aktivity
		     * @return string
		     */
		    private function formatActivityUsage(array $usage): string
		    {
		      $labels = array(
		        'diary_total' => 'lekce',
		        'membership_card_total' => 'permanentky',
		        'sales_total' => 'prodeje',
		        'registration_total' => 'registrace',
		        'credits_total' => 'nenulové kredity',
		      );

		      $items = array();
		      foreach ($labels as $key => $label)
		      {
		        $count = (int) ($usage[$key] ?? 0);
		        if ($count > 0)
		          $items[] = sprintf('%s: %d',$label,$count);
		      }

		      return $items ? implode(', ',$items) : 'žádné vazby';
		    }


		    /**
		     * Formuláře pro mazání aktivity.
	     *
	     * @return Multiplier
	     */
	    protected function createComponentDeleteAktivitaForm(): Multiplier
	    {
	      $this->requireAdmin();

	      return new Multiplier(function (string $id)
	      {
	        $form = new Form;
	        $form->setHtmlAttribute('style','display:inline;');
	        $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');
	        $form->addHidden('id',$id);
	        $form->addSubmit('send',\Nette\Utils\Html::el('i')->setAttribute('class','bi bi-trash'))
	          ->renderAsButton()
	          ->setHtmlAttribute('class','btn btn-sm btn-danger')
	          ->setHtmlAttribute('title','Smazat')
	          ->setHtmlAttribute('aria-label','Smazat')
	          ->setHtmlAttribute('onclick',"return confirm('Opravdu chcete záznam odstranit?');");
	        $form->onSuccess[] = [$this,'deleteAktivitaFormSucceeded'];

	        return $form;
	      });
	    }


	    /**
	     * Akce po odeslání formuláře pro mazání aktivity.
	     *
	     * @param Form $form Objekt formuláře
	     * @param type $data Data z formuláře
	     * @return void
	     */
	    public function deleteAktivitaFormSucceeded(Form $form,$data): void
	    {
	      $this->requireAdmin();

		      $data = self::array_to_object(['id' => $data->id, 'deleted_by' => $this->userName]);
		      $activityLabel = $this->logActivityLabelById((int) $data->id);

		      try
		      {
	        $result = $this->activityManager->deleteAktivita($data);
	      }
	      catch (\Throwable $e)
	      {
		        $_msg = sprintf('Chyba! Aktivita %s nebyla smazána. DB: %s',$activityLabel,$e->getMessage());
	        $this->eventlog('activity',$_msg);
	        $this->flashMessage(sprintf('Chyba! Aktivita %s nebyla smazána.',$activityLabel),'danger');
	        $this->redirect('Activity:default');
	      }

	      if ($result === ActivityManager::DELETE_HAS_RELATIONS)
	      {
	        $usage = $this->activityManager->getAktivitaUsageCounts((int) $data->id);
	        $_msg = sprintf('Aktivitu %s nelze smazat, protože je použita: %s.',$activityLabel,$this->formatActivityUsage($usage));
	        $this->eventlog('activity',$_msg);
	        $this->flashMessage($_msg,'danger');
	        $this->redirect('Activity:default');
	      }

	      if ($result === ActivityManager::DELETE_NOT_FOUND)
	      {
	        $_msg = sprintf('Chyba! Aktivita %s nebyla nalezena nebo už byla smazána.',$activityLabel);
	        $this->eventlog('activity',$_msg);
	        $this->flashMessage($_msg,'danger');
	        $this->redirect('Activity:default');
	      }

		      $_msg = sprintf('Aktivita %s byla smazána.',$activityLabel);
	      $this->flashMessage($_msg);
	      $this->eventlog('activity',$_msg);
	      $this->redirect('Activity:default');
    }


    /**
     * Editace aktivity
     *
     * @param int $id ID aktivity
     * @return void
     */
		    public function renderEdit(int $id = 0): void
		    {
		      $this->requireAdmin();

		      if ($id === 0)
		        return;

		      $params = self::array_to_object(array('id' => $id));

	      $data = $this->activityManager->getAktivita($params);
	      if (!$data)
	      {
	        $_msg = sprintf('Chyba! Aktivita ID=%d nebyla nalezena nebo už byla smazána.',$id);
	        $this->flashMessage($_msg,'danger');
	        $this->eventlog('activity',$_msg);
	        $this->redirect('Activity:default');
	      }

	      $this->template->data = $data;

	      $this->eventlog('activity',sprintf('Zobrazena editace aktivity %s.',$this->logActivityLabel($data,$id)));
    }


    /**
     * Formuláře pro aktivitu
     *
     * @return Form
     */
	    protected function createComponentAktivitaForm(): Form
	    {
	      $this->requireAdmin();

	      $form = new Form;

      $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

	      $form->addHidden('id')
	        ->setDefaultValue(0)
	        ->setHtmlAttribute('id','frm-aktivitaForm-id');

	      $form->addText('nazev','Název:')
	        ->setHtmlAttribute('class','form-control')
	        ->setHtmlAttribute('placeholder','')
	        ->addRule($form::MAX_LENGTH,'%label může mít maximálně %d znaků!',255)
	        ->setRequired('%label je vyžadováno!');

	      $form->addText('vstupy_min','Počet vstupů - min:')
	        ->setHtmlType('number')
	        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
	        ->addRule($form::MIN,'%label nesmí být menší než %d!',0)
	        ->setHtmlAttribute('class','form-control')
	        ->setHtmlAttribute('min',0)
	        ->setHtmlAttribute('placeholder','')
	        ->setRequired('%label je vyžadována!');

	      $form->addText('vstupy_max','Počet vstupů - max:')
	        ->setHtmlType('number')
	        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
	        ->addRule($form::MIN,'%label musí být alespoň %d!',1)
	        ->setHtmlAttribute('class','form-control')
	        ->setHtmlAttribute('min',1)
	        ->setHtmlAttribute('placeholder','')
	        ->setRequired('%label je vyžadována!');

	      $form->addText('zruseni_zdarma','Doba zrušení klientem zdarma [hod]:')
	        ->setHtmlType('number')
	        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
	        ->addRule($form::MIN,'%label nesmí být menší než %d!',0)
	        ->setHtmlAttribute('class','form-control')
	        ->setHtmlAttribute('min',0)
	        ->setHtmlAttribute('placeholder','')
	        ->setRequired('%label je vyžadována!');

	      $form->addText('zruseni_neucast','Doba zrušení pro neúčast [hod]:')
	        ->setHtmlType('number')
	        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
	        ->addRule($form::MIN,'%label nesmí být menší než %d!',0)
	        ->setHtmlAttribute('class','form-control')
	        ->setHtmlAttribute('min',0)
	        ->setHtmlAttribute('placeholder','')
	        ->setRequired('%label je vyžadována!');

	      $form->addText('registrace_konec','Konec registrace před lekcí [hod]:')
	        ->setHtmlType('number')
	        ->addRule($form::INTEGER,'%label musí být číselná hodnota!')
	        ->addRule($form::MIN,'%label nesmí být menší než %d!',0)
	        ->setHtmlAttribute('class','form-control')
	        ->setHtmlAttribute('min',0)
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
		      $this->requireAdmin();

	      $data->id = (int) $data->id;
	      $data->vstupy_min = (int) $data->vstupy_min;
	      $data->vstupy_max = (int) $data->vstupy_max;
	      $data->zruseni_zdarma = (int) $data->zruseni_zdarma;
	      $data->zruseni_neucast = (int) $data->zruseni_neucast;
	      $data->registrace_konec = (int) $data->registrace_konec;

	      if ($data->vstupy_min > $data->vstupy_max)
	      {
	        $form['vstupy_max']->addError('Počet vstupů max musí být větší nebo roven minimu.');
	        return;
	      }

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
	        $_msg = sprintf('Chyba! Neplatná operace pro aktivitu %s.',$this->logActivityLabel($data,(int) $data->id));
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
          $data->id = $this->activityManager->insertAktivita($data);
        }
	      catch (\Throwable $e)
	        {
		          $_msg = sprintf('Chyba! Nová aktivita %s nebyla uložena. DB: %s',$this->logActivityLabel($data),$e->getMessage());
	          $this->flashMessage(sprintf('Chyba! Nová aktivita %s nebyla uložena.',$this->logActivityLabel($data)),'danger');
	          $this->eventlog('activity',$_msg);
	          $this->redirect('Activity:default');
        }

	        $_msg = sprintf('Nová aktivita %s byla uložena.',$this->logActivityLabel($data));
        $this->flashMessage($_msg);
        $this->eventlog('activity',$_msg);
        $this->redirect('Activity:default');
      }

	      if ($operace == 'update')
	      {
	        $data->updated_by = $this->userName;
	        $activityLabel = $this->logActivityLabelById((int) $data->id);

	        if (!$this->activityManager->getAktivita($data))
	        {
	          $_msg = sprintf('Chyba! Aktivita %s nebyla nalezena nebo už byla smazána.',$activityLabel);
	          $this->flashMessage($_msg,'danger');
	          $this->eventlog('activity',$_msg);
	          $this->redirect('Activity:default');
	        }

	        try
	        {
	          $updated = $this->activityManager->updateAktivita($data);
	        }
	        catch (\Throwable $e)
	        {
		          $_msg = sprintf('Chyba! Aktivita %s nebyla uložena. DB: %s',$this->logActivityLabel($data,(int) $data->id),$e->getMessage());
	          $this->flashMessage(sprintf('Chyba! Aktivita %s nebyla uložena.',$this->logActivityLabel($data,(int) $data->id)),'danger');
	          $this->eventlog('activity',$_msg);
	          $this->redirect('Activity:default');
	        }

	        if (!$updated)
	        {
	          $_msg = sprintf('Chyba! Aktivita %s nebyla nalezena nebo už byla smazána.',$activityLabel);
	          $this->flashMessage($_msg,'danger');
	          $this->eventlog('activity',$_msg);
	          $this->redirect('Activity:default');
	        }

		        $_msg = sprintf('Aktivita %s byla uložena.',$this->logActivityLabel($data,(int) $data->id));
        $this->flashMessage($_msg);
        $this->eventlog('activity',$_msg);
        $this->redirect('Activity:default');
      }
    }
  }
