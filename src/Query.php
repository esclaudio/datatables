<?php

namespace Esclaudio\Datatables;

class Query
{
    /**
     * Table
     *
     * @var string
     */
    protected $table;

    /**
     * Fields
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Joins
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Where
     *
     * @var array
     */
    protected $where = [];

    /**
     * Parameters
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Order
     *
     * @var array
     */
    protected $order = [];

    /**
     * Start
     *
     * @var integer
     */
    protected $start = 0;

    /**
     * Length
     *
     * @var integer
     */
    protected $length = 0;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function join(string $table, string $relation): self
    {
        $this->joins[] = " INNER JOIN {$table} ON {$relation}";
        return $this;
    }

    public function leftJoin(string $table, string $relation): self
    {
        $this->joins[] = " LEFT JOIN {$table} ON {$relation}";
        return $this;
    }

    public function rightJoin(string $table, string $relation): self
    {
        $this->joins[] = " RIGHT JOIN {$table} ON {$relation}";
        return $this;
    }

    public function select(array $fields): self
    {
        foreach ($fields as $key => $field) {
            if (is_string($key)) {
                $this->fields[$key] = $field;
            } else {
                $this->fields[$field] = $field;
            }
        }
        return $this;
    }

    public function where(string $where): self
    {
        $this->where[] = $where;
        return $this;
    }

    public function setParameter(string $name, $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    public function order(string $order): self
    {
        $this->order[] = $order;
        return $this;
    }

    public function orderDesc(string $order, $direction = 'asc'): self
    {
        $this->order[] = "$order DESC";
        return $this;
    }

    public function limit(int $start, int $length = 0): self
    {
        $this->start = $start;
        $this->length = $length;
        return $this;
    }

    public function getSql(): string
    {
        return $this->getSelect()
            . $this->getFrom()
            . $this->getJoin()
            . $this->getWhere()
            . $this->getOrder()
            . $this->getLimit();
    }

    public function getCountSql(): string
    {
        return 'SELECT COUNT(*)' . $this->getFrom() . $this->getJoin();
    }

    public function getFilteredCountSql(): string
    {
        return 'SELECT COUNT(*)' . $this->getFrom() . $this->getJoin() . $this->getWhere();
    }

    protected function getSelect(): string
    {
        if ($this->fields) {
            $fields = [];
    
            foreach ($this->fields as $field => $alias) {
                if ($field === $alias) {
                    $fields[] = $field;
                } else {
                    $fields[] = "$field AS $alias";
                }
            }
        } else {
            $fields = ['*'];
        }

        return 'SELECT ' . implode(', ', $fields);
    }

    protected function getFrom(): string
    {
        return ' FROM ' . $this->table;
    }

    protected function getJoin(): string
    {
        return implode('', $this->joins);
    }

    protected function getWhere(): string
    {
        if ( ! $this->where) return '';
        
        return ' WHERE ' . implode(' AND ', $this->where);
    }

    protected function getOrder(): string
    {
        if ( ! $this->order) return '';
        
        return ' ORDER BY ' . implode(', ', $this->order);
    }

    protected function getLimit(): string
    {
        if ($this->start && $this->length) {
            return ' LIMIT ' . $this->start . ', ' . $this->length;
        }

        if ($this->start) {
            return ' LIMIT ' . $this->start;
        }

        return '';
    }

    public function __toString()
    {
        return $this->getSql();
    }
}
