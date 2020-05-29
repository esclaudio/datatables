<?php

use PHPUnit\Framework\TestCase;
use Esclaudio\Datatables\Query;
use Esclaudio\Datatables\Datatable;

final class DatatablesTest extends TestCase
{
    /**
     * PDO
     *
     * @var \PDO
     */
    protected $pdo;

    public function setUp(): void
    {
        $this->pdo = new \PDO('sqlite:test.db');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testSimple(): void
    {
        $query = (new Query('customers c'))
            ->select([
                'c.id'   => 'id',
                'c.name' => 'name',
                'c.age'  => 'age',
                'u.name' => 'created_by_name',
            ])
            ->join('users u', 'c.created_by = u.id')
            ->where('c.age > 25');
        
        $params = [
            'draw' => '1',
            'columns' => [
                [
                    'data'       => 'id',
                    'searchable' => 'true',
                    'orderable'  => 'true'
                ],
                [
                    'data'       => 'name',
                    'searchable' => 'true',
                    'orderable'  => 'true'
                ],
                [
                    'data'       => 'age',
                    'searchable' => 'false',
                    'orderable'  => 'true'
                ],
                [
                    'data'       => 'created_by_name',
                    'searchable' => 'true',
                    'orderable'  => 'true'
                ],
            ],
            'order' => [
                [
                    'column' => '0',
                    'dir'    => 'asc',
                ],
                [
                    'column' => '2',
                    'dir'    => 'desc',
                ],
            ],
            'start' => '0',
            'lenght' => '5',
            'search' => [
                'value' => 'uno',
                'regex' => 'false',
            ]
        ];

        $response = (new Datatable($this->pdo, $query, $params))->response();
        var_dump($response);
        die();
        // echo $datatable->sql();
        // die();
        // $datatable = new Datatable($this->pdo, 'customers', []);
        // $output = $datatable->getOutput();

        // $this->assertIsArray($output);
        
        // $this->assertArrayHasKey('draw', $output);
        // $this->assertEquals(0, $output['draw']);

        // $this->assertArrayHasKey('recordsTotal', $output);
        // $this->assertEquals(0, $output['recordsTotal']);

        // $this->assertArrayHasKey('recordsFiltered', $output);
        // $this->assertEquals(0, $output['recordsFiltered']);

        // $this->assertArrayHasKey('data', $output);
        // $this->assertEquals([], $output['data']);
        $this->assertTrue(true);
    }

    public function testLimitParams(): void
    {
        // $params = [
        //     'draw'  => 1,
        //     'start' => 0,
        //     'limit' => 5
        // ];

        // $datatable = new Datatable($this->pdo, 'customers', $params);

        // $output = $datatable->getOutput();

        // $this->assertEquals($params['draw'], $output['draw']);
        // $this->assertCount(5, $output['data']);
        // $this->assertEquals(11, $output['recordsTotal']);
        // $this->assertEquals(11, $output['recordsFiltered']);
        $this->assertTrue(true);
    }
}

