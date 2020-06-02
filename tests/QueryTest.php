<?php

namespace Esclaudio\Datatables\Tests;

use PHPUnit\Framework\TestCase;
use Esclaudio\Datatables\Query\Grammars\SQLiteGrammar;
use Esclaudio\Datatables\Query\Grammars\PostgresGrammar;
use Esclaudio\Datatables\Query\Grammars\MySqlGrammar;
use Esclaudio\Datatables\Query\Grammars\Grammar;
use Esclaudio\Datatables\Query\Builder;

abstract class QueryTest extends TestCase
{
    /**
     * Grammars
     *
     * @var array
     */
    protected $grammars = [
        'sqlite' => SQLiteGrammar::class,
        'mysql'  => MySqlGrammar::class,
        'pgsql'  => PostgresGrammar::class,
    ];

    /**
     * Query
     *
     * @var \Esclaudio\Datatables\Query\Builder
     */
    protected $query;

    /**
     * Driver
     *
     * @var string
     */
    protected $driver = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (array_key_exists($this->driver, $this->grammars)) {
            $grammar = new $this->grammars[$this->driver];
        } else {
            $grammar = new Grammar;
        }

        $this->query = new Builder($grammar);
    }

    /** @test */
    public function empty_query(): void
    {
        $this->assertSameQuery('select 0', $this->query);
    }

    /** @test */
    public function basic_query(): void
    {
        $this->query->from('test');

        $expect = <<<SQL
            select * from <<test>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function select_with_array(): void
    {
        $this->query
            ->from('test')
            ->select(['id', 'name']);

        $expect = <<<SQL
            select
                <<id>>, <<name>>
            from
                <<test>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function select_with_string(): void
    {
        $this->query
            ->from('test')
            ->select('id, name');

        $expect = <<<SQL
            select <<id>>, <<name>>
            from <<test>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @text */
    public function select_with_string_alias(): void
    {
        $this->query
            ->from('test as t')
            ->select('t.id as id, t.name as name');

        $expect = <<<SQL
            select
                <<t>>.<<id>> as <<id>>,
                <<t>>.<<name>> as <<name>>
            from
                <<test>> as <<t>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function select_with_expression(): void
    {
        $this->query
            ->from('test')
            ->select(['id', $this->query->raw('count(*)')]);

        $expect = <<<SQL
            select
                <<id>>, count(*)
            from
                <<test>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function select_raw(): void
    {
        $this->query
            ->from('test')
            ->selectRaw('count(*)');

        $expect = <<<SQL
            select count(*)
            from <<test>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function basic_where(): void
    {
        $this->query
            ->from('test')
            ->where('id', 1);

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<id>> = ?
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals([1], $this->query->getBindings());
    }

    /** @test */
    public function where_or(): void
    {
        $this->query
            ->from('test')
            ->orWhere('id', 1);

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<id>> = ?
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals([1], $this->query->getBindings());
    }

    /** @test */
    public function where_and_or(): void
    {
        $this->query
            ->from('test')
            ->where('name', 'like', '%claudio%')
            ->orWhere('age', '>', 20);

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<name>> like ?
            or
                <<age>> > ?
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals(['%claudio%', 20], $this->query->getBindings());
    }

    /** @test */
    public function where_in(): void
    {
        $this->query
            ->from('test')
            ->whereIn('id', [1, 2, 3]);

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<id>> in (?, ?, ?)
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals([1, 2, 3], $this->query->getBindings());
    }

    /** @test */
    public function where_not_in(): void
    {
        $this->query
            ->from('test')
            ->whereNotIn('id', [1, 2, 3]);

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<id>> not in (?, ?, ?)
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals([1, 2, 3], $this->query->getBindings());
    }

    /** @test */
    public function where_null(): void
    {
        $this->query
            ->from('test')
            ->whereNull('name');

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<name>> is null
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEmpty($this->query->getBindings());
    }

    /** @test */
    public function where_not_null(): void
    {
        $this->query
            ->from('test')
            ->whereNotNull('name');

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<name>> is not null
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEmpty($this->query->getBindings());
    }

    /** @test */
    public function where_with_function(): void
    {
        $this->query
            ->from('test')
            ->where('id', '>', 10)
            ->whereNotNull('age')
            ->where(function (Builder $query) {
                $query->where('firstname', 'like', '%a%')
                    ->orWhere('lastname', 'like', '%a%');
            })
            ->orWhere('created_at', '>', '2020-01-01');

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<id>> > ?
            and
                <<age>> is not null
            and
                (<<firstname>> like ? or <<lastname>> like ?)
            or
                <<created_at>> > ?
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals([10, '%a%', '%a%', '2020-01-01'], $this->query->getBindings());
    }

    /** @test */
    public function where_with_empty_function(): void
    {
        $this->query
            ->from('test')
            ->where(function () {})
            ->where('id', 1);

        $expect = <<<SQL
            select *
            from
                <<test>>
            where
                <<id>> = ?
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals([1], $this->query->getBindings());
    }

    /** @test */
    public function join(): void
    {
        $this->query
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

        $expect = <<<SQL
            select
                <<t>>.<<id>>,
                <<e1>>.<<name>> as <<e1_name>>,
                <<e2>>.<<name>> as <<e2_name>>,
                <<e3>>.<<name>> as <<e3_name>>
            from
                <<test>> as <<t>>
            inner join
                <<example1>> as <<e1>> on <<t>>.<<example_1>> = <<e1>>.<<id>>
            left join
                <<example2>> as <<e2>> on <<t>>.<<example_2>> = <<e2>>.<<id>>
            right join
                <<example3>> as <<e3>> on <<t>>.<<example_3>> = <<e3>>.<<id>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function order_by(): void
    {
        $this->query
            ->from('test')
            ->orderBy('id')
            ->orderByDesc('name');

        $expect = <<<SQL
            select *
            from
                <<test>>
            order by
                <<id>> asc,
                <<name>> desc
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function order_by_array(): void
    {
        $this->query
            ->from('test')
            ->orderBy(['id', 'name']);

        $expect = <<<SQL
            select *
            from
                <<test>>
            order by
                <<id>> asc,
                <<name>> asc
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function group_by(): void
    {
        $this->query->from('test')
            ->select(['year', $this->query->raw('sum(total) as total_year')])
            ->groupBy('year');

        $expect = <<<SQL
            select
                <<year>>,
                sum(total) as total_year
            from
                <<test>>
            group by
                <<year>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function group_by_array(): void
    {
        $this->query->from('test')
            ->select(['year', 'month', $this->query->raw('count(*)')])
            ->groupBy(['year', 'month']);

        $expect = <<<SQL
            select
                <<year>>,
                <<month>>,
                count(*)
            from
                <<test>>
            group by
                <<year>>,
                <<month>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function limit(): void
    {
        $this->query
            ->from('test')
            ->limit(10, 2)
            ->limit(5);

        $expect = <<<SQL
            select *
            from <<test>>
            limit 5
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function limit_zero(): void
    {
        $this->query
            ->from('test')
            ->limit(0);

        $expect = <<<SQL
            select *
            from <<test>>
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function limit_with_length(): void
    {
        $this->query
            ->from('test')
            ->limit(0, 5);

        $expect = <<<SQL
            select *
            from <<test>>
            limit 0, 5
SQL;

        $this->assertSameQuery($expect, $this->query);
    }

    /** @test */
    public function full_query(): void
    {
        $this->query
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

        $expect = <<<SQL
            select
                <<t>>.<<id>>,
                <<e1>>.<<name>> as <<e1_name>>,
                <<e2>>.<<name>> as <<e2_name>>,
                <<e3>>.<<name>> as <<e3_name>>
            from
                <<test>> as <<t>>
            inner join
                <<example1>> as <<e1>> on <<t>>.<<example_1>> = <<e1>>.<<id>>
            left join
                <<example2>> as <<e2>> on <<t>>.<<example_2>> = <<e2>>.<<id>>
            right join
                <<example3>> as <<e3>> on <<t>>.<<example_3>> = <<e3>>.<<id>>
            where
                <<t>>.<<name>> like ?
            or
                <<t>>.<<age>> = ?
            order by
                <<t>>.<<id>> asc,
                <<t>>.<<name>> desc
            limit
                10, 5
SQL;

        $this->assertSameQuery($expect, $this->query);
        $this->assertEquals(['%claudio%', 20], $this->query->getBindings());
    }

    protected function assertSameQuery($expect, $actual)
    {
        // remove excess of whitespace
        $expect = trim(preg_replace('/\s+/', ' ', (string)$expect));
        $actual = trim(preg_replace('/\s+/', ' ', (string)$actual));

        // normalize line endings
        $expect = preg_replace('/\r\n|\n|\r/', PHP_EOL, $expect);
        $actual = preg_replace('/\r\n|\n|\r/', PHP_EOL, $actual);

        $expect = $this->requoteIdentifiers($expect);

        $this->assertSame($expect, $actual);
    }

    protected function requoteIdentifiers(string $string): string
    {
        $grammar = $this->query->getGrammar();

        $string = str_replace('<<', $grammar->getQuotePrefix(), $string);
        $string = str_replace('>>', $grammar->getQuoteSuffix(), $string);

        return $string;
    }
}
