<?php
namespace App\Services\GridServerSideSearch\AssetEventSearch;

use App\Services\GridServerSideSearch\Search;

class AssetEventSearch extends Search{

    public function applyFilters():Search{
        
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'type':
                    
                    $map = [];

                    foreach(__("enum.AssetEventTypes") as $k => $value) {
                        $map[$value] = $k;
                    }

                    $values = array_map(function($k) use ($map){
                        return $map[$k];
                    }, $filter['values']);

                    
                    $this->query->whereIn($key,$values);
                    break;
                
                default:
                    $this->query->where($key,"like","%".$filter['filter']."%");
                    break;
            }
        }
        return $this;
        
    }

    public function applySorts():Search{
        if(!is_array($this->sorts))
            return $this;

        
        if(count($this->sorts)){

            $sortCol = ($this->sorts)[0]['colId'];
            $sortType = ($this->sorts)[0]['sort'];

            $this->query->orderBy($sortCol,$sortType);
        }
       

        return $this;
    }
}
