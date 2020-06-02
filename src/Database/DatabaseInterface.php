<?php

namespace Esclaudio\Datatables\Database;

use Esclaudio\Datatables\Query\Builder;

interface DatabaseInterface
{
    public function fetchAll(Builder $query): array;
    public function fetchColumn(Builder $query, int $column = 0);
}
