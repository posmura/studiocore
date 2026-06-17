<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Form;
use BulkGate\Sdk\Sender;
use BulkGate\Sdk\Message\Sms;

/**
 * Třída presenteru pro odeslání SMS
 */
final class SmsPresenter extends BasePresenter
{
  /** @var Sender @inject */
  public $sender;

  /**
   * Datum objednávky
   * @var array
   */
  private $orderDate;

  /**
   * Data příjemců SMS
   * @var array
   */
  private $smsRecipients;

  /**
   * Počet data příjemců SMS
   * @var int
   */
  private $smsRecipientsCount;


  /**
   * Inicalizace presenteru
   *
   * @return void
   */
	  public function startup(): void {
	    parent::startup();

	    $this->requireStaff();

    // Nastavení datumu
    $orderDate = $this->getParameter('orderDate');
    if (!$orderDate)
      $this->orderDate = $this->orderDateTomorrow;
    else
      $this->orderDate = $orderDate;

    // Nastavení dat
    $this->smsRecipients = $this->factoryManager->getOrders($this->orderDate);
    $this->smsRecipientsCount = count($this->smsRecipients);
  }


  /**
   * Seznam příjemců SMS
   *
   * @return void
   */
  public function renderDefault($orderDate = null,$showForm = true): void
  {
    $this->template->showTable = true;
    $orderDateText = $this->dateOrderToDateForm($this->orderDate);

    if($this->orderDate <= $this->orderDateToday)
    {
      $this->flashMessage('Chyba! Nelze odeslat SMS pro dnešní nebo starší objednávky.','danger');
      $this->eventlog('sms',sprintf('Chyba! Nelze odeslat SMS pro objednávky na den %s, protože jde o dnešní nebo starší datum.',$orderDateText));

      $this->template->showTable = false;
    }

    $this->template->orderDate = $this->orderDate;
    $this->template->formDate = $orderDateText;

    $this->template->showForm = $showForm;

    $this->template->smsRecipients = $this->smsRecipients;

    $this->eventlog('sms',sprintf('Seznam příjemců SMS pro den %s byl zobrazen (%d příjemců).',$orderDateText,$this->smsRecipientsCount));
  }


  /**
   * Odeslání SMS
   *
   * @param string $sms_phone Telefonní číslo
   * @param string $sms_text Text SMS zprávy
   * @return bool
   */
  public function renderSend($sms_phone = null,$sms_text = null): bool
  {
    if ((!$sms_phone) || (!$sms_text))
      return false;

    if (! $this->sender->send(new Sms($sms_phone,$sms_text)))
      return false;

    return true;
    }


  /**
   * Vrací objekt Form formuláře pro výběr datumu
   *
   * @return Form
   */
	  protected function createComponentOrderDateForm(): Form
	  {
	    $this->requireStaff();

	    // Definice formuláře
	    $form = new Form;
	    $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');
	    $form->setHtmlAttribute('class','d-flex flex-row align-items-center flex-wrap');

    // Datum
    $form->addText('date','Datum:')
      ->setValue($this->dateOrderToDateForm($this->orderDate))
      ->setHtmlAttribute('class','form-control')
      ->setHtmlAttribute('placeholder',"klikněte pro výběr datumu")
      ->setHtmlAttribute("style","cursor: pointer; width: 220px; background: #D1E7DD; border: 1px solid #D1E7DD; color: #0A3622;")
      ->setHtmlAttribute('readonly','readonly');

    // Tlačítko pro odeslání
    $form->addSubmit('send','OK')
      ->setHtmlAttribute('class','btn btn-success');

    // Definice akce
    $form->onSuccess[] = [$this,'formSucceeded'];

    return $form;
  }


  /**
   * Zpracování dat odeslaných z formuláře
   *
   * @param Form $form Objekt Form formuláře
   * @param object $data Data z formuláře
   * @return void
   */
	  public function formSucceeded(Form $form,$data): void
	  {
	    $this->requireStaff();

	    $orderDate = $this->dateFormToDateOrder($data->date);

    if (!$orderDate)
      $orderDate = $this->orderDateToday;

    $this->redirect('Sms:default',$orderDate);
  }


  /**
   * Vrací objekt Form formuláře seseznamem příjemců SMS
   *
   * @return Form
   */
	  protected function createComponentSmsRecipientsForm(): Form
	  {
	    $this->requireStaff();

	    // Definice formuláře
	    $form = new Form;
	    $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

	    // SMS zpráva
    $form->addTextArea('text_sms','Text SMS zprávy')
      ->setValue($this->smsOrderText)
      ->setHtmlAttribute('class','form-control')
      ->setHtmlAttribute('placeholder','Text SMS zprávy')
      ->setHtmlAttribute('style','font-weight:bold;font-size:125%;background: #D1E7DD;')
      ->addRule($form::REQUIRED,'%label musí být zadán ')
      ->addRule($form::MAX_LENGTH, '%label je příliš dlouhý', 160);

    // Kontakty
    foreach ($this->smsRecipients as $key =>$items)
    {
      // Datum
      $form->addHidden('date_'.$key,$this->dateOrderToDateForm($this->orderDate));

      // Čas
      $form->addText('time_'.$key,'čas')
        ->setValue($this->timeOrderToTimeForm($items['hour_from'],$items['min_from'],':'))
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder',"čas")
        ->setHtmlAttribute('style','background: #f3f3f3;')
        ->setHtmlAttribute('title',$this->timeOrderToTimeForm($items['hour_from'],$items['min_from'],':'))
        ->setHtmlAttribute('onclick','alert("Automaticky vyplňované pole");')
        ->setHtmlAttribute('readonly','readonly');

      // Pacient
      $form->addText('name_'.$key,'jméno')
        ->setValue(trim($items['surname'].' '.$items['name']))
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder',"pacient")
        ->setHtmlAttribute('style','background: #f3f3f3;')
        ->setHtmlAttribute('title',trim($items['surname'].' '.$items['name']))
        ->setHtmlAttribute('onclick','alert("Automaticky vyplňované pole");')
        ->setHtmlAttribute('readonly','readonly');

      // Telefon
      $form->addText('phone_'.$key,'telefon')
        ->setValue($items['phone'])
        ->setHtmlAttribute('title',$items['phone'])
        ->setHtmlAttribute('class','form-control')
        ->setHtmlAttribute('placeholder',"zadejte telefon");

      // Výběr položky
      $form->addCheckbox('check_'.$key,'');
    }

    // Počet záznamů
    $form->addHidden('dataCount',$this->smsRecipientsCount);

    // Tlačítko pro odeslání
    $form->addSubmit('send','Odeslat')
      ->setHtmlAttribute('class','btn btn-success');

    // Definice akce
    $form->onSuccess[] = [$this,'smsRecipientsFormSucceeded'];

    return $form;
  }


  /**
   * Zpracování dat odeslaných z formuláře
   *
   * @param Form $form Objekt Form formuláře
   * @param array $data Data z formuláře
   * @return void
   */
	  public function smsRecipientsFormSucceeded(Form $form,$data): void
	  {
	    $this->requireStaff();

	    // inicializace zpracování dat
    $send_status = false;
    $orderDateText = $this->dateOrderToDateForm($this->orderDate);

    // nastavení textu
    $data_text_sms = trim(htmlspecialchars($data['text_sms']));

    // test na text sms zprávy
    if(!$data_text_sms)
    {
      $this->flashMessage('Chyba! Nebyl zadán text SMS zprávy. SMS nebyly odeslány.','danger');
      $this->eventlog('sms',sprintf('Chyba! Nebyl zadán text SMS zprávy pro objednávky na den %s. SMS nebyly odeslány.',$orderDateText));
      $this->redirect('Sms:',$this->orderDate,false);
      die();
    }

    // zpracování dat
    for ($i = 0; $i < $data['dataCount']; $i++)
    {
      // kontrola zaškrntutí záznamu
      $i_check = 'check_'.$i;
      if (!$data[$i_check])
        continue;

      // aspoň jedna sms byla zaškrtnuta
      $send_status = true;

      // nastavení dat
      $i_date = 'date_'.$i;
      $sms_date = $data[$i_date];
      $i_time = 'time_'.$i;
      $sms_time = $data[$i_time];
      $i_name = 'name_'.$i;
      $sms_name = $data[$i_name];

      // sestavení textu sms zprávy
      $sms_text = sprintf($data_text_sms,$sms_date,$sms_time);

      // kontrola telefonního čísla
      $i_phone = 'phone_'.$i;
      $sms_phone = $this->checkSmsPhone($data[$i_phone]);

      if (!$sms_phone)
      {
        $this->flashMessage('Chyba! Chybný formát telefonního čísla "'.$data[$i_phone].'". SMS pro "'.$sms_name.'" nebyla odeslána.','danger');
        $this->eventlog('sms',sprintf('Chyba! Chybný formát telefonního čísla "%s". SMS pro "%s" na termín %s %s nebyla odeslána.',$data[$i_phone],$sms_name,$sms_date,$sms_time));
        continue;
      }

	        // odeslání SMS
	        if ($this->renderSend($sms_phone,$sms_text))
	        {
          $this->flashMessage('SMS "'.$sms_phone.'" pro "'.$sms_name.'" byla předána k odeslání: '.$sms_text);
          $this->eventlog('sms',sprintf('SMS "%s" pro "%s" na termín %s %s byla předána k odeslání: %s',$sms_phone,$sms_name,$sms_date,$sms_time,$sms_text));
        }
        else
        {
          $this->flashMessage('Chyba: Problém při předání SMS "'.$sms_phone.'" pro "'.$sms_name.'" k odeslání.','danger');
	          $this->eventlog('sms',sprintf('Chyba: Problém při předání SMS "%s" pro "%s" na termín %s %s k odeslání.',$sms_phone,$sms_name,$sms_date,$sms_time));
	        }

	    }

    // žádná sms nebyla zaškrtnuta
    if(!$send_status)
    {
      $this->flashMessage('Chyba! Nebyl vybrán žádný odsílatel. SMS nebyly odeslány.','danger');
      $this->eventlog('sms',sprintf('Chyba! Nebyl vybrán žádný příjemce SMS pro objednávky na den %s. SMS nebyly odeslány.',$orderDateText));
    }

    $this->redirect('Sms:',$this->orderDate,false);
  }

}
