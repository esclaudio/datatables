<?php

namespace Esclaudio\Datatables\Query\Grammars;

class MysqlGrammar extends Grammar
{
    protected function wrapValue(string $value)
    {
        return '`'.str_replace('`', '``', $value).'`';
    }
}
