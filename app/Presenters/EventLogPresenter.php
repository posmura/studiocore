<?php

  declare(strict_types=1);

  namespace App\Presenters;

  use Nette\Utils\Html;

  /**
   * Třída pro správu eventlogu
   */
  final class EventLogPresenter extends BasePresenter
  {

    /**
     * Počet záznamů na stránce
     */
    const SQL_LIMIT = 100;

    /**
     * Nastavení první stránky pro výpočet offsetu
     */
    const SQL_FIRST_PAGE = 1;

    /**
     * Inicalizace presenteru
     *
     * @return void
     */
	    public function startup(): void
	    {
	      parent::startup();

	      $this->requireAdmin();
	    }


    /**
     * Zobrazení logu
     *
     * @param int $page
     * @return void
     */
    public function renderDefault(): void
    {
      // načtení dat pro danou stránku
      $eventlog = $this->logManager->getEventlog();

      foreach ($eventlog as $item)
        $item->action_html = $this->formatEventlogAction((string) $item->action);

      // předání hodonot do šablony
      $this->template->eventlog = $eventlog;
    }


    /**
     * Vrací text akce s odkazy na rozpoznaná ID uživatele, lekce, prodeje, aktivity a permanentky.
     */
    private function formatEventlogAction(string $action): Html
    {
      $html = Html::el();
      $lastOffset = 0;

      if (!preg_match_all('~(?<![A-Za-z_])ID=(\d+)~',$action,$matches,PREG_OFFSET_CAPTURE))
      {
        $html->addText($action);
        return $html;
      }

      foreach ($matches[0] as $key => $match)
      {
        $text = $match[0];
        $offset = $match[1];
        $id = (int) $matches[1][$key][0];

        $html->addText(substr($action,$lastOffset,$offset - $lastOffset));

        $link = $this->getEventlogIdLink($action,$offset,$id);
        if ($link)
        {
          $html->addHtml(
            Html::el('a',['class' => 'eventlog-link'])
              ->href($link['href'])
              ->title($link['title'])
              ->setText($text)
          );
        }
        else
          $html->addText($text);

        $lastOffset = $offset + strlen($text);
      }

      $html->addText(substr($action,$lastOffset));

      return $html;
    }


    /**
     * Určí cíl odkazu podle nejbližšího popisku entity před ID v textu logu.
     *
     * @return array{href: string, title: string}|null
     */
    private function getEventlogIdLink(string $action,int $offset,int $id): ?array
    {
      if ($id <= 0)
        return null;

      $entity = $this->detectEventlogEntityBeforeId($action,$offset);

      if ($entity === 'user')
      {
        return [
          'href' => $this->link('User:user',$id),
          'title' => 'Zobrazit nastavení uživatele',
        ];
      }

      if ($entity === 'lesson')
      {
        return [
          'href' => $this->getEventlogLessonLink($id),
          'title' => 'Zobrazit správu klientů na lekci',
        ];
      }

      if ($entity === 'membership_card')
      {
        return [
          'href' => $this->link('MembershipCard:edit',$id),
          'title' => 'Zobrazit nastavení permanentky',
        ];
      }

      if ($entity === 'sale')
      {
        return [
          'href' => $this->link('Sales:default',['eventlogSaleId' => $id]),
          'title' => 'Zobrazit prodej',
        ];
      }

      if ($entity === 'activity')
      {
        return [
          'href' => $this->link('Activity:edit',$id),
          'title' => 'Zobrazit nastavení aktivity',
        ];
      }

      return null;
    }


    /**
     * Najde poslední relevantní typ entity před textem ID=...
     */
    private function detectEventlogEntityBeforeId(string $action,int $offset): ?string
    {
      $context = substr($action,0,$offset);
      $context = mb_substr($context,-180,180,'UTF-8');
      $context = mb_strtolower($context,'UTF-8');

      $patterns = [
        'user' => '~uživatel[\p{L}]*|klient[\p{L}]*|náhradník[\p{L}]*|lektor[\p{L}]*~u',
        'lesson' => '~lekc[\p{L}]*|událost[\p{L}]*|termín[\p{L}]*~u',
        'membership_card' => '~permanentk[\p{L}]*~u',
        'sale' => '~prodej[\p{L}]*~u',
        'activity' => '~aktivit[\p{L}]*~u',
      ];

      $foundType = null;
      $foundOffset = -1;

      foreach ($patterns as $type => $pattern)
      {
        if (!preg_match_all($pattern,$context,$matches,PREG_OFFSET_CAPTURE))
          continue;

        foreach ($matches[0] as $match)
        {
          if ($match[1] > $foundOffset)
          {
            $foundOffset = $match[1];
            $foundType = $type;
          }
        }
      }

      return in_array($foundType,['user','lesson','membership_card','sale','activity'],true) ? $foundType : null;
    }


    /**
     * Vrací odkaz na správu klientů na lekci.
     */
    private function getEventlogLessonLink(int $diaryId): string
    {
      return $this->link('Diary:users',['diary_id' => $diaryId]);
    }
  }
