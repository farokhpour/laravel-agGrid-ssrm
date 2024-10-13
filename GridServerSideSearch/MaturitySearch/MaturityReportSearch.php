<?php

namespace App\Services\GridServerSideSearch\MaturitySearch;

use App\Model\Domain;
use App\Services\GridServerSideSearch\Search;

class MaturityReportSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'period_title':
                    $this->query->whereIn('period_title',$filter['values']);
                    break;
                case 'organization_title':
                    $this->query->whereIn('organization_title',$filter['values']);
                    break;
                case 'control_number':
                    $this->query->whereIn('control_number',$filter['values']);
                    break;
                case 'control_title':
                    $this->query->whereIn('control_title',$filter['values']);
                    break;
                case 'domain_title':
                    $this->query->whereIn('domain_title',$this->findDomainsChildren($filter['values']));
                    break;
                case 'description':
                    $this->query->where('description',"like","%".$filter['filter']."%");
                    break;
                case 'standard_title':
                    if(count($filter['values']))
                        $this->query->whereIn('standard_title',$filter['values']);
                    else
                        $this->query->where('standard_title','nothing');
                    break;
                case 'result':
                    if(count($filter['values'])){
                        $this->query->whereIn('result',($filter['values']));
                    }else{
                        $this->query->where('control_id','nothing');
                    }
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
    
    public function findDomainsChildren($domains,$result=[]){
        foreach($domains as $domain){
            $result[] = $domain;
            $childrenDomains = Domain::whereHas('domain',function($q) use($domain){
                $q->where('title',$domain);
            })->select('title')->get()->toArray();
            if(count($childrenDomains)){
                $result = $this->findDomainsChildren($childrenDomains, $result);
            }
        }
        return $result;
    }

}