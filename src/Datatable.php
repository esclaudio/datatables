<?php

namespace Esclaudio\Datatables;

class Datatable
{
    /**
     * PDO
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Query
     *
     * @var Query
     */
    protected $query;

    /**
     * Option
     *
     * @var Option
     */
    protected $option;

    public function __construct(\PDO $pdo, Query $query, array $request)
    {
        $this->pdo = $pdo;
        $this->query = $query;
        $this->option = new Option($request);
    }

    public function sql(): string
    {
        $query = clone $this->query;

        $params = $this->params($query);

        $where = $this->filter($query, $params);
        $order = $this->order($query);
        $limit = $this->limit($query);

        return (string)$query;
    }

    public function response(): array
    {
        $query = clone $this->query;

        $total = $this->pdo->query($query->getCountSql())->fetchColumn();
        $filteredTotal = $this->pdo->query($query->getFilteredCountSql())->fetchColumn();

        $params = $this->params($query);

        $this->filter($query, $params);
        $this->order($query);
        $this->limit($query);

        $statement = $this->pdo->prepare($query);
        
        foreach ($params as $key => $param) {
            $statement->bindParam($key, $param['value'], $param['type']);
        }

        $statement->execute();
        $data = $statement->fetchAll();

        return [
            'draw'            => $this->option->draw(),
            'recordsTotal'    => (int)$total,
            'recordsFiltered' => (int)$filteredTotal,
            'data'            => $data,
        ];
    }

    private function params(Query $query): array
    {
        $searchValue = $this->option->searchValue();
        $fields = $query->getFields();
        $params = [];

        foreach ($this->option->columns() as $column) {
            if ($column['searchable'] == 'true') {
                $field = array_search($column['data'], $fields);

                if ($field) {
                    $params[':binding_' . count($params)] = [
                        'field' => $field,
                        'value' => '%'.$searchValue.'%',
                        'type'  => \PDO::PARAM_STR,
                    ];
                }
            }
        }

        return $params;
    }

    private function limit(Query $query): void
    {
        $query->limit($this->option->start(), $this->option->length());
    }

    private function order(Query $query): void
    {
        $columns = $this->option->columns();
        $fields = $query->getFields();

        foreach ($this->option->order() as $order) {
            $column = $columns[$order['column']];
            $field = array_search($column, $fields);

            if ($field) {
                if (strtolower($order['dir']) === 'desc') {
                    $query->orderDesc($field);
                } else {
                    $query->order($field);
                }
            }
        }
    }

    private function filter(Query $query, array $params): void
    {
        foreach ($params as $key => $param) {
            $query->where($param['field'] . ' LIKE ' . $key);
        }
    }
}
