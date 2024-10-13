<?php
namespace App\Services\GridServerSideSearch\RiskSearch;

use App\Model\MgaItem;
use App\Services\GridServerSideSearch\Search;

class RiskAssessmentThreatSearch extends Search{


    public function applyFilters():Search{
        
        
        $item_id = null;
        $project_id = session('mga_project_id');
        $search = [
            'mga_project_id' => $project_id
        ];

        if($this->item_id){
            $mgaItem = MgaItem::find($this->item_id);
            $project_id = $mgaItem->mga_project_id;
            $item_id = $mgaItem->id;
            $search = [
                'mga_project_id' => $project_id
            ];
        }

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'title':
                    $this->query->where('title',"like","%".$filter['filter']."%");
                    break;
                case 'answers.choice.title':
                    if($filter['type']== 'blank'){
                        $this->query
                        ->doesntHave('answers')
                        ->orWhereHas('answers',function($subQuery){
                            $subQuery->where('answers',0);
                        });
                    }else{
                        $new_filter = $filter['filter'];
                        $this->query
                        ->whereHas('answers',function($subQuery) use($search,$new_filter,$item_id){
                            $subQuery->where($search)
                            ->where(function ($q) use ($item_id) {
                                return $q->whereNull('mga_item_id')->orWhere('mga_item_id' , $item_id);
                            })
                            ->orderBy('mga_item_id', request()->isManagementRisk ? 'ASC' : 'DESC')
                            ->whereHas('choice',function($answerQuery) use($new_filter){
                                $answerQuery->where('title',"like","%".$new_filter."%");
                            });
                            
                        });
                    }
                    break;    
                case 'answers.description':
                    $new_filter = $filter['filter'];

                    $this->query
                    ->whereHas('answers',function($subQuery) use($search,$new_filter,$item_id){
                        $subQuery->where($search)
                        ->where(function ($q) use ($item_id) {
                            return $q->whereNull('mga_item_id')->orWhere('mga_item_id' , $item_id);
                        })
                        ->where('description',"like","%".$new_filter."%")
                        ->orderBy('mga_item_id', request()->isManagementRisk ? 'ASC' : 'DESC');
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