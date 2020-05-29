<?php

namespace Esclaudio\Datatables\Contracts;

use Esclaudio\Datatables\Query;

interface Database
{
    public function count(Query $query): int;
    public function filteredCount(Query $query): int;
    public function execute(Query $query, array $parameters): array;
}