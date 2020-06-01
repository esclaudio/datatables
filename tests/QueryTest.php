<?php

use PHPUnit\Framework\TestCase;
use Esclaudio\Datatables\Translator\PostgresqlTranslator;
use Esclaudio\Datatables\Translator\MysqlTranslator;
use Esclaudio\Datatables\Query\Builder;

final class QueryTest extends TestCase
{
    /** @test */
    public function empty_query(): void
    {
        $this->assertEquals('', (string)(new Builder));
    }

    /** @test */
    public function simple_query(): void
    {
        $query = $this->getQuery()->from('test');
        
        $this->assertEquals('select * from "test"', (string)$query);
    }

    /** @test */
    public function select(): void
    {
        $query = $this->getQuery()
            ->from('test')
            ->select(['id', 'name']);
        
        $this->assertEquals('select "id", "name" from "test"', (string)$query);

        $query = $this->getQuery()
            ->from('test')
            ->select('id, name');
        
        $this->assertEquals('select "id", "name" from "test"', (string)$query);

        $query = $this->getQuery()
            ->from('test as t')
            ->select('t.id as id, t.name as name');
        
        $this->assertEquals('select "t"."id" as "id", "t"."name" as "name" from "test" as "t"', (string)$query);

        $query = $this->getQuery()
            ->from('test')
            ->select(['id', $query->raw('count(*)')]);

        $this->assertEquals('select "id", count(*) from "test"', (string)$query);

        $query = $this->getQuery()
            ->from('test')
            ->selectRaw('count(*)');

        $this->assertEquals('select count(*) from "test"', (string)$query);
    }

    /** @test */
    public function where(): void
    {
        $query = $this->getQuery()
            ->from('test')
            ->where('id', 1);

        $this->assertEquals('select * from "test" where "id" = ?', (string)$query);
        $this->assertEquals([1], $query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->orWhere('id', 1);

        $this->assertEquals('select * from "test" where "id" = ?', (string)$query);
        $this->assertEquals([1], $query->getBindings());
        
        $query = $this->getQuery()
            ->from('test')
            ->where('name', 'like', '%claudio%')
            ->orWhere('age', '>', 20);

        $this->assertEquals('select * from "test" where "name" like ? or "age" > ?', (string)$query);
        $this->assertEquals(['%claudio%', 20], $query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->whereIn('id', [1, 2, 3]);

        $this->assertEquals('select * from "test" where "id" in (?, ?, ?)', (string)$query);
        $this->assertEquals([1, 2, 3], $query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->whereNotIn('id', [1, 2, 3]);

        $this->assertEquals('select * from "test" where "id" not in (?, ?, ?)', (string)$query);
        $this->assertEquals([1, 2, 3], $query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->whereNull('name');

        $this->assertEquals('select * from "test" where "name" is null', (string)$query);
        $this->assertEmpty($query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->whereNotNull('name');

        $this->assertEquals('select * from "test" where "name" is not null', (string)$query);
        $this->assertEmpty($query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->where('id', '>', 10)
            ->whereNotNull('age')
            ->where(function (Builder $query) {
                $query->where('firstname', 'like', '%a%')
                    ->orWhere('lastname', 'like', '%a%');
            })
            ->orWhere('created_at', '>', '2020-01-01');

        $this->assertEquals('select * from "test" where "id" > ? and "age" is not null and ("firstname" like ? or "lastname" like ?) or "created_at" > ?', (string)$query);
        $this->assertEquals([10, '%a%', '%a%', '2020-01-01'], $query->getBindings());

        $query = $this->getQuery()
            ->from('test')
            ->where(function () {})
            ->where('id', 1);

        $this->assertEquals('select * from "test" where "id" = ?', (string)$query);
        $this->assertEquals([1], $query->getBindings());
    }

    /** @test */
    public function join(): void
    {
        $query = $this->getQuery()
            ->from('test as t')
            ->select([
                't.id',
                'e1.name as e1_name',
                'e2.name as e2_name',
                'e3.name as e3_name',
            ])
            ->join('example1 as e1', 't.example_1', '=', 'e1.id')
            ->leftJoin('example2 as e2', 't.example_2', '=', 'e2.id')
            ->rightJoin('example3 as e3', 't.example_3', '=', 'e3.id');
        
        $this->assertEquals('select "t"."id", "e1"."name" as "e1_name", "e2"."name" as "e2_name", "e3"."name" as "e3_name" from "test" as "t" inner join "example1" as "e1" on "t"."example_1" = "e1"."id" left join "example2" as "e2" on "t"."example_2" = "e2"."id" right join "example3" as "e3" on "t"."example_3" = "e3"."id"', (string)$query);
    }

    /** @test */
    public function order_by(): void
    {
        $query = $this->getQuery()
            ->from('test')
            ->orderBy('id')
            ->orderByDesc('name');
        
        $this->assertEquals('select * from "test" order by "id" asc, "name" desc', (string)$query);

        $query = $this->getQuery()
            ->from('test')
            ->orderBy(['id', 'name']);
        
        $this->assertEquals('select * from "test" order by "id" asc, "name" asc', (string)$query);
    }

    /** @test */
    public function group_by(): void
    {
        $query = $this->getQuery();

        $query->from('test')
            ->select(['year', $query->raw('sum(total) as total_year')])
            ->groupBy('year');
        
        $this->assertEquals('select "year", sum(total) as total_year from "test" group by "year"', (string)$query);

        $query = $this->getQuery();

        $query->from('test')
            ->select(['year', 'month', $query->raw('count(*)')])
            ->groupBy(['year', 'month']);
        
        $this->assertEquals('select "year", "month", count(*) from "test" group by "year", "month"', (string)$query);
    }

    /** @test */
    public function limit(): void
    {
        $query = $this->getQuery()
            ->from('test')
            ->limit(0);
        
        $this->assertEquals('select * from "test"', (string)$query);

        $query = $this->getQuery()
            ->from('test')
            ->limit(10, 2)
            ->limit(5);
        
        $this->assertEquals('select * from "test" limit 5', (string)$query);

        $query = $this->getQuery()
            ->from('test')
            ->limit(0, 5);
        
        $this->assertEquals('select * from "test" limit 0, 5', (string)$query);
    }

    /** @test */
    public function full_query(): void
    {
        $query = $this->getQuery()
            ->from('test as t')
            ->select([
                't.id',
                'e1.name as e1_name',
                'e2.name as e2_name',
                'e3.name as e3_name',
            ])
            ->join('example1 as e1', 't.example_1', '=', 'e1.id')
            ->leftJoin('example2 as e2', 't.example_2', '=', 'e2.id')
            ->rightJoin('example3 as e3', 't.example_3', '=', 'e3.id')
            ->orWhere('t.name', 'like', '%claudio%')
            ->orWhere('t.age', '20')
            ->orderBy('t.id')
            ->orderByDesc('t.name')
            ->limit(10, 5);

        $this->assertEquals('select "t"."id", "e1"."name" as "e1_name", "e2"."name" as "e2_name", "e3"."name" as "e3_name" from "test" as "t" inner join "example1" as "e1" on "t"."example_1" = "e1"."id" left join "example2" as "e2" on "t"."example_2" = "e2"."id" right join "example3" as "e3" on "t"."example_3" = "e3"."id" where "t"."name" like ? or "t"."age" = ? order by "t"."id" asc, "t"."name" desc limit 10, 5', (string)$query);
        $this->assertEquals(['%claudio%', 20], $query->getBindings());
    }

    private function getQuery(): Builder
    {
        return new Builder;
    }
}