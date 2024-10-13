<?php
namespace App\Services\GridServerSideSearch\RiskSearch;

use App\Services\GridServerSideSearch\IssueSearch\IssueDefaultSearch;
use App\Services\GridServerSideSearch\Search;

class MgaRtpSearch extends Search{


    public function applyFilters():Search{
        
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'title':
                    $this->query->where('title',"like","%".$filter['filter']."%");
                    break;    
                case 'end_date':
                case 'start_date':
                    if(array_key_exists('dateFrom', $filter)){
                        $dateFrom = date_format(date_create($filter['dateFrom']),"Y-m-d");
                        if($filter['type'] =='inRange'){
                            $dateTo = date_format(date_create($filter['dateTo']),"Y-m-d");
                            $this->query->whereBetween($key,[$dateFrom,$dateTo]);
                        }else{
                            $operator = $this->dateOperator($filter['type']);
                            $this->query->whereDate($key,$operator,$dateFrom);
                        }
                        
                    }else{
                        $dateFrom = date_format(date_create($filter['condition1']['dateFrom']),"Y-m-d");
                        if($filter['condition1']['type'] =='inRange'){
                            $dateTo = date_format(date_create($filter['condition1']['dateTo']),"Y-m-d");
                            $this->query->whereBetween($key,[$dateFrom,$dateTo]);
                        }else{
                            $operator = $this->dateOperator($filter['condition1']['type']);
                            $this->query->whereDate($key,$operator,$dateFrom);
                        }
                    }
                case 'mga_categorization_group.title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('mgaCategorizationGroup')
                    ->whereHas('mgaCategorizationGroup', function ($ch_query) use($new_filter){
                        $ch_query->where('title',"like","%".$new_filter."%");
                    });
    
                    break;                   
                    
                case 'sub_control.title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('subControl')
                    ->whereHas('subControl', function ($ch_query) use($new_filter){
                        $ch_query->where('title',"like","%".$new_filter."%");
                    });
    
                    break;       

                case 'user.name':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('user')
                    ->whereHas('user', function ($ch_query) use($new_filter){
                        $ch_query->where('name',"like","%".$new_filter."%");
                    });
    
                    break;
                case 'mga_risk.mga_threat.title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('MgaRisk.MgaThreat')
                    ->whereHas('MgaRisk.MgaThreat', function ($ch_query) use($new_filter){
                        $ch_query->where('title',"like","%".$new_filter."%");
                    });
    
                    break;    
                case 'canSeeRequestTitle':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->whereHas('reqRequest', function ($ch_query) use($new_filter){
                        $ch_query->where('code',"like","%".$new_filter."%");
                    });
    
                    break;           
                case 'request_status':
                    $new_filter = $filter['values'];
                    $this->query
                        ->whereHas('reqRequest', function ($q1) use($new_filter){
                            $q1->whereHas('issue',function($q2)use($new_filter){
                                $q2->whereIn('status',array_map(array($this,'translatePersianStatus'),$new_filter));
                            });
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

    public function dateOperator($type){

        $operator = '';
        switch ($type) {
            case 'equals':
                $operator = '=';
                break;
            
            case 'greaterThan':
                $operator = '>';
                break;
            case 'lessThan':
                $operator = '<';
                notEqual;
            case 'lessThan':
                $operator = '!=';
                break;
            
            default:
                $operator = '=';
                break;
        }
        return $operator;
    }

    public function translatePersianStatus($persianStatus){
        $statusInPersian = [
            "تعریف شده" => "assign" ,
            "متوقف" => "hold" ,
            "در حال انجام" => "todo",
            "تکمیل شده" => "done",
            "لغو شده" => "cancel" ,
            "بسته شده" => "closed" 
        ];
        return $statusInPersian[$persianStatus];
    }
}