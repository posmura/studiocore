<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\Responses\JsonResponse;


final class SearchPresenter extends Nette\Application\UI\Presenter
{
/*
  public function __construct(
        private readonly Explorer $db
    ) {
        parent::__construct();
    }
 * 
 */

    public function actionLektor(?string $term = null): void
    {
      /*
        $term = trim((string) $term);
        if (mb_strlen($term) < 3) {
            $this->sendJson([]); // nic nevracej, když je dotaz krátký
            return;
        }

        // Přizpůsob si tabulku/sloupce
        $rows = $this->db->query(
            'SELECT id, name, email FROM users
             WHERE name LIKE ? OR email LIKE ?
             ORDER BY name LIMIT 10',
            $term . '%',
            $term . '%'
        )->fetchAll();

        // jQuery UI Autocomplete čeká objekty {label, value, ...}
        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'label' => $r->name . ' (' . $r->email . ')',
                'value' => $r->name, // co se vyplní do inputu
                'id'    => $r->id,   // vlastní data navíc
                'email' => $r->email,
            ];
        }

        $this->sendJson($items);
       *
       */
    }
}
