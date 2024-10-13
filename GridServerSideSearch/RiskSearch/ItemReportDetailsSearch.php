<?php
namespace App\Services\GridServerSideSearch\RiskSearch;


use App\Services\GridServerSideSearch\Search;
use Str;
use Termwind\Components\BreakLine;

class ItemReportDetailsSearch extends Search{


    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            if(Str::endsWith($key, "_title")){
                $key = Str::replaceLast("_title","", $key);
            }
            switch ($key) {
                case 'impact':
                case 'risk':
                case 'threat_value':
                case 'vulnerability_value':
                    if(!count($filter['values'])){
                        $this->query->whereNull('mga_risks.id');
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
                    if(array_key_exists('values',$filter)){
                        if(!count(@$filter['values'])){
                            $this->query->whereNull('mga_risks.id');
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
                    }else{
                        $key = str_replace("_1","",$key);
                        $this->query->where($key,"like","%".@$filter['filter']."%");
                    }
                    break;
                case 'risk_value_1':
                    if(floatval($filter['filter'])){
                        $this->query->where("risk_value","like","%".(floatval($filter['filter'])/\Mga::initialProperty([], session('mga_project)'))->RANGE_OF_TEN)."%");
                    }else{
                        $this->query->whereNull('title');
                    }
                    break;
                case 'risk_1':
                    $this->query->where('risk',"like","%".(floatval($filter['filter'])/\Mga::initialProperty([], session('mga_project)'))->RANGE_OF_TEN)."%");
                    break;
                case 'vulnerability':
                case 'vulnerability_title':
                case 'vulnerability':
                case 'vulnerability_question.vulnerability_title':
                case 'vulnerability_question.vulnerability':
                    $new_filter = $filter['values'];
                    $this->query
                    ->with('vulnerabilityQuestion')
                    ->whereHas('vulnerabilityQuestion', function ($ch_query) use($new_filter){
                        $ch_query->whereIn('vulnerability_title',$new_filter);
                    });
                    break;
                case 'mga_threat_agent':
                case 'mga_threat_agent_title':
                case 'mga_threat_agent.title':
                    $new_filter = $filter['values'];
                    $this->query
                    ->with('mgaThreatAgent')
                    ->whereHas('mgaThreatAgent', function ($ch_query) use($new_filter){
                        $ch_query->whereIn('title',$new_filter);
                    });
                    break;
                case 'mga_threat':
                case 'mga_threat_title':
                case 'mga_threat.title':
                    $new_filter = $filter['values'];
                    $this->query
                    ->with('mgaThreat')
                    ->whereHas('mgaThreat', function ($ch_query) use($new_filter){
                        $ch_query->whereIn('title',$new_filter);
                    });
                    break;
                case 'mga_item.title':
                    $new_filter = $filter['values'];
                    if(!count($new_filter)){
                        $this->query->whereNull('mga_risks.id');
                    }else{
                        $this->query->whereHas('mgaItem',function($q) use($new_filter){
                            $q->whereIn('title',$new_filter);
                        });
                    }
                    break;
                case 'organization':
                    $this->query->whereHas('mgaProject.organization', function ($org_query) use($filter){
                        $org_query->whereIn('title',$filter['values']);
                    });
                    break;
                case 'sub_control_number':
                    $new_filter = $filter['values'];
                    $this->query
                        ->whereHas('vulnerabilityQuestion.reference', function ($ch_query) use($new_filter){
                            $ch_query->whereIn('number',$new_filter);
                    });
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

    public function applySorts():Search{
        $sortCol="mga_risks.id";
        $sortType="desc";
        if(count($this->sorts)){
            if(
                   $this->sorts[0]['colId'] == "risk_1"
                || $this->sorts[0]['colId'] == "impact_1"
                || $this->sorts[0]['colId'] == "threat_value_1"
                || $this->sorts[0]['colId'] == "vulnerability_value_1"
            ){
                $sortCol = str_replace("_1","",$this->sorts[0]['colId']);
            }
            else if( 
                $this->sorts[0]['colId'] == "likelihood"
                || $this->sorts[0]['colId'] == "payamad"
                || $this->sorts[0]['colId'] == "likelihood_1"
                || $this->sorts[0]['colId'] == "payamad_1"
            ){
                $sortCol =  $this->sorts[0]['colId'];
                $sortCol =   str_replace('_1',"",$sortCol);
            }
            else{
                $sortCol = ($this->sorts)[0]['colId'];
            }
            $sortType = ($this->sorts)[0]['sort'];
        }

        if(Str::endsWith($sortCol, "_title")){
            $sortCol = Str::replaceLast("_title","", $sortCol);
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
            "risk" => "risk_values",
            "likelihood" => "risk_values",
            "payamad" => "risk_values",
        ];
        $levels = [
            "threat_value" => "THREAT_LEVEL",
            "vulnerability_value" => "VULNERABILITY_LEVEL",
            "impact_value" => "IMPACT_LEVEL",
            "risk_value" => "RISK_LEVEL",
            "risk" => "RISK_LEVEL",
            "likelihood" => "RISK_LEVEL",
            "payamad" => "RISK_LEVEL",
        ];
        if($type == 'risk_value' || $type =='risk'){
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
