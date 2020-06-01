<?php

namespace Esclaudio\Datatables\Query\Translator;

use Esclaudio\Datatables\Query\Builder;

interface TranslatorInterface
{
    public function translate(Builder $query): string;
}