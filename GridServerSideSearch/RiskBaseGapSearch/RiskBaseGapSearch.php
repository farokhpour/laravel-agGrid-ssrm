<?php

namespace App\Services\GridServerSideSearch\RiskBaseGapSearch;

use App\Services\GridServerSideSearch\Search;

class RiskBaseGapSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'control_title':
                    $this->query->whereIn('control.title',$filter['values']);
                    break;
                case 'control_number':
                    $this->query->where('control.number',$filter['values']);
                    break;
                case 'sub_control_title':
                    $new_filter = $filter['values'];
                    $this->query->whereHas('subControl',function($q)use($new_filter){
                        $q->whereIn('title',$new_filter);
                    });
                    break;
                case 'sub_control_number':
                    $new_filter = $filter['values'];
                    $this->query->whereHas('subControl',function($q)use($new_filter){
                        $q->whereIn('number',$new_filter);
                    });
                    break;
                // case 'vulnerability_value':
                //     foreach($filter['values'] as $fValue){
                //         $result = $this->mapResult($fValue);
                //         $operator = request()->operator;
                //         $this->query->havingRaw("$operator(mga_risks.vulnerability_value) == $result");
                //     }
                //     break;
            }
        }
        return $this;
    }
    

    public function applySorts():Search{

        // if(count($this->sorts)){

        //     $sortCol = ($this->sorts)[0]['colId'];
        //     $sortType = ($this->sorts)[0]['sort'];

        //     $this->query->orderBy($sortCol,$sortType);
        // }
       
        return $this;
    }  


    public function mapResult($result)
    {
        $data = [
            'اصلا پیاده سازی نشده' => '5',
            'تا حد کمی پیاده سازی شده' => '4',
            'تا حد زیادی پیاده سازی شده' => '2',
            'بطور کامل پیاده سازی شده' => '1',
            'محاسبه نشده' => null,
        ];

        return $data[$result];
    }

}