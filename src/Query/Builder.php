<?php

namespace Esclaudio\Datatables\Query;

use Esclaudio\Datatables\Query\Translator\TranslatorInterface;
use Esclaudio\Datatables\Query\Translator\AnsiTranslator;

class Builder
{
    /**
     * Translator
     *
     * @var \Esclaudio\Datatables\Query\Translator\TranslatorInterface
     */
    protected $translator;

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
    protected $wheres = [];

    /**
     * Bindings
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Orders
     *
     * @var array
     */
    protected $orders = [];

    /**
     * Groups
     *
     * @var array
     */
    protected $groups = [];

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

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator ?? new AnsiTranslator;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getStart(): int
    {
        return (int)$this->start;
    }

    public function getLength(): int
    {
        return (int)$this->length;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
    
    public function resetWhere(): self
    {
        $this->wheres = [];
        $this->bindings = [];
        return $this;
    }

    public function resetOrder(): self
    {
        $this->orders = [];
        return $this;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        return $this;
    }

    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param string|array $fields
     * @return self
     */
    public function select($fields): self
    {
        $this->fields = [];

        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }

        foreach ($fields as $field) {
            if ($field instanceof Expression) {
                $this->fields[] = $field;
            } else {
                $field = trim($field);

                if (false !== stripos($field, ' as ')) {
                    list($field, $key) = preg_split('/\s+as\s+/i', $field);
                    $this->fields[$key] = $field;
                } else {
                    $this->fields[] = $field;
                }
            }
        }

        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->fields = [new Expression($expression)];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): self
    {
        $type = strtolower($type);

        if ( ! in_array($type, ['inner', 'left', 'right'])) {
            throw new \Exception('Invalid join type "' . $type . '"');
        }

        if ( ! in_array($operator, ['=', '<', '<=', '>', '>=', '!='])) {
            throw new \Exception('Invalid join operator "' . $operator . '"');
        }

        $this->joins[] = [$type, $table, $first, $operator, $second];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return$this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * @param string|\Closure $column
     * @param string|array $operator
     * @param string|array|null $value
     * @param string $type
     * @return self
     */
    public function where($column, $operator = null, $value = null, string $type = 'and'): self
    {
        $type = strtolower($type);

        if ( ! in_array($type, ['and', 'or'])) {
            throw new \Exception('Invalid where type "'.$type.'"');
        }

        if (empty($this->wheres)) {
            $type = 'where';
        }

        if ($column instanceof \Closure) {
            call_user_func($column, $query = new Builder($this->translator));

            if ( ! empty($query->getWheres())) {
                $this->wheres[] = [$type, $query];
                $this->addBinding($query->getBindings());
            }

            return $this;
        }

        if (null === $value) {
            list($value, $operator) = [$operator, '='];
        }

        $this->wheres[] = [$type, $column, $operator, $value];

        if ( ! $value instanceof Expression) {
            $this->addBinding($value);
        }

        return $this;
    }

    public function orWhere($column, $param1 = null, $param2 = null): self
    {
        return $this->where($column, $param1, $param2, 'or');
    }

    public function whereIn($column, array $params): self
    {
        return $this->where($column, 'in', $params);
    }

    public function whereNotIn($column, array $params): self
    {
        return $this->where($column, 'not in', $params);
    }

    public function whereNull($column): self
    {
        return $this->where($column, 'is', $this->raw('null'));
    }

    public function whereNotNull($column): self
    {
        return $this->where($column, 'is not', $this->raw('null'));
    }
    
    /**
     * @param string|array $column
     * @param string $type
     * @return self
     */
    public function orderBy($column, string $type = 'asc'): self
    {
        $type = trim($type);

        if ( ! in_array($type, ['asc', 'desc'])) {
            throw new \Exception('Invalid order type "'.$type.'"');
        }

        if (is_array($column)) {
            foreach ($column as $col) {
                $this->orderBy($col, $type);
            }
        } else {
            $this->orders[] = [$type, $column];
        }

        return $this;
    }

    /**
     * @param string|array $column
     * @param string $type
     * @return self
     */
    public function orderByDesc($column): self
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * @param string|array $column
     * @param string $type
     * @return self
     */
    public function groupBy($column): self
    {
        if (is_array($column)) {
            foreach ($column as $col) {
                $this->groupBy($col);
            }
        } else {
            $this->groups[] = $column;
        }

        return $this;
    }

    public function limit(int $start, int $length = 0): self
    {
        $this->start = $start;
        $this->length = $length;

        return $this;
    }

    public function raw(string $expression): Expression
    {
        return new Expression($expression);
    }

    public function sql(): string
    {
        return $this->translator->translate($this);
    }

    protected function addBinding($value)
    {
        if (is_array($value)) {
            $this->bindings = array_values(array_merge($this->bindings, $value));
        } else {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->sql();
    }
}
