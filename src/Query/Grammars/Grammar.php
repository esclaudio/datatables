<?php

namespace Esclaudio\Datatables\Query\Grammars;

use Esclaudio\Datatables\Query\Expression;
use Esclaudio\Datatables\Query\Builder;

class Grammar
{
    public function compile(Builder $query): string
    {
        if ( ! $query->getTable()) {
            return 'select 0';
        }

        return $this->compileSelect($query)
             . $this->compileFrom($query)
             . $this->compileJoin($query)
             . $this->compileWhere($query)
             . $this->compileGroupBy($query)
             . $this->compileOrderBy($query)
             . $this->compileLimit($query);
    }

    protected function compileSelect(Builder $query): string
    {
        $fields = $query->getFields();

        if ( ! $fields) return 'select *';

        $build = 'select ';

        foreach ($fields as $alias => $field) {
            if (is_int($alias)) {
                $build .= $this->wrap($field) . ', ';
            } else {
                $build .= $this->wrap($field) . ' as ' . $this->wrap($alias) . ', ';
            }
        }
        
        return substr($build, 0, -2);
    }

    protected function compileFrom(Builder $query): string
    {
        return ' from ' . $this->wrap($query->getTable());
    }

    protected function compileJoin(Builder $query): string
    {
        if ( ! $joins = $query->getJoins()) return '';

        return ' ' . implode(' ', array_map(function ($join) {
            return $join[0] . ' join ' . $this->wrap($join[1]) . ' on ' . $this->wrap($join[2]) . ' ' . $join[3] . ' ' . $this->wrap($join[4]);
        }, $joins));
    }

    protected function compileWhere(Builder $query): string
    {
        if ( ! $wheres = $query->getWheres()) return '';
        
        return ' ' . implode(' ', array_map(function ($where) {
            if ($where[1] instanceof Builder) {
                return $where[0] . ' (' . substr($this->compileWhere($where[1]), 7) . ')';
            } else {
                if (is_array($where[3])) {
                    $where[3] = '(' . $this->parameterize($where[3]) . ')';
                } else {
                    $where[3] = $this->param($where[3]);
                }

                $where[1] = $this->wrap($where[1]);

                return implode(' ', $where);
            }
        }, $wheres));
    }

    protected function compileOrderBy(Builder $query): string
    {
        if ( ! $orders = $query->getOrders()) return '';
        
        return ' order by ' . implode(', ', array_map(function ($order) {
            return $this->wrap($order[1]) . ' ' . $order[0];
        }, $orders));
    }

    protected function compileGroupBy(Builder $query): string
    {
        if ( ! $groups = $query->getGroups()) return '';
        
        return ' group by ' . implode(', ', array_map([$this, 'wrap'], $groups));
    }

    protected function compileLimit(Builder $query): string
    {
        $start = $query->getStart();
        $length = $query->getLength();

        if ($start >= 0 && $length > 0) {
            return " limit $start, $length";
        }

        if ($start > 0) {
            return " limit $start";
        }

        return '';
    }

    protected function parameterize(array $params): string
    {
        return implode(', ', array_map([$this, 'param'], $params));
    }

    protected function param($value): string
    {
        if ($value instanceof Expression) {
            return (string)$value;
        }

        return '?';
    }

    protected function wrap($value): string
    {
        if ($value instanceof Expression) {
            return (string)$value;
        }
        
        if (is_object($value)) {
            throw new \Exception('Cannot wrap from class ' . get_class($value));
        }

        $value = trim($value);
        
        if (false !== stripos($value, ' as ')) {
            $segments = preg_split('/\s+as\s+/i', $value);

            return $this->wrap(trim($segments[0])) . ' as ' . $this->wrap(trim($segments[1]));
        }

        if (strpos($value, '.') !== false) {
            $segments = explode('.', $value);

            foreach ($segments as $key => $item) {
                $segments[$key] = $this->wrapValue($item);
            }

            return implode('.', $segments);
        }

        return $this->wrapValue($value);
    }

    protected function wrapValue(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
}