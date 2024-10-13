<?php

namespace App\Services\GridServerSideSearch\IssueSearch;

use App\Services\GridServerSideSearch\Search;

class IssueSmartRiskSearch extends IssueDefaultSearch{

    public $filterParams = [
        2 => 'title',
        3 => 'type',
        5 => 'mgaCategorizationGroup',
        6 => 'risk',
        7 => 'mgaThreat',
        8 => 'subControl',
        9 => 'subControl_number',
        10 => 'subControl_weight',
        11 => 'budget',
        12 => 'subControl_implementation_method',
        13 => 'subControl_description',
        14 => 'status',
        15 => 'code',
        16 => 'requester',
        17 => 'responsible',
        18 => 'start_date',
        19 => 'end_date',
        20 => 'is_expired'
    ];
    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            if(array_key_exists($key,$this->filterParams)){
                if($this->filterParams[$key] == 'status'){
                    if(count($filter['values'])){
                        $this->query->whereIn('status',array_map(array($this,'translatePersianStatus'),$filter['values']));
                    }
                    else{
                        //for return nothing when nothing is selected in status filter
                        $this->query->where('status','nothing');
                    }
                }
                else if($this->filterParams[$key] == 'type'){
                    if(count($filter['values'])){
                        $this->query->whereIn('type',array_map(array($this,'translatePersianType'),$filter['values']));
                    }else{
                        //for return nothing when nothing is selected in status filter
                        $this->query->where('status','nothing');
                    }
                }
                else if($this->filterParams[$key] =='is_expired'){
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
                else if($this->filterParams[$key] == 'start_date' || $this->filterParams[$key] == 'end_date'){
                    if(array_key_exists('dateFrom', $filter)){
                        $dateFrom = date_format(date_create($filter['dateFrom']),"Y-m-d");
                        if($filter['type'] =='inRange'){
                            $dateTo = date_format(date_create($filter['dateTo']),"Y-m-d");
                            $this->query->whereBetween($this->filterParams[$key],[$dateFrom,$dateTo]);
                        }else{
                            $operator = $this->dateOperator($filter['type']);
                            $this->query->whereDate($this->filterParams[$key],$operator,$dateFrom);
                        }
                        
                    }else{
                        $dateFrom = date_format(date_create($filter['condition1']['dateFrom']),"Y-m-d");
                        if($filter['condition1']['type'] =='inRange'){
                            $dateTo = date_format(date_create($filter['condition1']['dateTo']),"Y-m-d");
                            $this->query->whereBetween($this->filterParams[$key],[$dateFrom,$dateTo]);
                        }else{
                            $operator = $this->dateOperator($filter['condition1']['type']);
                            $this->query->whereDate($this->filterParams[$key],$operator,$dateFrom);
                        }
                    }
                }

                else if($this->filterParams[$key] == 'mgaCategorizationGroup' || $this->filterParams[$key] == 'mgaThreat' || str_contains( $this->filterParams[$key],'subControl') || $this->filterParams[$key] == 'budget' ){
                    $filter = $filter['filter'];
                    $type = $this->filterParams[$key];
                    $this->query->whereHas('rtp',function($q1) use($filter,$type){
                        if($type == 'mgaCategorizationGroup' || $type == 'subControl'){
                            $q1->whereHas($type,function($q2) use($filter){
                                $q2->where('title',"like","%".$filter."%");
                            });
                        }
                        if($type == 'budget'){
                            $q1->where($type,"like","%".$filter."%");
                        }
                        else if(str_contains($type,'subControl_')){
                            $q1->whereHas('subControl',function($q2) use($filter,$type){
                                $q2->where(str_replace('subControl_','',$type),"like","%".$filter."%");
                            });
                        }
                        else{
                            $q1->whereHas('mgaRisk',function($q2) use($filter,$type){
                                $q2->whereHas('mgaThreat', function($q3) use($filter,$type){
                                    $q3->where('title',"like","%".$filter."%");
                                });
                            });
                        }
                    });
                }
                else{
                    $this->query->where($this->filterParams[$key],"like","%".$filter['filter']."%");
                }
            }
        }
        return $this;
        
    }
    public function applySorts():Search{
        $sortCol="id";
        $sortType="desc";
        if(count($this->sorts)){
            $sortCol = $this->filterParams[($this->sorts)[0]['colId']];
            $sortType = ($this->sorts)[0]['sort'];
        }
        $this->query->orderBy($sortCol,$sortType);

        return $this;
    }
}