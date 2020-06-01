<?php

namespace Esclaudio\Datatables;

class Column
{
    /**
     * Data
     *
     * @var string
     */
    protected $name;

    /**
     * Searchable
     *
     * @var bool
     */
    protected $searchable;

    /**
     * Orderable
     *
     * @var bool
     */
    protected $orderable;

    /**
     * Search value
     *
     * @var null|string
     */
    protected $searchValue;

    public function __construct(string $name, bool $searchable = false, bool $orderable = false, string $searchValue = null)
    {
        $this->name = $name;
        $this->searchable = $searchable;
        $this->orderable = $orderable;
        $this->searchValue = $searchValue;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function searchable(): bool
    {
        return $this->searchable;
    }

    public function orderable(): bool
    {
        return $this->orderable;
    }

    public function searchValue(): ?string
    {
        return $this->searchValue;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}