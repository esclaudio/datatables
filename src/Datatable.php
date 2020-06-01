<?php

namespace Esclaudio\Datatables;

use Esclaudio\Datatables\Query\Translator\TranslatorInterface;
use Esclaudio\Datatables\Query\Translator\AnsiTranslator;
use Esclaudio\Datatables\Query\Builder;
use Esclaudio\Datatables\DatabaseInterface;

class Datatable
{
    /**
     * Database
     *
     * @var \Esclaudio\Datatables\DatabaseInterface
     */
    protected $db;

    /**
     * Base query
     *
     * @var \Esclaudio\Datatables\Query\Builder|null
     */
    protected $baseQuery = null;

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

    public function __construct(DatabaseInterface $db, Options $options, Builder $query = null)
    {
        $this->db = $db;
        $this->options = $options;
        $this->baseQuery = $query;
    }

    public function from(string $table, TranslatorInterface $translator = null): Builder
    {
        return $this->baseQuery = (new Builder())->setTranslator($translator ?? new AnsiTranslator)->from($table);
    }

    public function add(string $column, \Closure $callback): self
    {
        $this->addedColumns[$column] = $callback;
        return $this;
    }

    public function addRowId(string $column): self
    {
        return $this->add('DT_RowId', function ($row) use ($column) {
            return 'row_' . $row[$column];
        });
    }

    public function edit(string $column, \Closure $callback): self
    {
        $this->editedColumns[$column] = $callback;
        return $this;
    }

    public function hide(string $column): self
    {
        $this->hiddenColumns[] = $column;
        return $this;
    }

    public function response(): array
    {
        return [
            'draw'            => $this->options->draw(),
            'recordsTotal'    => $this->total(),
            'recordsFiltered' => $this->filteredTotal(),
            'data'            => $this->data(),
        ];
    }

    public function query(): ?Builder
    {
        if ( ! $this->baseQuery) return null;
        
        $query = clone $this->baseQuery;

        $this->filter($query);
        $this->order($query);
        $this->limit($query);
        
        return $query;
    }

    protected function data(): array
    {
        if ( ! $query = $this->query()) return [];

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
        }, $this->db->fetchAll($query));
    }

    protected function total(): int
    {
        if ( ! $this->baseQuery) return 0;

        $query = clone $this->baseQuery;

        $query->selectRaw('count(*)')
            ->resetOrder()
            ->limit(0);

        return (int)$this->db->fetchColumn($query);
    }

    protected function filteredTotal(): int
    {
        if ( ! $this->baseQuery) return 0;

        $query = clone $this->baseQuery;

        $this->filter($query);

        $query->selectRaw('count(*)')
            ->resetOrder()
            ->limit(0);

        return (int)$this->db->fetchColumn($query);
    }

    protected function filter(Builder $query): void
    {
        $queryFields = $this->fields($query);
        
        /** @var \Esclaudio\Datatables\Column[] $searchableColumns */
        $searchableColumns = array_filter($this->options->columns(), function (Column $column) {
            return $column->searchable;
        });

        $globalSearch = $this->options->searchValue();

        if ($searchableColumns && $globalSearch) {
            $query->where(function ($query) use ($searchableColumns, $queryFields, $globalSearch) {
                foreach ($searchableColumns as $column) {
                    $field = $queryFields[$column->name] ?? null;

                    if ($field) {
                        $query->orWhere($field, 'like', "%$globalSearch%");
                    }
                }
            });
        }

        foreach ($searchableColumns as $column) {
            $columnSearch = $column->searchValue;
            
            if ($columnSearch) {
                $field = $queryFields[$column->name] ?? null;

                if ($field) {
                    $query->where($field, 'like', "%$columnSearch%");
                }
            }
        }
    }

    protected function order(Builder $query): void
    {
        $queryFields = $this->fields($query);

        foreach ($this->options->order() as $order) {
            $field = $queryFields[$order->column()] ?? null;

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

    protected function fields(Builder $query): array
    {
        $fields = [];

        // The reason to cast fields to string is because
        // I can have an expression object

        foreach ($query->getFields() as $alias => $field) {
            if (is_int($alias)) {
                $alias = (string)$field;
            }

            $fields[$alias] = (string)$field;
        }

        return $fields;
    }
}
