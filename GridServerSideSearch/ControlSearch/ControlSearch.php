<?php
namespace App\Services\GridServerSideSearch\ControlSearch;

use App\Services\GridServerSideSearch\Search;

class ControlSearch extends Search{


    public function applyFilters():Search{
        
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'domain.title':    
                    $new_filter = $filter['filter'];
                    $this->query
                    ->whereHas('domain',function($subQuery) use($new_filter){
                        $subQuery->where('title',"like","%".$new_filter."%")
                        ->whereHas('standard' , function($q){
                            $q->organizationScope();
                        });
                    });
                    break;
                case 'domain.number':    
                    $new_filter = $filter['filter'];
                    $this->query
                    ->whereHas('domain',function($subQuery) use($new_filter){
                        $subQuery->where('number',"like","%".$new_filter."%")
                        ->whereHas('standard' , function($q){
                            $q->organizationScope();
                        });
                    });
                    break;
                case 'title':
                    $this->query->where('title',"like","%".$filter['filter']."%");
                    break;
                case 'number':
                    $this->query->where('number',"like","%".$filter['filter']."%");
                    break;
                case 'domain.standard.title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->whereHas('domain.standard',function($subQuery) use($new_filter){
                        $subQuery->organizationScope()
                            ->where('title',"like","%".$new_filter."%");
                    });
                    break;
            }
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