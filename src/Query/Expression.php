<?php

namespace Esclaudio\Datatables\Query;

class Expression
{
    /**
     * Value
     *
     * @var string
     */
    protected $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
