<?php
namespace App\Services\GridServerSideSearch\RiskSearch;

use App\Model\MgaItem;
use App\Services\GridServerSideSearch\Search;

class OrganizationalAssessmentImpactSearch extends Search{


    public function applyFilters():Search{
        
        $search = [
            'mga_item_id' => null,
            'mga_project_id' => session('mga_project_id')
        ];

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'title':
                    $this->query->where('title',"like","%".$filter['filter']."%");
                    break;
                case 'question.answers.choice.title':
                    if($filter['type']== 'blank'){
                        $this->query
                        ->doesntHave('question.answers')
                        ->orWhereHas('question.answers',function($subQuery){
                            $subQuery->where('answers',0);
                        });
                    }else{
                        $new_filter = $filter['filter'];
                        $this->query
                        ->whereHas('question.answers',function($subQuery) use($search,$new_filter){
                            $subQuery->where($search)->whereHas('choice',function($answerQuery) use($new_filter){
                                $answerQuery->where('title',"like","%".$new_filter."%");
                            });
                        });
                    }
                    break;
                case 'question.answers.description':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->whereHas('question.answers',function($subQuery) use($search,$new_filter){
                        $subQuery->where($search)->where('description',"like","%".$new_filter."%");
                    });
 
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


}