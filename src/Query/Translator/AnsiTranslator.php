<?php

namespace Esclaudio\Datatables\Query\Translator;

use Esclaudio\Datatables\Query\Translator\TranslatorInterface;
use Esclaudio\Datatables\Query\Expression;
use Esclaudio\Datatables\Query\Builder;

class AnsiTranslator implements TranslatorInterface
{
    protected $quotePrefix = '"';
    protected $quoteSuffix = '"';

    public function translate(Builder $query): string
    {
        if ( ! $query->getTable()) {
            return '';
        }

        return $this->translateSelect($query)
            . $this->translateFrom($query)
            . $this->translateJoin($query)
            . $this->translateWhere($query)
            . $this->translateGroupBy($query)
            . $this->translateOrderBy($query)
            . $this->translateLimit($query);
    }

    protected function translateSelect(Builder $query): string
    {
        $fields = $query->getFields();

        if ( ! $fields) return 'select *';

        $build = 'select ';

        foreach ($fields as $alias => $field) {
            if (is_int($alias)) {
                $build .= $this->escape($field) . ', ';
            } else {
                $build .= $this->escape($field) . ' as ' . $this->escape($alias) . ', ';
            }
        }
        
        return substr($build, 0, -2);
    }

    protected function translateFrom(Builder $query): string
    {
        return ' from ' . $this->escape($query->getTable());
    }

    protected function translateJoin(Builder $query): string
    {
        if ( ! $joins = $query->getJoins()) return '';

        return ' ' . implode(' ', array_map(function ($join) {
            return $join[0] . ' join ' . $this->escape($join[1]) . ' on ' . $this->escape($join[2]) . ' ' . $join[3] . ' ' . $this->escape($join[4]);
        }, $joins));
    }

    protected function translateWhere(Builder $query): string
    {
        if ( ! $wheres = $query->getWheres()) return '';
        
        return ' ' . implode(' ', array_map(function ($where) {
            if ($where[1] instanceof Builder) {
                return $where[0] . ' (' . substr($this->translateWhere($where[1]), 7) . ')';
            } else {
                if (is_array($where[3])) {
                    $where[3] = '(' . $this->parameterize($where[3]) . ')';
                } else {
                    $where[3] = $this->param($where[3]);
                }

                $where[1] = $this->escape($where[1]);

                return implode(' ', $where);
            }
        }, $wheres));
    }

    protected function translateOrderBy(Builder $query): string
    {
        if ( ! $orders = $query->getOrders()) return '';
        
        return ' order by ' . implode(', ', array_map(function ($order) {
            return $this->escape($order[1]) . ' ' . $order[0];
        }, $orders));
    }

    protected function translateGroupBy(Builder $query): string
    {
        if ( ! $groups = $query->getGroups()) return '';
        
        return ' group by ' . implode(', ', array_map([$this, 'escape'], $groups));
    }

    protected function translateLimit(Builder $query): string
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
        return implode(', ', array_map(function ($value) {
            return $this->param($value);
        }, $params));
    }

    protected function param($value): string
    {
        if ($value instanceof Expression) {
            return (string)$value;
        }

        return '?';
    }

    protected function escape($value): string
    {
        if ($value instanceof Expression) {
            return (string)$value;
        }
        
        if (is_object($value)) {
            throw new \Exception('Cannot escape from class ' . get_class($value));
        }

        $value = trim($value);
        
        if (false !== stripos($value, ' as ')) {
            $segments = preg_split('/\s+as\s+/i', $value);

            return $this->escape(trim($segments[0])) . ' as ' . $this->escape(trim($segments[1]));
        }

        if (strpos($value, '.') !== false) {
            $segments = explode('.', $value);

            foreach ($segments as $key => $item) {
                $segments[$key] = $this->escapeIdentifier($item);
            }

            return implode('.', $segments);
        }

        return $this->escapeIdentifier($value);
    }

    protected function escapeIdentifier(string $value): string
    {
        return  $this->quoteSuffix . str_replace([$this->quoteSuffix, $this->quotePrefix], '', $value) . $this->quotePrefix;
    }
}