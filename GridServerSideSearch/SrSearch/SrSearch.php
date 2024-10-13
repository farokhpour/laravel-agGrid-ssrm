<?php
namespace App\Services\GridServerSideSearch\SoaSearch;


use App\Services\GridServerSideSearch\Search;
use Illuminate\Database\Query\Builder;

class SoaSearch extends Search{
    public function __construct(Builder $query,$filters,$sorts,$offset,$limit,$item_id = null) {
        $this->query = $query;
        $this->filters = $filters;
        $this->sorts = $sorts;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->item_id = $item_id;
    }
    public function response(){
        return $this->applyNewPaginate()->get();
    }

    public function applyNewPaginate(): Builder{
        return $this->query->offset($this->offset)->limit($this->limit-$this->offset);
    }

    public function applyFilters(): Search
    {
        foreach ($this->filters as $column => $filterParams){
            if($column === 'usability_status'){
                if (count($filterParams['values']) === 0){
                    continue;
                }

                $this->query->where('usability_status',$filterParams['values'][0]);
                continue;
            }

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


    public function applySorts():Search{
        if(count($this->sorts)){

            $sortCol = ($this->sorts)[0]['colId'];
            $sortType = ($this->sorts)[0]['sort'];

            $this->query->orderBy($sortCol,$sortType);
        }
       

        return $this;
    }

}
