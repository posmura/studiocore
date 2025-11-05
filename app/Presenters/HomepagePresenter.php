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
    try {
      $this->postManager->deletePost($postId,$deletedBy);
      $this->flashMessage('Příspěvěk byl smazán.');
    }
    catch (\Exception $e)
    {
      $this->eventlog('home','Chyba! Položka '.$postId.' nebyla smazána!');
      $this->flashMessage('Chyba! Příspěvěk nebyl smazán!',"danger");
    }
    $this->eventlog('home','Položka '.$postId.' byla smazána!');
    $this->redirect('Homepage:');
  }


  /**
   * Definice formuláře pro příspěvěk
   *
   * @return Form Foruláře
   */
  protected function createComponentPostForm(): Form
  {
    $userName = $this->getUser()->identity->name;

    $form = new Form;

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
    $postId = isset($data->id) ? $data->id : 0;

    try
    {
      $this->postManager->insertPost($data);
      $this->flashMessage('Příspěvěk byl zveřejněn.');
    }
    catch (\Exception $e)
    {
      $this->eventlog('home','Chyba! Položka '.$postId.' nebyla zveřejněna!');
      $this->flashMessage('Chyba! Příspěvěk nebyl zveřejněn!',"danger");
    }

    $this->eventlog('home','Položka '.$postId.' byla zveřejněna!');
    $this->redirect('Homepage:');
  }

}
