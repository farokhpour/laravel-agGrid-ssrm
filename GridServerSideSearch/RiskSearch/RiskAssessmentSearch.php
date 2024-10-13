<?php
namespace App\Services\GridServerSideSearch\RiskSearch;


use App\Services\GridServerSideSearch\Search;

class RiskAssessmentSearch extends Search{


    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'rm_asset_title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->whereHas('rmAsset', function ($ans_query) use($new_filter){
                        $ans_query->where('title',"like","%".$new_filter."%");
                    });
                break; 
                case 'vulnerability_value_1':
                case 'impact_value_1':
                case 'threat_value_1':
                    if(!count($filter['values'])){
                        $this->query->whereNull('title');
                    }else{
                        $col = str_replace("_1","",$key);
                        $this->query->where(function($q) use($filter , $col){
                            $rules = $this->buildRule();
                            foreach($filter['values'] as $value){
                                $query = str_replace('x',$col,$rules[$value]);
                                $query = str_replace('&&','AND',$query);
                                $q->orWhereRaw($query);
                            }
                        });
                    }
                break; 
                default:
                    $this->query->where($key,"like","%".$filter['filter']."%");
                    
            }
        }
        return $this;
    }
    public function buildRule(){
        $MGA = \Mga::initialProperty([], session('mga_project)'));
        return getClassRules($MGA->LEVEL_VALUATION['vulnerability_values'], $MGA->VULNERABILITY_LEVEL['level'],'x','title');
    }
    
    public function applySorts():Search{
        $sortCol="id";
        $sortType="desc";
        if(count($this->sorts)){
            if(
                   $this->sorts[0]['colId'] == "impact_value_1"
                || $this->sorts[0]['colId'] == "threat_value_1"
                || $this->sorts[0]['colId'] == "vulnerability_value_1"
            ){
                $sortCol = str_replace("_1","",$this->sorts[0]['colId']);
            }else{
                $sortCol = ($this->sorts)[0]['colId'];
            }
            $sortType = ($this->sorts)[0]['sort'];
            $this->query->orderBy($sortCol,$sortType);
        }
        $this->query->orderBy($sortCol,$sortType);
        return $this;
        return $this;
    
    }  
    

}