<?php

namespace App\Services\GridServerSideSearch\AuditSearch;

use App\Services\GridServerSideSearch\Search;

class AuditCheckListReportSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'title':
                    $this->query->whereIn('check_lists.title',$filter['values']);
                    break;
                case 'status':
                    $this->query->whereIn('au_audit_check_lists.status',array_map(array($this,'mapResult'),$filter['values']));
                    break;
                case 'description':
                    $this->query->where('au_audit_check_lists.description',"like","%".$filter['filter']."%");
                    break;
                case 'domain_title':
                    $this->query->whereIn('domain.title',$filter['values']);
                    break;
                case 'standard_title':
                    $this->query->whereIn('standard.title',$filter['values']);
                    break;
                case 'control_title':
                    $this->query->whereIn('control.title',$filter['values']);
                    break;
                case 'control_number':
                    $this->query->whereIn('control.number',$filter['values']);
                    break;
                case 'organization.title':
                    $new_filter = $filter['values'];
                    $this->query
                    ->with('organization')
                    ->whereHas('organization', function ($ch_query) use($new_filter){
                        $ch_query->whereIn('title',$new_filter);
                    });
                    break;
                default:
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


    public function mapResult($result)
    {
        $data = [
            'بلی' => 'yes',
            'خیر' => 'no',
            'کاربرد ناپذیر' => 'inapplicable',
        ];

        return $data[$result];
    }
}