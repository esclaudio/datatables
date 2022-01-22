<?php

namespace Esclaudio\Datatables\Database;

use Esclaudio\Datatables\Query\Builder;
use Esclaudio\Datatables\Query\Grammars\Grammar;

interface ConnectionInterface
{
    public function fetchAll(Builder $query): array;

    public function fetchColumn(Builder $query, int $column = 0);

    public function grammar(): Grammar;
}
