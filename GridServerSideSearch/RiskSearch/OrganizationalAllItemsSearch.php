<?php
namespace App\Services\GridServerSideSearch\RiskSearch;


use App\Services\GridServerSideSearch\Search;

class OrganizationalAllItemsSearch extends Search{


    public function applyFilters():Search{
        
        foreach($this->filters as $key => $filter){
            switch ($key) {
                // case 'risk_1':
                //     $this->query->where('risk',"like","%".$filter['filter']."%");
                //     break;
                // case 'threat_value_1':
                //     $this->query->where('threat_value',"like","%".$filter['filter']."%");
                //     break;
                // case 'vulnerability_value_1':
                //     $this->query->where('vulnerability_value',"like","%".$filter['filter']."%");
                //     break;
                // case 'impact_1':
                //     $this->query->where('impact',"like","%".$filter['filter']."%");
                //     break;    
                case 'vulnerability_question.vulnerability_title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('vulnerabilityQuestion')
                    ->whereHas('vulnerabilityQuestion', function ($ch_query) use($new_filter){
                        $ch_query->where('vulnerability_title',"like","%".$new_filter."%");
                    });
    
                    break;

                case 'mga_threat_agent.title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('mgaThreatAgent')
                    ->whereHas('mgaThreatAgent', function ($ans_query) use($new_filter){
                        $ans_query->where('title',"like","%".$new_filter."%");
                    });
                    break;


                case 'mga_threat.title':
                    $new_filter = $filter['filter'];
                    $this->query
                    ->with('mgaThreat')
                    ->whereHas('mgaThreat', function ($ch_query) use($new_filter){
                        $ch_query->where('title',"like","%".$new_filter."%");
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

}