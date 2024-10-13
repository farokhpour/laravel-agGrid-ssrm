<?php
namespace App\Services\GridServerSideSearch;

use DB;
use Illuminate\Database\Eloquent\Builder;

abstract class Search{

    protected $filters;
    protected $sorts;
    protected $offset;
    protected $query;
    protected $limit;
    protected $item_id;


    abstract public function applyFilters() : Search;

    abstract public function applySorts() : Search;

    public function __construct(Builder $query,$filters,$sorts,$offset,$limit,$item_id = null) {
        $this->query = $query;
        $this->filters = $filters;
        $this->sorts = $sorts;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->item_id = $item_id;
    }
    
    public function setQuery(Builder $query):Search{
        $this->query = $query;
        return $this;
    }

    public function applyPaginate(): Builder{

        return $this->query->offset($this->offset)->limit($this->limit-$this->offset);
    }

    public function getTotal(): int{
        return $this->query->count();
    }

    public function getFinalSearch():Search{
        if($this->filters){
            if(count($this->filters)){
                $this->applyFilters();
            }
        }
        $this->applySorts();

        return $this;
    }

    public function getDataForExportExcel($table = null){

        if(request()->has('selectedIds') && count(request()->selectedIds))
            return $this->query->whereIn($table ? $table.'.id' : 'id',request()->selectedIds)->get();
        else
            return $this->query->get();
    }

    public function getFinalQueryBuilder(){
        return $this->getFinalSearch()->query; 
    }

    public function response(){
        return [
            'rowCount' => $this->getTotal(),
            'rowData' => $this->applyPaginate()->get()
        ];
    }


    public function groupByQuery($group_by_filed,$field,$function){

         return $this->query
            ->select('*')
            ->selectRaw($function.'('.$field.') as '.$field.'_'.strtolower($function))
            ->groupBy($group_by_filed)
            ->get();
    }

    public function percent($group_by_filed,$table)
    {
        return $this->query
            ->selectRaw("$group_by_filed, COUNT(*) as group_count")
                    ->groupBy($group_by_filed)
                    ->get();
    }
    

    public function groupByQueryLeveles($field,$function,$low_min,$low_max,$medium_min,$medium_max,$high_min,$high_max,$div){

        return $this->query
                ->select( DB::raw("CASE 
                                        WHEN $field/$div BETWEEN $low_min AND $low_max THEN 'کم'
                                        WHEN $field/$div BETWEEN $high_min AND $high_max THEN 'زیاد'
                                        WHEN $field/$div BETWEEN $medium_min AND $medium_max THEN 'متوسط'
                                        ELSE 'بحرانی'
                                        END AS risk_lable"))
                ->groupBy('risk_lable')
                ->selectRaw($function.'('.$field.') as '.$field.'_'.strtolower($function))
                ->get();
   }


}