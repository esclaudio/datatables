<?php

namespace Esclaudio\Datatables\Tests;

use Esclaudio\Datatables\Database\Connection;
use Esclaudio\Datatables\Datatables;
use Esclaudio\Datatables\Options;
use PHPUnit\Framework\TestCase;

final class DatatablesTest extends TestCase
{
    public const REQUEST_DRAW = 1;
    public const REQUEST_LENGTH = 5;
    public const REQUEST_COLUMN_ID = 0;
    public const REQUEST_COLUMN_NAME = 1;
    public const REQUEST_COLUMN_AGE = 2;
    public const REQUEST_COLUMN_CREATED_BY_NAME = 3;
    public const TOTAL_RECORDS = 11;

    protected $pdo;
    protected $request;

    public function setUp(): void
    {
        $this->pdo = new \PDO('sqlite:'.__DIR__.'/fixtures/test.db');
        $this->request = [
            'draw' => self::REQUEST_DRAW,
            'columns' => [
                [
                    'data' => 'id',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [],
                ],
                [
                    'data' => 'name',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [],
                ],
                [
                    'data' => 'age',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [],
                ],
                [
                    'data' => 'created_by_name',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [],
                ],
            ],
            'order' => [],
            'start' => '0',
            'length' => strval(self::REQUEST_LENGTH),
            'search' => [],
        ];
    }

    /** @test */
    public function empty_request_without_query(): void
    {
        $datatable = new Datatables(new Connection($this->pdo), new Options([]));
        $response = $datatable->toArray();

        $this->assertResponse(0, 0, 0, $response);

        $this->assertEquals([[0]], $response['data']);
    }

    /** @test */
    public function empty_request(): void
    {
        $this->request = [];

        $response = $this->datatable()->toArray();

        $this->assertResponse(0, self::TOTAL_RECORDS, self::TOTAL_RECORDS, $response);
        $this->assertCount(self::TOTAL_RECORDS, $response['data']);

        $first = reset($response['data']);

        $this->assertEquals(1, $first['id']);
    }

    /** @test */
    public function simple_request(): void
    {
        $response = $this->datatable()->toArray();

        $this->assertResponse(self::REQUEST_DRAW, self::TOTAL_RECORDS, self::TOTAL_RECORDS, $response);
        $this->assertLessThanOrEqual(self::REQUEST_LENGTH, count($response['data']));

        $first = reset($response['data']);

        $this->assertEquals(1, $first['id']);
    }

    /** @test */
    public function request_with_global_search(): void
    {
        $this->request['search']['value'] = 'f';

        $response = $this->datatable()->toArray();

        $this->assertResponse(self::REQUEST_DRAW, self::TOTAL_RECORDS, 2, $response);
        $this->assertLessThanOrEqual(self::REQUEST_LENGTH, count($response['data']));

        $first = reset($response['data']);

        $this->assertEquals(4, $first['id']);
    }

    /** @test */
    public function request_with_column_search(): void
    {
        $this->request['columns'][self::REQUEST_COLUMN_CREATED_BY_NAME]['search']['value'] = 'claudio';

        $response = $this->datatable()->toArray();

        $this->assertResponse(self::REQUEST_DRAW, self::TOTAL_RECORDS, 4, $response);
        $this->assertLessThanOrEqual(self::REQUEST_LENGTH, count($response['data']));

        $first = reset($response['data']);

        $this->assertEquals(2, $first['id']);
    }

    /** @test */
    public function add_column(): void
    {
        $response = $this->datatable()
            ->addColumn('action', function ($row) {
                return '<a href="/test/' . $row['id'] . '">Edit</a>';
            })
            ->toArray();

        $first = reset($response['data']);

        $this->assertEquals('<a href="/test/1">Edit</a>', $first['action']);
    }

    public function add_column_without_request(): void
    {
        $this->request = [];

        $response = $this->datatable()
            ->addColumn('action', function ($row) {
                return '<a href="/test/' . $row['id'] . '">Edit</a>';
            })
            ->toArray();

        $first = reset($response['data']);

        $this->assertEquals('<a href="/test/1">Edit</a>', $first['action']);
    }

    /** @test */
    public function add_id_column(): void
    {
        $response = $this->datatable()
            ->addRowId('id')
            ->toArray();

        $first = reset($response['data']);

        $this->assertArrayHasKey('DT_RowId', $first);
        $this->assertEquals('row_1', $first['DT_RowId']);
    }

    /** @test */
    public function edit_column(): void
    {
        $response = $this->datatable()
            ->editColumn('name', function ($row) {
                return strtoupper($row['name']);
            })
            ->toArray();

        $first = reset($response['data']);

        $this->assertEquals('ONE INC.', $first['name']);
    }

    public function edit_column_without_request(): void
    {
        $this->request = [];

        $response = $this->datatable()
            ->editColumn('name', function ($row) {
                return strtoupper($row['name']);
            })
            ->toArray();

        $first = reset($response['data']);

        $this->assertEquals('ONE INC.', $first['name']);
    }

    /** @test */
    public function hide_column(): void
    {
        $response = $this->datatable()
            ->hideColumn('id')
            ->editColumn('name', function ($row) {
                return "$row[name] ($row[id])";
            })
            ->toArray();

        $first = reset($response['data']);

        $this->assertArrayNotHasKey('id', $first);
        $this->assertEquals('One Inc. (1)', $first['name']);
    }

    public function hide_column_without_request(): void
    {
        $this->request = [];

        $response = $this->datatable()
            ->hideColumn('id')
            ->editColumn('name', function ($row) {
                return "$row[name] ($row[id])";
            })
            ->toArray();

        $first = reset($response['data']);

        $this->assertArrayNotHasKey('id', $first);
        $this->assertEquals('One Inc. (1)', $first['name']);
    }

    /** @test */
    public function request_with_order(): void
    {
        $this->request['order'] = [
            [
                'column' => self::REQUEST_COLUMN_AGE,
                'dir' => 'desc',
            ],
        ];

        $response = $this->datatable()->toArray();

        $first = reset($response['data']);

        $this->assertEquals(11, $first['id']);
    }

    /** @test */
    public function it_can_inner_join(): void
    {
        $query = (new Datatables(new Connection($this->pdo), new Options($this->request)))
            ->from('a')
            ->join('b', 'a.id', '=', 'b.a_id')
            ->getQuery();

        $this->assertSame('select * from "a" inner join "b" on "a"."id" = "b"."a_id" limit 0, 5', (string)$query);
    }

    /** @test */
    public function it_can_left_join(): void
    {
        $query = (new Datatables(new Connection($this->pdo), new Options($this->request)))
            ->from('a')
            ->leftJoin('b', 'a.id', '=', 'b.a_id')
            ->getQuery();

        $this->assertSame('select * from "a" left join "b" on "a"."id" = "b"."a_id" limit 0, 5', (string)$query);
    }

    /** @test */
    public function it_can_right_join(): void
    {
        $query = (new Datatables(new Connection($this->pdo), new Options($this->request)))
            ->from('a')
            ->rightJoin('b', 'a.id', '=', 'b.a_id')
            ->getQuery();

        $this->assertSame('select * from "a" right join "b" on "a"."id" = "b"."a_id" limit 0, 5', (string)$query);
    }

    public function assertResponse(int $draw, int $recordsTotal, int $recordsFiltered, array $response): void
    {
        $this->assertArrayHasKey('draw', $response);
        $this->assertEquals($draw, $response['draw']);

        $this->assertArrayHasKey('recordsTotal', $response);
        $this->assertEquals($recordsTotal, $response['recordsTotal']);

        $this->assertArrayHasKey('recordsFiltered', $response);
        $this->assertEquals($recordsFiltered, $response['recordsFiltered']);

        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    public function datatable(): Datatables
    {
        return (new Datatables(new Connection($this->pdo), new Options($this->request)))
            ->from('customers as c')
            ->join('users as u', 'c.created_by', '=', 'u.id')
            ->select('c.id as id, c.name as name, c.age as age, u.name as created_by_name');
    }
}
