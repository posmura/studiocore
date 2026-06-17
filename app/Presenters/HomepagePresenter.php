<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Form;


/**
 * Presenter úvodní stránky
 */
final class HomepagePresenter extends BasePresenter
{
  /**
   * Render: Výchozí šablona
   *
   * @return void
   */
  public function renderDefault(): void
  {
    $this->redirect('Diary:default');
    //$this->template->posts = $this->postManager->getAllPosts();
    //$this->eventlog('home','Přehled příspěvků byl zobrazen.');
  }


  /**
   * Render: Odstranit záznam
   *
   * @param int $postId ID příspěvku
   * @param string $deletedBy Jmén uživatele
   * @return void
   */
  public function renderDelete($postId,$deletedBy): void
  {
    $this->requireAdmin();
    $this->error('Mazání příspěvku je nutné provést formulářem.',405);
    $postLabel = $this->logPostLabel(null,(int) $postId);

    try {
      $this->postManager->deletePost($postId,$deletedBy);
      $this->flashMessage('Příspěvěk byl smazán.');
    }
    catch (\Exception $e)
    {
      $this->eventlog('home','Chyba! Příspěvek '.$postLabel.' nebyl smazán!');
      $this->flashMessage('Chyba! Příspěvěk nebyl smazán!',"danger");
      $this->redirect('Homepage:');
    }
    $this->eventlog('home','Příspěvek '.$postLabel.' byl smazán!');
    $this->redirect('Homepage:');
  }


  /**
   * Definice formuláře pro příspěvěk
   *
   * @return Form Foruláře
   */
  protected function createComponentPostForm(): Form
  {
    $this->requireAdmin();

    $userName = $this->getUser()->identity->name;

    $form = new Form;
    $form->addProtection('Vypršela platnost formuláře, odešlete jej prosím znovu.');

    $form->addText('title','Nadpis:')
      ->setHtmlAttribute("class","form-control")
      ->setRequired();

    $form->addTextArea('content','Obsah:')
      ->setHtmlAttribute("class","form-control")
      ->setRequired();

    $form->addHidden("created_by")
      ->setValue($userName);

    $form->addSubmit('send','Publikovat')
      ->setHtmlAttribute("class","btn btn-success");

    $form->onSuccess[] = [$this,'formSucceeded'];

    return $form;
  }


  /**
   * Zpracování formuláře pro příspěvek
   *
   * @param Form $form Formulář
   * @param object $data Data formuláře
   * @return void
   */
  public function formSucceeded(Form $form,$data): void
  {
    $this->requireAdmin();

    $postId = isset($data->id) ? $data->id : 0;
    $postLabel = $this->logPostLabel($data,(int) $postId);

    try
    {
      $this->postManager->insertPost($data);
      $this->flashMessage('Příspěvěk byl zveřejněn.');
    }
    catch (\Exception $e)
    {
      $this->eventlog('home','Chyba! Příspěvek '.$postLabel.' nebyl zveřejněn!');
      $this->flashMessage('Chyba! Příspěvěk nebyl zveřejněn!',"danger");
      $this->redirect('Homepage:');
    }

    $this->eventlog('home','Příspěvek '.$postLabel.' byl zveřejněn!');
    $this->redirect('Homepage:');
  }

}
