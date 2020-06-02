<?php

namespace Esclaudio\Datatables;

use Esclaudio\Datatables\Query\Builder;
use Esclaudio\Datatables\Database\DatabaseInterface;
use Esclaudio\Datatables\Database\Connection;

class Datatables
{
    /**
     * Database
     *
     * @var \Esclaudio\Datatables\Database\DatabaseInterface
     */
    protected $connection;

    /**
     * Base query
     *
     * @var \Esclaudio\Datatables\Query\Builder|null
     */
    protected $baseQuery;

    /**
     * Options
     *
     * @var \Esclaudio\Datatables\Options
     */
    protected $options;

    /**
     * Hidden columns
     *
     * @var array
     */
    protected $hiddenColumns = [];

    /**
     * Added columns
     *
     * @var array
     */
    protected $addedColumns = [];

    /**
     * Edited columns
     *
     * @var array
     */
    protected $editedColumns = [];

    public function __construct(Connection $connection, Options $options, Builder $query = null)
    {
        $this->connection = $connection;
        $this->options = $options;
        $this->baseQuery = $query ?? new Builder($connection->grammar());
    }

    public function from(string $table): self
    {
        $this->baseQuery->from($table);
        return $this;
    }

    /**
     * @param string|array $fields
     * @return self
     */
    public function select($fields): self
    {
        $this->baseQuery->select($fields);
        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->baseQuery->selectRaw($expression);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): self
    {
        $this->baseQuery->join($table, $first, $operator, $second);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return$this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * @param string|\Closure $column
     * @param string|array $operator
     * @param string|array|null $value
     * @param string $type
     * @return self
     */
    public function where($column, $operator = null, $value = null, string $type = 'and'): self
    {
        $this->baseQuery->where($column, $operator, $value, $type);
        return $this;
    }

    public function orWhere($column, $param1 = null, $param2 = null): self
    {
        return $this->where($column, $param1, $param2, 'or');
    }

    public function whereIn($column, array $params): self
    {
        return $this->where($column, 'in', $params);
    }

    public function whereNotIn($column, array $params): self
    {
        return $this->where($column, 'not in', $params);
    }

    public function whereNull($column): self
    {
        return $this->where($column, 'is', $this->baseQuery->raw('null'));
    }

    public function whereNotNull($column): self
    {
        return $this->where($column, 'is not', $this->baseQuery->raw('null'));
    }

    /**
     * @param string|array $column
     * @param string $type
     * @return self
     */
    public function orderBy($column, string $type = 'asc'): self
    {
        $this->baseQuery->orderBy($column, $type);
        return $this;
    }

    /**
     * @param string|array $column
     * @param string $type
     * @return self
     */
    public function orderByDesc($column): self
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * @param string|array $column
     * @param string $type
     * @return self
     */
    public function groupBy($column): self
    {
        $this->baseQuery->groupBy($column);
        return $this;
    }

    public function addColumn(string $column, \Closure $callback): self
    {
        $this->addedColumns[$column] = $callback;
        return $this;
    }

    public function addRowId(string $column): self
    {
        return $this->addColumn('DT_RowId', function ($row) use ($column) {
            return 'row_' . $row[$column];
        });
    }

    public function editColumn(string $column, \Closure $callback): self
    {
        $this->editedColumns[$column] = $callback;
        return $this;
    }

    public function hideColumn(string $column): self
    {
        $this->hiddenColumns[] = $column;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'draw'            => $this->options->draw(),
            'recordsTotal'    => $this->total(),
            'recordsFiltered' => $this->filteredTotal(),
            'data'            => $this->data(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function getQuery(): Builder
    {
        $query = clone $this->baseQuery;

        $this->filter($query);
        $this->order($query);
        $this->limit($query);

        return $query;
    }

    protected function data(): array
    {
        return array_map(function ($row) {
            $item = [];

            foreach ($row as $column => $value) {
                if (in_array($column, $this->hiddenColumns)) {
                    continue;
                }

                if (array_key_exists($column, $this->editedColumns)) {
                    $item[$column] = $this->editedColumns[$column]($row);
                } else {
                    $item[$column] = $value;
                }
            }

            foreach ($this->addedColumns as $column => $callback) {
                $item[$column] = $callback($row);
            }

            return $item;
        }, $this->connection->fetchAll($this->getQuery()));
    }

    protected function total(): int
    {
        $query = clone $this->baseQuery;

        $query->selectRaw('count(*)')
            ->resetOrder()
            ->limit(0);

        return (int)$this->connection->fetchColumn($query);
    }

    protected function filteredTotal(): int
    {
        $query = clone $this->baseQuery;
        
        $query->selectRaw('count(*)')
            ->resetOrder()
            ->limit(0);

        $this->filter($query);

        return (int)$this->connection->fetchColumn($query);
    }

    protected function filter(Builder $query): void
    {
        $validFields = $this->validFields();
        $searchableColumns = $this->options->searchableColumns();
        $globalSearch = $this->options->searchValue();

        if ($searchableColumns && $globalSearch) {
            $query->where(function ($query) use ($searchableColumns, $validFields, $globalSearch) {
                foreach ($searchableColumns as $column) {
                    $field = $validFields[$column->name()] ?? null;

                    if ($field) {
                        $query->orWhere($field, 'like', "%$globalSearch%");
                    }
                }
            });
        }

        foreach ($searchableColumns as $column) {
            $columnSearch = $column->searchValue();

            if ($columnSearch) {
                $field = $validFields[$column->name()] ?? null;

                if ($field) {
                    $query->where($field, 'like', "%$columnSearch%");
                }
            }
        }
    }

    protected function order(Builder $query): void
    {
        $validFields = $this->validFields();

        foreach ($this->options->order() as $order) {
            $field = $validFields[$order->column()] ?? null;

            if ($field) {
                if ($order->isAsc()) {
                    $query->orderBy($field);
                } else {
                    $query->orderByDesc($field);
                }
            }
        }
    }

    protected function limit(Builder $query): void
    {
        $query->limit($this->options->start(), $this->options->length());
    }

    protected function validFields(): array
    {
        $fields = [];

        // The reason to cast fields to string is because
        // there could be expression objects

        foreach ($this->baseQuery->getFields() as $alias => $field) {
            if (is_int($alias)) {
                $alias = (string)$field;
            }

            $fields[$alias] = (string)$field;
        }

        return $fields;
    }
}
