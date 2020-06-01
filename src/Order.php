<?php

namespace Esclaudio\Datatables;

class Order
{
    /**
     * Column
     *
     * @var string
     */
    protected $column;

    /**
     * Type
     *
     * @var string
     */
    protected $type;

    public function __construct(string $column, string $type)
    {
        $this->column = $column;
        $this->type = $type;
    }

    public function column(): string
    {
        return $this->column;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isAsc(): bool
    {
        return $this->type === 'asc';
    }

    public function isDesc(): bool
    {
        return $this->type === 'desc';
    }
}