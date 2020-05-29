<?php

namespace Esclaudio\Datatables;

use Esclaudio\Datatables\Contracts\Database;

class Datatable
{
    /**
     * PDO
     *
     * @var \Esclaudio\Datatables\Contracts\Database
     */
    protected $db;

    /**
     * Query
     *
     * @var \Esclaudio\Datatables\Query
     */
    protected $query;

    /**
     * Options
     *
     * @var \Esclaudio\Datatables\Options
     */
    protected $options;

    public function __construct(Database $db, Query $query, array $request)
    {
        $this->db = $db;
        $this->query = $query;
        $this->options = new Options($request);
    }

    public function response(): array
    {
        $query = clone $this->query;

        $total = $this->db->count($query);
        $filteredTotal = $this->db->filteredCount($query);

        $params = $this->filter($query);
        $this->order($query);
        $this->limit($query);

        $data = $this->db->execute($query, $params);

        return [
            'draw'            => $this->options->draw(),
            'recordsTotal'    => (int)$total,
            'recordsFiltered' => (int)$filteredTotal,
            'data'            => $data,
        ];
    }

    private function params(Query $query): array
    {
        
    }

    private function limit(Query $query): void
    {
        $query->limit($this->options->start(), $this->options->length());
    }

    private function order(Query $query): void
    {
        $columns = $this->options->columns();
        $fields = $query->getFields();

        foreach ($this->options->order() as $order) {
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

    private function filter(Query $query): array
    {
        $globalSearch = $this->options->searchValue();
        $fields = $query->getFields();
        $params = [];
        $globalWhere = [];
        $columnWhere = [];
        
        foreach ($this->options->columns() as $column) {
            if ($column['searchable'] == 'true') {
                $field = array_search($column['data'], $fields);
                
                if ($field) {
                    if ($globalSearch) {
                        $key = ':binding_'.count($params);
                        $params[$key] = "%$globalSearch%";
                        $globalWhere[] = "$field LIKE $key";
                    }

                    $columnSearch = $column['search']['value'];
                    
                    if ($columnSearch) {
                        $key = ':binding_' . count($params);
                        $params[$key] = "%$columnSearch%";
                        $columnWhere[] = "$field LIKE $key";
                    }
                }
            }
        }

        // $query->where()
    }
}
