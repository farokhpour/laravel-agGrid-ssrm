<?php

namespace App\Services\GridServerSideSearch\IssueSearch;

use App\Services\GridServerSideSearch\Search;

class IssueDefaultSearch extends Search{

    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            if($key == 'status'){
                if(count($filter['values'])){
                    $this->query->whereIn('status',array_map(array($this,'translatePersianStatus'),$filter['values']));
                }
                else{
                    //for return nothing when nothing is selected in status filter
                    $this->query->where('status','nothing');
                }
            }
            else if($key == 'type'){
                if(count($filter['values'])){
                    $this->query->whereIn('type',array_map(array($this,'translatePersianType'),$filter['values']));
                }else{
                    //for return nothing when nothing is selected in status filter
                    $this->query->where('status','nothing');
                }
            }
            else if($key =='is_expired'){
                if(count($filter['values'])){
                    $this->query->whereIn('status',['todo','done','assign','hold']);
                    $this->query->whereNotNull('end_date');
                    if($filter['values'][0]=='منقضی شده'){
                        $this->query->where('end_date','<', date("Y-m-d"));
                    }else if($filter['values'][0]=='در جریان'){
                        $this->query->where('end_date','>', date("Y-m-d"));
                    }
                }
                else{
                    //for return nothing when nothing is selected in status filter
                    $this->query->where('status','nothing');
                }
            }
            else if($key == 'start_date' || $key == 'end_date'){
                if(array_key_exists('dateFrom', $filter)){
                    $dateFrom = jalaliToMiladi(date_format(date_create($filter['dateFrom']),"Y/m/d"));
                    if($filter['type'] =='inRange'){
                        $dateTo = jalaliToMiladi(date_format(date_create($filter['dateTo']),"Y/m/d"));
                        $this->query->whereBetween($key,[$dateFrom,$dateTo]);
                    }else{
                        $operator = $this->dateOperator($filter['type']);
                        $this->query->whereDate($key,$operator,$dateFrom);
                    }
                    
                }else{
                    $dateFrom = jalaliToMiladi(date_format(date_create($filter['condition1']['dateFrom']),"Y/m/d"));
                    if($filter['condition1']['type'] =='inRange'){
                        $dateTo = date_format(date_create($filter['condition1']['dateTo']),"Y/m/d");
                        $this->query->whereBetween($key,[$dateFrom,$dateTo]);
                    }else{
                        $operator = $this->dateOperator($filter['condition1']['type']);
                        $this->query->whereDate($key,$operator,$dateFrom);
                    }
                }
            }
            else{
                $this->query->where($key,"like","%".$filter['filter']."%");
            }
        }
        return $this;
        
    }
    public function applySorts():Search{
        $sortCol="id";
        $sortType="desc";
        if(count($this->sorts)){
            $sortCol = ($this->sorts)[0]['colId'];
            $sortType = ($this->sorts)[0]['sort'];
        }
        $this->query->orderBy($sortCol,$sortType);

        return $this;
    }
    //todo
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
    //todo
    public function translatePersianType($persianType){
        $typeInPersian = [
            "معمولی" => "TICKET", 
            "فرآیندی" => "PROCESS", 
            "ممیزی" => "AUDIT",
            "RTP ریسک هوشمند" => "RTP_SMART_RISK",
            "RTP ریسک فنی" => "RTP_TECHNICAL_RISK", 
            "اندازه‌گیری عملکرد" => "EFFECTIVENESS_MEASUREMENT", 
            "اقدام اصلاحی شاخص" => "corrective", 
            "اقدام اهداف امنیتی" => "action", 
        ];
        return $typeInPersian[$persianType];
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
}