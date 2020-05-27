<?php

namespace Datatables;

class Datatable
{
    const LEFT_JOIN  = 'LEFT';
    const RIGHT_JOIN = 'RIGHT';
    const INNER_JOIN = 'INNER';

    /**
     * PDO
     *
     * @var \PDO
     */
    private $pdo;

    /**
     * Table name
     *
     * @var string
     */
    private $table;

    /**
     * Params
     *
     * @var array
     */
    private $params;

    /**
     * Columns
     *
     * @var array
     */
    private $columns = [];

    /**
     * Where all
     *
     * @var array
     */
    private $whereAll = [];

    /**
     * Where result
     *
     * @var array
     */
    private $whereResult = [];

    /**
     * Join
     *
     * @var array
     */
    private $joins = [];

    public function __construct(\PDO $pdo, string $table, array $params)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->params = $params;
    }

    public function addJoin(string $table, string $relation, string $type): self
    {
        $this->joins[] = "$type JOIN {$table} ON {$relation}";
        return $this;
    }

    public function addInnerJoin(string $table, string $relation): self
    {
        return $this->addJoin($table, $relation, self::INNER_JOIN);
    }

    public function addLeftJoin(string $table, string $relation): self
    {
        return $this->addJoin($table, $relation, self::LEFT_JOIN);
    }

    public function addRightJoin(string $table, string $relation): self
    {
        return $this->addJoin($table, $relation, self::RIGHT_JOIN);
    }

    // public function addWhereAll($where): self
    // {
    //     $this->whereAll[] = $where;
    //     return $this;
    // }

    // public function addWhereResult($where): self
    // {
    //     $this->whereResult[] = $where;
    //     return $this;
    // }

    public function addColumn(string $key, string $field = null, callable $formatter = null): self
    {
        $this->columns[] = [
            'dt'        => $key,
            'db'        => $field ?? $key,
            'formatter' => $formatter
        ];

        return $this;
    }

    public function getOutput(): array
    {
        return array(
            'draw'            => (int)$this->params['draw'],
            'recordsTotal'    => 0,
            'recordsFiltered' => 0,
            'data'            => []
        );
    }

    // private static function data_output(array $columns, array $data)
    // {
    //     $out = [];

    //     for ($i=0, $ien=count($data) ; $i<$ien ; $i++) {
    //         $row = [];

    //         for ($j=0, $jen=count($columns) ; $j<$jen ; $j++) {
    //             $column = $columns[$j];

    //             // Is there a formatter?
    //             if (isset($column['formatter'])) {
    //                 $row[ $column['dt'] ] = $column['formatter']($data[$i][ $column['dt'] ], $data[$i]);
    //             } else {
    //                 $row[ $column['dt'] ] = $data[$i][ $columns[$j]['dt'] ];
    //             }
    //         }

    //         $out[] = $row;
    //     }

    //     return $out;
    // }

    // private function limit(array $params): string
    // {
    //     if (isset($params['start']) && $params['length'] != -1) {
    //         return 'LIMIT ' . intval($params['start']) . ', ' . intval($params['length']);
    //     }

    //     return '';
    // }

    // private function order(array $params, array $columns): string
    // {
    //     if (isset($request['order']) && count($request['order'])) {
    //         $orderBy = [];
    //         $dtColumns = self::pluck($columns, 'dt');

    //         for ($i=0, $ien=count($request['order']) ; $i<$ien ; $i++) {
    //             // Convert the column index into the column data property
    //             $columnIdx = intval($request['order'][$i]['column']);
    //             $requestColumn = $request['columns'][$columnIdx];

    //             $columnIdx = array_search($requestColumn['data'], $dtColumns);
    //             $column = $columns[ $columnIdx ];

    //             if ($requestColumn['orderable'] == 'true') {
    //                 $dir = $request['order'][$i]['dir'] === 'asc' ?
    //                     'ASC' :
    //                     'DESC';

    //                 $orderBy[] = $column['db'].' '.$dir;
    //             }
    //         }

    //         return 'ORDER BY '.implode(', ', $orderBy);
    //     }

    //     return '';
    // }

    // private static function filter(array $request, array $columns, array &$bindings)
    // {
    //     $globalSearch = [];
    //     $columnSearch = [];
    //     $dtColumns = self::pluck($columns, 'dt');

    //     if (isset($request['search']) && $request['search']['value'] != '') {
    //         $str = $request['search']['value'];

    //         for ($i=0, $ien=count($request['columns']) ; $i<$ien ; $i++) {
    //             $requestColumn = $request['columns'][$i];
    //             $columnIdx = array_search($requestColumn['data'], $dtColumns);
    //             $column = $columns[ $columnIdx ];

    //             if ($requestColumn['searchable'] == 'true') {
    //                 $binding = self::bind($bindings, '%'.$str.'%', \PDO::PARAM_STR);
    //                 $globalSearch[] = $column['db']." LIKE ".$binding;
    //             }
    //         }
    //     }

    //     // Individual column filtering
    //     for ($i=0, $ien=count($request['columns']) ; $i<$ien ; $i++) {
    //         $requestColumn = $request['columns'][$i];
    //         $columnIdx = array_search($requestColumn['data'], $dtColumns);
    //         $column = $columns[ $columnIdx ];

    //         $str = $requestColumn['search']['value'];

    //         if ($requestColumn['searchable'] == 'true' && $str != '') {
    //             $binding = self::bind($bindings, '%'.$str.'%', \PDO::PARAM_STR);
    //             $columnSearch[] = $column['db']." LIKE ".$binding;
    //         }
    //     }

    //     // Combine the filters into a single string
    //     $where = '';

    //     if (count($globalSearch)) {
    //         $where = '('.implode(' OR ', $globalSearch).')';
    //     }

    //     if (count($columnSearch)) {
    //         $where = $where === '' ?
    //             implode(' AND ', $columnSearch) :
    //             $where .' AND '. implode(' AND ', $columnSearch);
    //     }

    //     return $where;
    // }

    // public static function for(\PDO $db, $table, array $columns, $whereResult, $whereAll, array $request)
    // {
    //     $bindings = [];

    //     // Build the SQL query string from the request
    //     $limit = self::limit($request, $columns);
    //     $order = self::order($request, $columns);
    //     $where = self::filter($request, $columns, $bindings);

    //     if ($whereResult) {
    //         $where .= $where? " AND $whereResult": $whereResult;
    //     }

    //     if ($whereAll) {
    //         $where .= $where? " AND $whereAll": $whereAll;
    //     }

    //     if ($where) {
    //         $where = "WHERE $where";
    //     }

    //     $fields = array_map(function ($column) {
    //         return $column['db'] . ' AS ' . $column['dt'] ?: $column['db'];
    //     }, $columns);

    //     $sql = "
	// 		SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $fields) . "
	// 		FROM $table
	// 		$where
	// 		$order
	// 		$limit
	// 	";

    //     // Main query to actually get the data
    //     $data = self::execute($db, $bindings, $sql);

    //     // Data set length after filtering
    //     $recordsFiltered = $db->query("SELECT FOUND_ROWS()")->fetchColumn();

    //     // Total data set length
    //     $recordsTotal = $db->query("SELECT COUNT(*) FROM $table" . ($whereAll? " WHERE $whereAll": ""))->fetchColumn();

    //     return array(
    //         "draw"            => (int)$request['draw'],
    //         "recordsTotal"    => (int)$recordsTotal,
    //         "recordsFiltered" => (int)$recordsFiltered,
    //         "data"            => self::data_output($columns, $data)
    //     );
    // }

    // private static function execute($db, array $bindings, $sql)
    // {
    //     $stmt = $db->prepare($sql);

    //     // Bind parameters
    //     foreach ($bindings as $binding) {
    //         $stmt->bindValue($binding['key'], $binding['val'], $binding['type']);
    //     }

    //     // Execute
    //     try {
    //         $stmt->execute();
    //     } catch (\PDOException $e) {
    //         echo json_encode(array(
    //             "error" => "An SQL error occurred: ".$e->getMessage()
    //         ));

    //         exit(0);
    //     }

    //     // Return all
    //     return $stmt->fetchAll();
    // }

    // private static function bind(array &$a, $val, $type)
    // {
    //     $key = ':binding_'.count($a);

    //     $a[] = array(
    //         'key' => $key,
    //         'val' => $val,
    //         'type' => $type
    //     );

    //     return $key;
    // }

    // private function pluck(array $array, $prop)
    // {
    //     $out = [];

    //     foreach ($array as $a) {
    //         $out[] = $a[$prop];
    //     }

    //     return $out;
    // }
}
