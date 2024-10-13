<?php

namespace App\Services\GridServerSideSearch\MaturitySearch;

use App\Model\GapPeriod;
use App\Model\MaturityAssessment;
use App\Services\GridServerSideSearch\Search;

class GapReportSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'period_title':
                    $this->query->where('gap_periods.title',"like","%".$filter['filter']."%");
                    break;
                case 'control_number':
                    $this->query->where('control.number',"like","%".$filter['filter']."%");
                    break;
                case 'control_title':
                    $this->query->where('control.title',"like","%".$filter['filter']."%");
                    break;
                case 'domain_title':
                    $this->query->where('domain.title',"like","%".$filter['filter']."%");
                    break;
                case 'description':
                    $this->query->where('gap_results.description',"like","%".$filter['filter']."%");
                    break;
                case 'standard_title':
                    if(count($filter['values']))
                        $this->query->whereIn('standard_title',$filter['values']);
                    else
                        $this->query->where('standard_title','nothing');
                    break;
                case 'result':
                    if(count($filter['values'])){
                        $filters = $filter['values'];
                        foreach($filters as $filter){
                            $filter_data = $this->mapResult($filter);
                            $this->query->orHavingRaw("FLOOR(AVG(answers)) = $filter_data");
                        }
                    }else{
                        $this->query->where('control.id','nothing');
                    }
                    break;
            }
        }
        return $this;
    }
    


    public function mapResult($result)
    {
       $data = [
            'کاربرد ندارد' => '-1',
            'عدم انطباق' => '0',
            'انطباق نسبی' => '1',
            'انطباق کامل' => '2',
       ];

       return $data[$result];
    }

    public function applySorts():Search{

        if(count($this->sorts)){

            $sortCol = ($this->sorts)[0]['colId'];
            $sortType = ($this->sorts)[0]['sort'];

            $this->query->orderBy($sortCol,$sortType);
        }
       

        return $this;
    
    }  

    public function mapData($filters){
        $result = [
            'filterParams' => []
        ];
        if(request()->has('mat_type')){
            $result['relation'] = 'maturityAnswers';
            $result['maturityAnswers'] = 'maturity_assessment_id';
            $result['id'] = request()->route('MaturityAssessment');
            $formValues = MaturityAssessment::find(request()->route('MaturityAssessment'))->information['data']['formValues'];
        }else{
            $result['relation'] = 'answers';
            $result['answers'] = 'gap_period_id';
            $result['id'] = request()->route('GapPeriod');
            $formValues = GapPeriod::find(request()->route('GapPeriod'))->information['data']['formValues'];
        }
        if(@$filters['values']){
            foreach($filters['values']  as $filter){
                $result['filterParams'][] = array_search($filter,$formValues);
            }
        }else if(@$filters['filter']){
            $result['filterParams'] = $filters['filter'];
        }
        return $result ;
    }
}