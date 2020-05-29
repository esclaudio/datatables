<?php

use PHPUnit\Framework\TestCase;
use Esclaudio\Datatables\Query;

final class QueryTest extends TestCase
{
    /** @test */
    public function simple(): void
    {
        $query = new Query('test');
        
        $this->assertEquals('SELECT * FROM test', (string)$query);
    }

    /** @test */
    public function select(): void
    {
        $query = (new Query('test'))
            ->select(['id', 'name']);
        
        $this->assertEquals('SELECT id, name FROM test', (string)$query);
    }

    /** @test */
    public function alias(): void
    {
        $query = (new Query('test t'))
            ->select([
                't.id'   => 'test_id',
                't.name' => 'test_name',
            ]);
        
        $this->assertEquals('SELECT t.id AS test_id, t.name AS test_name FROM test t', (string)$query);
    }

    /** @test */
    public function where(): void
    {
        $query = (new Query('test'))
            ->where("name LIKE '%:name%'")
            ->where('age > 20');
        
        $this->assertEquals("SELECT * FROM test WHERE name LIKE '%:name%' AND age > 20", (string)$query);
    }

    /** @test */
    public function join(): void
    {
        $query = (new Query('test t'))
            ->select([
                't.id',
                'e1.name' => 'e1_name',
                'e2.name' => 'e2_name',
                'e3.name' => 'e3_name',
            ])
            ->join('example1 e1', 't.example_1 = e1.id')
            ->leftJoin('example2 e2', 't.example_2 = e2.id')
            ->rightJoin('example3 e3', 't.example_3 = e3.id');
        
        $this->assertEquals("SELECT t.id, e1.name AS e1_name, e2.name AS e2_name, e3.name AS e3_name FROM test t INNER JOIN example1 e1 ON t.example_1 = e1.id LEFT JOIN example2 e2 ON t.example_2 = e2.id RIGHT JOIN example3 e3 ON t.example_3 = e3.id", (string)$query);
    }

    /** @test */
    public function order(): void
    {
        $query = (new Query('test'))
            ->order('id')
            ->orderDesc('name');
        
        $this->assertEquals("SELECT * FROM test ORDER BY id, name DESC", (string)$query);
    }

    /** @test */
    public function limit(): void
    {
        $query = (new Query('test'))->limit(10)->limit(5);
        
        $this->assertEquals("SELECT * FROM test LIMIT 5", (string)$query);

        $query = (new Query('test'))->limit(10, 5);
        
        $this->assertEquals("SELECT * FROM test LIMIT 10, 5", (string)$query);
    }

    /** @test */
    public function complete(): void
    {
        $query = (new Query('test t'))
            ->select([
                't.id',
                'e1.name' => 'e1_name',
                'e2.name' => 'e2_name',
                'e3.name' => 'e3_name',
            ])
            ->join('example1 e1', 't.example_1 = e1.id')
            ->leftJoin('example2 e2', 't.example_2 = e2.id')
            ->rightJoin('example3 e3', 't.example_3 = e3.id')
            ->where("t.name LIKE '%:name%'")
            ->where('t.age > 20')
            ->order('t.id')
            ->orderDesc('t.name')
            ->limit(10, 5);

        $sql = "SELECT t.id, e1.name AS e1_name, e2.name AS e2_name, e3.name AS e3_name FROM test t INNER JOIN example1 e1 ON t.example_1 = e1.id LEFT JOIN example2 e2 ON t.example_2 = e2.id RIGHT JOIN example3 e3 ON t.example_3 = e3.id WHERE t.name LIKE '%:name%' AND t.age > 20 ORDER BY t.id, t.name DESC LIMIT 10, 5";

        $this->assertEquals($sql, (string)$query);
    }
    
    /** @test */
    public function count_sql(): void
    {
        $query = (new Query('test'))->getCountSql();

        $this->assertEquals('SELECT COUNT(*) FROM test', $query);
    }

    /** @test */
    public function filtered_count_sql(): void
    {
        $query = (new Query('test'))
            ->where("name LIKE '%:name%'")
            ->where('age > 20')
            ->getFilteredCountSql();

        $this->assertEquals("SELECT COUNT(*) FROM test WHERE name LIKE '%:name%' AND age > 20", $query);
    }
}