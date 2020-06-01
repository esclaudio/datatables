<?php

namespace Esclaudio\Datatables\Query\Translator;

class MysqlTranslator extends AnsiTranslator
{
    protected $quotePrefix = '`';
    protected $suffixPrefix = '`';
}