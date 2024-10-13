<?php
namespace App\Services\GridServerSideSearch\MgaCategorizationGroupSearch;

use App\Services\GridServerSideSearch\Search;

class MgaCategorizationGroupSearch extends Search{

    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            if($key == "asset_categorization.title"){
                if(count($filter['values'])){
                    $filter = $filter['values'];
                    $this->query->whereHas('assetCategorization',function($q) use($filter){
                        $q->whereIn('title',$filter);
                    });
                }else{
                    $this->query->where('status','nothing');
                }
            }
            else if($key == "reports.0.roundRisk"){
                $filter = $filter['filter'];
                $this->query->whereHas('reports',function($q) use($filter){
                    $q->where('risk',"like","%".$filter."%");
                });
            }
            else if($key == "reports.0.roundThreat"){
                $filter = $filter['filter'];
                $this->query->whereHas('reports',function($q) use($filter){
                    $q->where('threat',"like","%".$filter."%");
                });
            }
            else if($key == "reports.0.roundVulnerability"){
                $filter = $filter['filter'];
                $this->query->whereHas('reports',function($q) use($filter){
                    $q->where('vulnerability',"like","%".$filter."%");
                });
            }
            else if($key == "reports.0.roundImpact"){
                $filter = $filter['filter'];
                $this->query->whereHas('reports',function($q) use($filter){
                    $q->where('impact',"like","%".$filter."%");
                });
            }
            else if($key == "reference.title"){
                $filter = $filter['filter'];
                $this->query->whereHas('reference',function($q) use($filter){
                    $q->where('title',"like","%".$filter."%");
                });
            }
            else if($key == "tags.0.name"){
                $filter = $filter['filter'];
                $this->query->whereHas('tags',function($q) use($filter){
                    $q->where('name',"like","%".$filter."%");
                });
            }
            else{
                $this->query->where($key,"like","%".$filter['filter']."%");
            }
        }
        return $this;
        
    }

    public function applySorts():Search{
        $sortCol="mga_categorization_group.id";
        $sortType="desc";
        if(count($this->sorts)){
            if(($this->sorts)[0]['colId'] == "userType"){
                $sortCol = "email";
            }else if(($this->sorts)[0]['colId'] == "isEnable"){
                $sortCol = "is_disable";
            }else{
                $sortCol = ($this->sorts)[0]['colId'];
            }
            $sortType = ($this->sorts)[0]['sort'];
        }
        $this->query->orderBy($sortCol,$sortType);

        return $this;
    }
}
