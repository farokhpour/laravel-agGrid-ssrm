<?php
namespace App\Services\GridServerSideSearch\RiskSearch;


use App\Services\GridServerSideSearch\Search;

class ItemReportSearch extends Search{
    
    public function applyFilters():Search{
        
        
        foreach($this->filters as $key => $filter){
            switch ($key) {    
                case 'impact_value':
                case 'risk_value':
                case 'threat_value':
                case 'vulnerability_value':
                    if(!count($filter['values'])){
                        $this->query->whereNull('title');
                    }else{
                        $this->query->where(function($q) use($filter , $key){
                            $rules = $this->buildRule($key);
                            foreach($filter['values'] as $value){
                                $query = str_replace('x',$key,$rules[$value]);
                                $query = str_replace('&&','AND',$query);
                                $q->orWhereRaw($query);
                            }
                        });
                    }
                    break;
                case 'likelihood':
                case 'payamad':
                    if(!count($filter['values'])){
                        $this->query->whereNull('title');
                    }else{
                        $this->query->where(function($q) use($filter , $key){
                            $rules = $this->buildRule($key);
                            foreach($filter['values'] as $value){
                                $query = str_replace('x',$key.'_value',$rules[$value]);
                                $query = str_replace('&&','AND',$query);
                                $q->orWhereRaw($query);
                            }
                        });
                    }
                    break;
                case 'risk_value_1':
                    if(floatval($filter['filter'])){
                        $this->query->where("risk_value","like","%".(floatval($filter['filter'])/\Mga::initialProperty([], session('mga_project)'))->RANGE_OF_TEN)."%");
                    }else{
                        $this->query->whereNull('title');
                    }
                    break;
                case 'likelihood_1':
                case 'payamad_1':
                    $key = str_replace("_1","_value",$key);
                    $this->query->where($key,"like","%".$filter['filter']."%");
                    break;
                case 'organizational':
                    $this->query->whereIn('is_organizational',$this->findOrganizationalStatus($filter['values']));
                    break;
                case 'organization_title':
                    $this->query->whereHas('mgaProject.organization', function ($org_query) use($filter){
                        $org_query->whereIn('title',$filter['values']);
                    });
                    break;
                case 'title':
                        $this->query->whereIn('title',$filter['values']);
                    break;
                default:
                    if(strpos($key,"_1")){
                        $key = str_replace("_1","",$key);
                    }
                    $this->query->where($key,"like","%".$filter['filter']."%");
                    break;
            }
        }
        return $this;
    }


    public function findOrganizationalStatus($values)
    {
        $result = [];
        foreach ($values as $value)
        {
            $result[] = $value == 'سازمانی' ? 1 : 0;
        }

        return $result;
    }

    public function applySorts():Search{
        $sortCol="id";
        $sortType="desc";
        if(count($this->sorts)){
            if(
                $this->sorts[0]['colId'] == "risk_value_1"
                || $this->sorts[0]['colId'] == "impact_value_1"
                || $this->sorts[0]['colId'] == "threat_value_1"
                || $this->sorts[0]['colId'] == "vulnerability_value_1"
            ){
                $sortCol = str_replace("_1","",$this->sorts[0]['colId']);
            }else if( 
                $this->sorts[0]['colId'] == "likelihood"
                || $this->sorts[0]['colId'] == "payamad"
                || $this->sorts[0]['colId'] == "likelihood_1"
                || $this->sorts[0]['colId'] == "payamad_1"
            ){
                $sortCol =  $this->sorts[0]['colId']."_value";
                $sortCol =   str_replace('_1',"",$sortCol);
            }
            else{
                $sortCol = ($this->sorts)[0]['colId'];
            }
            $sortType = ($this->sorts)[0]['sort'];
        }
        $this->query->orderBy($sortCol,$sortType);
        return $this;
    } 
    public function buildRule($type){
        $riskValues = null;
        $likelihoodOrPayamadValues = null;
        $exception = null;
        $MGA = \Mga::initialProperty([], session('mga_project)'));
        $types = [
            "threat_value" => "threat_values",
            "vulnerability_value" => "vulnerability_values",
            "impact_value" => "impact_values",
            "risk_value" => "risk_values",
            "likelihood" => "risk_values",
            "payamad" => "risk_values",
        ];
        $levels = [
            "threat_value" => "THREAT_LEVEL",
            "vulnerability_value" => "VULNERABILITY_LEVEL",
            "impact_value" => "IMPACT_LEVEL",
            "risk_value" => "RISK_LEVEL",
            "likelihood" => "RISK_LEVEL",
            "payamad" => "RISK_LEVEL",
        ];
        if($type == 'risk_value'){
            $riskValues = array_map(function ($item) use($MGA) {
                return $item["max"]/ $MGA->RANGE_OF_TEN;
            }, $MGA->LEVEL_RANGE["rangeRisk"]);
            $exception = $riskValues;
        }
        if($type == 'likelihood' || $type == 'payamad'){
            $likelihoodOrPayamadValues = array_map(function ($item) {
                return $item["max"];
            }, $MGA->LEVEL_RANGE["rangeRisk"]);
            $exception = $likelihoodOrPayamadValues;
        }
        return getClassRules($exception?$exception:$MGA->LEVEL_VALUATION[$types[$type]], $MGA->{$levels[$type]}['level'],'x','title');
    }

}
