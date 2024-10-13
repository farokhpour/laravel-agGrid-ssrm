<?php

namespace App\Services\GridServerSideSearch\GovSearch;

use App\Services\GridServerSideSearch\Search;


class SubcontrolSearch extends Search

{
    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'tags.asset':
                case 'assetTagsList':
                    if (count($filter['values']) === 0){
                        $this->query->where('id','nothing');
                        break;
                    }
                    $this->query
                        ->whereHas('assetTags', function ($q) use($filter){
                            return $q->whereIn('title',$filter['values']);
                        });
                    break;
                case 'tags.process':
                case 'processTagsList':
                    if (count($filter['values']) === 0){
                        $this->query->where('id','nothing');
                        break;
                    }
                    $this->query
                        ->whereHas('processTags', function ($q) use($filter){
                            return $q->whereIn('title',$filter['values']);
                        });
                    break;
                case 'tags.section':
                case 'sectionTagsList':
                    if (count($filter['values']) === 0){
                        $this->query->where('id','nothing');
                        break;
                    }
                    $this->query
                        ->whereHas('sectionTags', function ($q) use($filter){
                            return $q->whereIn('title',$filter['values']);
                        });
                    break;
                case 'tags.other':
                case 'otherTagsList':
                    if (count($filter['values']) === 0){
                        $this->query->where('id','nothing');
                        break;
                    }
                    $this->query
                        ->whereHas('otherTags', function ($q) use($filter){
                            return $q->whereIn('title',$filter['values']);
                        });
                    break;
                case 'type':
                    if(count($filter['values'])){
                        $filter = $filter['values'];
                        $filterMap = [];
                        if(in_array('پیشگیرانه' ,$filter)){
                            $filterMap[] = ['preventive'];
                        }
                        if(in_array('اصلاحی' ,$filter)){
                            $filterMap[] = ['corrective'];
                        }
                        $this->query->whereIn('type',$filterMap);
                    }else{
                        $this->query->where('status','nothing');
                    }
                    break;
                case 'levelsWithTranslate':
                    if(count($filter['values'])){
                        $filter = $filter['values'];
                        if(in_array('سازمانی' ,$filter)){
                            $this->query->where('has_organizational',1);
                        }
                        if(in_array('مدیریتی' ,$filter)){
                            $this->query->where('has_managerial',1);
                        }
                    }else{
                        $this->query->where('status','nothing');
                    }
                    break;
                case 'mga_threats':
                    if(count($filter['values'])){
                        $filter = $filter['values'];
                        $this->query->whereHas('mgaThreats',function($q)use($filter){
                            $q->whereIn('title' , $filter);
                        });
                    }else{
                        $this->query->where('status','nothing');
                    }
                    break;
                case str_contains($key,"standard_id"):
                    $standardId = str_replace("standard_id","",$key);
                    $this->filterByStandards($filter, $standardId);
                    break;

                case 'control_title':
                    $this->query->where('title',"like","%".$filter['filter']."%");
                    break;

                case 'status':
                    if (count($filter['values']) === 0){
                        $this->query->where('id','nothing');
                        break;
                    }

                    $this->query->where('usability_status',$filter['values'][0] == 'کاربرد دارد');
                    break;

                case str_contains($key, 'controls.'):
                    $standardId = str_replace("controls.","",$key);
                    $this->filterByStandards($filter, $standardId);
                    break;

                case 'organization_title':
                    $this->query->whereHas('organization', function ($org_query) use($filter){
                        $org_query->whereIn('title',$filter['values']);
                    });
                    break;

                case 'description':
                    $this->query->whereHas('soaDescription', function ($dquery) use($filter){
                        $dquery->where('body','LIKE',"%".$filter['filter']."%");
                    });
                    break;
                default:
                    if(in_array($key, ['acceptance_risk', 'has_legal_requirement', 'has_business_requirement'])){
                        if (count($filter['values']) === 0){
                            break;
                        }

                        $this->query->where($key,$filter['values'][0] == 'دارد');
                        break;
                    }

                    $this->query->where($key,"like","%".$filter['filter']."%");
            }
        }
        return $this;
    }

    public function applySorts():Search{
        return $this;
    }

    /**
     * @param mixed $filter
     * @param array|int|string $standardId
     * @return void
     */
    public function filterByStandards(mixed $filter, array|int|string $standardId): void
    {
        $this->query->whereHas('controls', function ($q) use ($filter, $standardId) {
            $q->where('title', "like", "%" . $filter['filter'] . "%")->whereHas('domain', function ($q2) use ($standardId) {
                $q2->whereHas('standard', function ($q3) use ($standardId) {
                    $q3->where('id', $standardId);
                });
            })->orWhere('number', "like", "%" . $filter['filter'] . "%")->whereHas('domain', function ($q2) use ($standardId) {
                $q2->whereHas('standard', function ($q3) use ($standardId) {
                    $q3->where('id', $standardId);
                });
            });
        });
    }
}