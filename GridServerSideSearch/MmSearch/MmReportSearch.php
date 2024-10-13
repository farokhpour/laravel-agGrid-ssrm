<?php

namespace App\Services\GridServerSideSearch\MmSearch;

use App\Services\GridServerSideSearch\Search;

class MmReportSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'period_title':
                    $this->query->whereIn('mm_periods.title',$filter['values']);
                    break;
                case 'domain_title':
                    $this->query->whereIn('domain.title',$filter['values']);
                    break;
                case 'control_title':
                    $this->query->whereIn('control.title',$filter['values']);
                    break;
                case 'control_number':
                    $this->query->whereIn('control.number',$filter['values']);
                    break;
                case 'project.organization.title':
                    $this->query->whereHas('project',function($q) use($filter){
                        $q->whereHas('organization',function($q2) use($filter){
                            $q2->whereIn('title',$filter['values']);
                        });
                    });
                    break;
                case 'control_status':
                    if(count($filter['values'])){
                        foreach($filter['values'] as $filterValue){
                            $filter_data = $this->mapResult($filterValue);
                            $this->query->orHavingRaw("FLOOR(AVG(answers)) = $filter_data");
                        }
                    }else{
                        $this->query->where('mm_periods.id','nothing');
                    }
                    break;
                case 'control_percent':
                    $filter_data = $this->mapPercent($filter['filter']);
                    $this->query->havingRaw("FLOOR(AVG(answers)) = $filter_data");
                    break;
                case 'flag':
                    if(count($filter['values'])){
                        if (count($filter['values'])== 2)
                            return $this;
                        $filter_data = $this->mapStatus($filter['values'][0]);
                        $filter_data == '0' ? $this->query->havingRaw("FLOOR(AVG(answers)) != 3"): $this->query->havingRaw("FLOOR(AVG(answers)) = 3") ;
                    }else{
                        $this->query->where('mm_periods.id','nothing');
                    }
                    break;
                case 'level':
                    $this->query->whereIn('level',$filter['values']);
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

    public function mapStatus($result)
    {
        $data = [
            'انجام نشده' => '0',
            'انجام شده' => '1',
        ];
        return $data[$result];
    }


    public function mapResult($result)
    {
        $data = [
            'انجام نشده' => '0',
            'تا حدی انجام شده' => '1',
            'بخش زیادی انجام شده' => '2',
            'به طور کامل انجام شده' => '3',
        ];
        return $data[$result];
    }

    public function mapPercent($result)
    {
        $data = [
            '0' => '0',
            '33' => '1',
            '67' => '2',
            '100' => '3',
        ];

        return $data[$result];
    }
}