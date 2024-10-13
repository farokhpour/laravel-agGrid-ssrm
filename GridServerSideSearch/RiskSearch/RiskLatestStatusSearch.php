<?php
namespace App\Services\GridServerSideSearch\RiskSearch;


use App\Services\GridServerSideSearch\Search;

class RiskLatestStatusSearch extends Search{
    
    public function applyFilters():Search{
    
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'type':
                    if(count($filter['values'])){
                        $types = $this->convertType($filter['values']);
                        $this->query->whereIn('type', $types);
                        break;
                    }
                    else
                        $this->query->whereIn('type', ['nothing']);
                case 'organization.title':
                    if(count($filter['values']))
                    {
                        $filterOrg = $filter['values'];
                        $this->query->whereHas('organization',function($q) use($filterOrg){
                            $q->whereIn('title',$filterOrg);
                        });
                    }
                    else
                        $this->query->whereIn('type', ['nothing']);
                default:
                    break;
            }

        }
        return $this;
    }

    public function applySorts():Search{

        return $this;
    }


    public function convertType($types)
    {
        $results = [];
        foreach($types as $type)
        {
            if ($type == 'مدیریتی')
                $results[] = 'manegerial';
            else if ($type == 'سازمانی')
                $results[] = 'organizational';
            else
                $results[] = 'aggregation';
        }
        return $results;
    }
}
