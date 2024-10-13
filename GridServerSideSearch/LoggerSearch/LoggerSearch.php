<?php

namespace App\Services\GridServerSideSearch\LoggerSearch;

use Exception;
use App\Services\GridServerSideSearch\Search;

/**
 * Class LoggerSearch
 *
 * @package \App\Services\GridServerSideSearch
 */
class LoggerSearch extends Search
{

    public function applyFilters(): Search
    {
        foreach ($this->filters as $column => $filterParams){
            $value = $filterParams['filter'];

            if(trim($value) == 'مهمان' &&  $column == 'name'){
                $this->query->whereNull('userId');
                continue;
            }


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
        
        if(count($this->sorts) == 0){
            $this->query->orderBy('laravel_logger_activity.created_at', 'desc');
        }else{
            foreach ($this->sorts as $sortParams){
                $this->query->orderBy($sortParams['colId'],$sortParams['sort']);
            }
        }

        return $this;
    }
}
