<?php
namespace Datatables;

/*
 * Helper functions for building a DataTables server-side processing SQL query
 *
 * The static functions in this class are just helper functions to help build
 * the SQL used in the DataTables demo server-side processing scripts. These
 * functions obviously do not represent all that can be done with server-side
 * processing, they are intentionally simple to show how it works. More complex
 * server-side processing operations will likely require a custom script.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */

class SSP
{
    /**
     * Create the data output array for the DataTables rows
     *
     *  @param  array $columns Column information array
     *  @param  array $data    Data from the SQL get
     *  @return array          Formatted data in a row based format
     */
    private static function data_output(array $columns, array $data)
    {
        $out = [];

        for ($i=0, $ien=count($data) ; $i<$ien ; $i++) {
            $row = [];

            for ($j=0, $jen=count($columns) ; $j<$jen ; $j++) {
                $column = $columns[$j];

                // Is there a formatter?
                if (isset($column['formatter'])) {
                    $row[ $column['dt'] ] = $column['formatter']($data[$i][ $column['dt'] ], $data[$i]);
                } else {
                    $row[ $column['dt'] ] = $data[$i][ $columns[$j]['dt'] ];
                }
            }

            $out[] = $row;
        }

        return $out;
    }


    /**
     * Paging
     *
     * Construct the LIMIT clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL limit clause
     */
    private static function limit(array $request, array $columns)
    {
        if (isset($request['start']) && $request['length'] != -1) {
            return "LIMIT " . intval($request['start']) . ", " . intval($request['length']);
        }

        return '';
    }


    /**
     * Ordering
     *
     * Construct the ORDER BY clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL order by clause
     */
    private static function order(array $request, array $columns)
    {
        if (isset($request['order']) && count($request['order'])) {
            $orderBy = [];
            $dtColumns = self::pluck($columns, 'dt');

            for ($i=0, $ien=count($request['order']) ; $i<$ien ; $i++) {
                // Convert the column index into the column data property
                $columnIdx = intval($request['order'][$i]['column']);
                $requestColumn = $request['columns'][$columnIdx];

                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $columns[ $columnIdx ];

                if ($requestColumn['orderable'] == 'true') {
                    $dir = $request['order'][$i]['dir'] === 'asc' ?
                        'ASC' :
                        'DESC';

                    $orderBy[] = $column['db'].' '.$dir;
                }
            }

            return 'ORDER BY '.implode(', ', $orderBy);
        }

        return '';
    }


    /**
     * Searching / Filtering
     *
     * Construct the WHERE clause for server-side processing SQL query.
     *
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here performance on large
     * databases would be very poor
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @param  array $bindings Array of values for PDO bindings, used in the
     *    execute() function
     *  @return string SQL where clause
     */
    private static function filter(array $request, array $columns, array &$bindings)
    {
        $globalSearch = [];
        $columnSearch = [];
        $dtColumns = self::pluck($columns, 'dt');

        if (isset($request['search']) && $request['search']['value'] != '') {
            $str = $request['search']['value'];

            for ($i=0, $ien=count($request['columns']) ; $i<$ien ; $i++) {
                $requestColumn = $request['columns'][$i];
                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $columns[ $columnIdx ];

                if ($requestColumn['searchable'] == 'true') {
                    $binding = self::bind($bindings, '%'.$str.'%', \PDO::PARAM_STR);
                    $globalSearch[] = $column['db']." LIKE ".$binding;
                }
            }
        }

        // Individual column filtering
        for ($i=0, $ien=count($request['columns']) ; $i<$ien ; $i++) {
            $requestColumn = $request['columns'][$i];
            $columnIdx = array_search($requestColumn['data'], $dtColumns);
            $column = $columns[ $columnIdx ];

            $str = $requestColumn['search']['value'];

            if ($requestColumn['searchable'] == 'true' && $str != '') {
                $binding = self::bind($bindings, '%'.$str.'%', \PDO::PARAM_STR);
                $columnSearch[] = $column['db']." LIKE ".$binding;
            }
        }

        // Combine the filters into a single string
        $where = '';

        if (count($globalSearch)) {
            $where = '('.implode(' OR ', $globalSearch).')';
        }

        if (count($columnSearch)) {
            $where = $where === '' ?
                implode(' AND ', $columnSearch) :
                $where .' AND '. implode(' AND ', $columnSearch);
        }

        return $where;
    }


    /**
     * Perform the SQL queries needed for an server-side processing requested,
     * utilising the helper functions of this class, limit(), order() and
     * filter() among others. The returned array is ready to be encoded as JSON
     * in response to an SSP request, or can be modified if needed before
     * sending back to the client.
     *
     * * 'Result condition' - This is applied to the result set, but not the
     *   overall paging information query - i.e. it will not effect the number
     *   of records that a user sees they can have access to. This should be
     *   used when you want apply a filtering condition that the user has sent.
     * * 'All condition' - This is applied to all queries that are made and
     *   reduces the number of records that the user can access. This should be
     *   used in conditions where you don't want the user to ever have access to
     *   particular records (for example, restricting by a login id).
     *
     *  @param  PDO $db PDO connection
     *  @param  string $table SQL table to query
     *  @param  array $columns Column information array
     *  @param  string $whereResult WHERE condition to apply to the result set
     *  @param  string $whereAll WHERE condition to apply to all queries
     *  @param  array $request Data sent to server by DataTables
     *  @return array Server-side processing response array
     */
    public static function for(\PDO $db, $table, array $columns, $whereResult, $whereAll, array $request)
    {
        $bindings = [];

        // Build the SQL query string from the request
        $limit = self::limit($request, $columns);
        $order = self::order($request, $columns);
        $where = self::filter($request, $columns, $bindings);

        if ($whereResult) {
            $where .= $where? " AND $whereResult": $whereResult;
        }

        if ($whereAll) {
            $where .= $where? " AND $whereAll": $whereAll;
        }

        if ($where) {
            $where = "WHERE $where";
        }

        $fields = array_map(function ($column) {
            return $column['db'] . ' AS ' . $column['dt'] ?: $column['db'];
        }, $columns);

        $sql = "
			SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $fields) . "
			FROM $table
			$where
			$order
			$limit
		";

        // Main query to actually get the data
        $data = self::execute($db, $bindings, $sql);

        // Data set length after filtering
        $recordsFiltered = $db->query("SELECT FOUND_ROWS()")->fetchColumn();

        // Total data set length
        $recordsTotal = $db->query("SELECT COUNT(*) FROM $table" . ($whereAll? " WHERE $whereAll": ""))->fetchColumn();

        return array(
            "draw"            => (int)$request['draw'],
            "recordsTotal"    => (int)$recordsTotal,
            "recordsFiltered" => (int)$recordsFiltered,
            "data"            => self::data_output($columns, $data)
        );
    }


    /**
     * Execute an SQL query on the database
     *
     * @param  resource $db  Database handler
     * @param  array    $bindings Array of PDO binding values from bind() to be
     *   used for safely escaping strings. Note that this can be given as the
     *   SQL query string if no bindings are required.
     * @param  string   $sql SQL query to execute.
     * @return array         Result from the query (all rows)
     */
    private static function execute($db, array $bindings, $sql)
    {
        $stmt = $db->prepare($sql);

        // Bind parameters
        foreach ($bindings as $binding) {
            $stmt->bindValue($binding['key'], $binding['val'], $binding['type']);
        }

        // Execute
        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            echo json_encode(array(
                "error" => "An SQL error occurred: ".$e->getMessage()
            ));

            exit(0);
        }

        // Return all
        return $stmt->fetchAll();
    }


    /**
     * Create a PDO binding key which can be used for escaping variables safely
     * when executing a query with execute()
     *
     * @param  array &$a    Array of bindings
     * @param  *      $val  Value to bind
     * @param  int    $type PDO field type
     * @return string       Bound key to be used in the SQL where this parameter
     *   would be used.
     */
    private static function bind(array &$a, $val, $type)
    {
        $key = ':binding_'.count($a);

        $a[] = array(
            'key' => $key,
            'val' => $val,
            'type' => $type
        );

        return $key;
    }


    /**
     * Pull a particular property from each assoc. array in a numeric array,
     * returning and array of the property values from each item.
     *
     *  @param  array  $a    Array to get data from
     *  @param  string $prop Property to read
     *  @return array        Array of property values
     */
    private static function pluck(array $array, $prop)
    {
        $out = [];

        foreach ($array as $a) {
            $out[] = $a[$prop];
        }

        return $out;
    }
}
