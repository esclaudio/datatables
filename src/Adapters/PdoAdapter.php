<?php

namespace Esclaudio\Datatables\Adapters;

use PDOStatement;
use Esclaudio\Datatables\Query\Builder;
use Esclaudio\Datatables\DatabaseInterface;

class PdoAdapter implements DatabaseInterface
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function fetchAll(Builder $query): array
    {
        $statement = $this->execute($query);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchColumn(Builder $query, int $column = 0): ?string
    {
        $statement = $this->execute($query);

        return $statement->fetchColumn($column);
    }

    protected function execute(Builder $query): PDOStatement
    {
        $statement = $this->pdo->prepare($query);

        $statement->execute($query->getBindings());

        return $statement;
    }
}