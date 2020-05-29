<?php

namespace Esclaudio\Datatables;

class Option
{
    /**
     * Request
     *
     * @var array
     */
    private $request;

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function draw(): int
    {
        return (int)($this->request['draw'] ?? 0);
    }

    public function start(): int
    {
        return (int)($this->request['start'] ?? 0);
    }

    public function length(): int
    {
        return (int)($this->request['length'] ?? 0);
    }

    public function searchValue(): string
    {
        return $this->request['search']['value'] ?? '';
    }

    public function order(): array
    {
        return (array)($this->request['order'] ?? []);
    }

    public function columns(): array
    {
        return (array)($this->request['columns'] ?? []);
    }
}