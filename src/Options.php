<?php

namespace Esclaudio\Datatables;

class Options
{
    /**
     * Draw
     *
     * @var int
     */
    protected $draw;

    /**
     * Start
     *
     * @var int
     */
    protected $start;

    /**
     * Length
     *
     * @var int
     */
    protected $length;

    /**
     * Search value
     *
     * @var string|null
     */
    protected $searchValue;

    /**
     * Columns
     *
     * @var \Esclaudio\Datatables\Column[]
     */
    protected $columns = [];

    /**
     * Order
     *
     * @var \Esclaudio\Datatables\Order[]
     */
    protected $order = [];

    public function __construct(array $request)
    {
        $this->draw = (int)($request['draw'] ?? 0);
        $this->start = (int)($request['start'] ?? 0);
        $this->length = (int)($request['length'] ?? 0);
        $this->searchValue = $request['search']['value'] ?? null;

        $columns = (array)($request['columns'] ?? null);

        foreach ($columns as $column) {
            $this->columns[] = new Column(
                $column['data'],
                'true' === ($column['searchable'] ?? 'false'),
                'true' === ($column['orderable'] ?? 'false'),
                $column['search']['value'] ?? null
            );
        }

        $orders = (array)($request['order'] ?? null);

        foreach($orders as $order) {
            $column = $this->columns[$order['column']] ?? null;

            if ($column && $column->orderable()) {
                $this->order[] = new Order(
                    $column->name(),
                    strtolower($order['dir']) === 'desc'? 'desc': 'asc'
                );
            }
        }
    }

    public function draw(): int
    {
        return $this->draw;
    }

    public function start(): int
    {
        return $this->start;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function searchValue(): ?string
    {
        return $this->searchValue;
    }

    /**
     * @return \Esclaudio\Datatables\Order[]
     */
    public function order(): array
    {
        return $this->order;
    }

    /**
     * @return \Esclaudio\Datatables\Column[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return \Esclaudio\Datatables\Column[]
     */
    public function searchableColumns(): array
    {
        return array_filter($this->columns, function (Column $column) {
            return $column->searchable();
        });
    }
}
