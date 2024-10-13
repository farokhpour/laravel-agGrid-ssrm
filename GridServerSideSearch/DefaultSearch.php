<?php

namespace App\Services\GridServerSideSearch;

use Exception;

/**
 * Class DefaultSearch
 *
 * @package \App\Services\GridServerSideSearch
 */
class DefaultSearch extends Search
{

    public function applyFilters(): Search
    {
        foreach ($this->filters as $column => $filterParams){
            $value = $filterParams['filter'];
            switch ($filterParams['type']){
                case 'contains':
                    $type = 'LIKE';
                    $value = "%".$value."%";
                    break;

                case 'notContains':
                    $type = 'Not LIKE';
                    $value = "%".$value."%";
                    break;

                case 'startsWith':
                    $type = 'LIKE';
                    $value = $value."%";
                    break;

                case 'endsWith':
                    $type = 'LIKE';
                    $value = "%".$value;
                    break;

                case 'equals':
                    $type = '=';
                    break;

                case 'notEqual':
                    $type = '!=';
                    break;

                default:
                    throw new Exception("not implementation");
            }

            $this->query->where($column,$type,$value);
        }

        return $this;
    }

    public function applySorts(): Search
    {
        foreach ($this->sorts as $sortParams){
            $this->query->orderBy($sortParams['colId'],$sortParams['sort']);
        }

        return $this;
    }
}
