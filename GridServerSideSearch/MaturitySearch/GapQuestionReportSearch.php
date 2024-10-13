<?php

namespace App\Services\GridServerSideSearch\MaturitySearch;

use App\Model\GapPeriod;
use App\Model\MaturityAssessment;
use App\Services\GridServerSideSearch\Search;

class GapQuestionReportSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'title':
                    $this->query->where('title',"like","%".$filter['filter']."%");
                    break;
                case 'control.title':
                        $this->query->whereHas('control',function($q) use($filter){
                            $q->where('title',"like","%".$filter['filter']."%");
                        });
                    break;
                case 'standard_title':
                    $this->query->whereHas('control',function($q) use($filter){
                        $q->whereHas('domain',function($q1) use($filter){
                            $q1->whereHas('standard',function($q2) use($filter){
                                $q2->where('title',"like","%".$filter['filter']."%");
                            });
                        });
                    });
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