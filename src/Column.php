<?php

namespace Esclaudio\Datatables;

class Column
{
    /**
     * Data
     *
     * @var string
     */
    public $name;

    /**
     * Searchable
     *
     * @var bool
     */
    public $searchable = true;

    /**
     * Orderable
     *
     * @var bool
     */
    public $orderable = true;

    /**
     * Search value
     *
     * @var null|string
     */
    public $searchValue = null;

    /**
     * Callback
     *
     * @var \Closure
     */
    public $callback = null;

    /**
     * Hidden
     *
     * @var boolean
     */
    public $hidden = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function value(array $row)
    {
        if ($this->callback) {
            return call_user_func($this->callback, $row);
        }

        return $row[$this->name] ?? '';
    }

    public function hide(): void
    {
        $this->hidden = true;
        $this->searchable = false;
        $this->orderable = false;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}