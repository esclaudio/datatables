<?php

namespace Esclaudio\Datatables\Database;

use Esclaudio\Datatables\Query\Builder;
use Esclaudio\Datatables\Query\Grammars\Grammar;
use Esclaudio\Datatables\Query\Grammars\MySqlGrammar;
use Esclaudio\Datatables\Query\Grammars\PostgresGrammar;
use Esclaudio\Datatables\Query\Grammars\SQLiteGrammar;
use Exception;
use PDO;
use PDOStatement;

class Connection implements ConnectionInterface
{
    protected $pdo;

    protected $grammars = [
        'sqlite' => SQLiteGrammar::class,
        'mysql' => MySqlGrammar::class,
        'pgsql' => PostgresGrammar::class,
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function fetchAll(Builder $query): array
    {
        $statement = $this->execute($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchColumn(Builder $query, int $column = 0): ?string
    {
        $statement = $this->execute($query);

        return $statement->fetchColumn($column);
    }

    public function driver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function grammar(): Grammar
    {
        $driver = $this->driver();

        if (! array_key_exists($driver, $this->grammars)) {
            throw new Exception("Unsupported driver $driver");
        }

        return new $this->grammars[$driver];
    }

    protected function execute(Builder $query): PDOStatement
    {
        $statement = $this->pdo->prepare($query->sql());

        $statement->execute($query->getBindings());

        return $statement;
    }
}
