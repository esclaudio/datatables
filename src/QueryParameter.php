<?php

namespace Esclaudio\Datatables;

class QueryParameter
{
    /**
     * Key
     *
     * @var string
     */
    public $key;

    /**
     * Field
     *
     * @var string
     */
    public $field;

    /**
     * Value
     *
     * @var string
     */
    public $value;

    public function __construct(string $key, string $field, string $value)
    {
        $this->key = $key;
        $this->field = $field;
        $this->value = $value;
    }
}