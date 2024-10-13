<?php

namespace App\Services\GridServerSideSearch\AuditService;

use App\Services\GridServerSideSearch\Search;

class AuditReportSearch extends Search{

    public function applyFilters():Search{

        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'audit_title':
                    $this->query->whereIn('au_audits.title',$filter['values']);
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
                case 'standard_title':
                    $this->query->whereIn('standard.title',$filter['values']);
                    break;
                case 'organization.title':
                    $new_filter = $filter['values'];
                    $this->query
                    ->with('organization')
                    ->whereHas('organization', function ($ch_query) use($new_filter){
                        $ch_query->whereIn('title',$new_filter);
                    });
                    break;
                case 'status':
                    if(count($filter['values'])){
                        foreach($filter['values'] as $filterValue){
                            $filter_data = $this->mapResult($filterValue);
                            $this->query->orWhere("au_audit_controls.status", $filter_data);
                        }
                    }else{
                        $this->query->where('au_audit_controls.id','nothing');
                    }
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
            'عدم انطباق جزئی' => 'minor_non_compliance',
            'عدم انطباق عمده' => 'major_non_compliance',
            'انطباق' => 'compliance',
            'فرصت برای بهبود' => 'opportunity_for_improvement',
            'نقطه قوت' => 'strength',
        ];

        return $data[$result];
    }
}