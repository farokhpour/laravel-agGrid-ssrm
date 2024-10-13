<?php
namespace App\Services\GridServerSideSearch\AssetSearch;

use App\Model\Post;
use App\Model\Section;
use App\Model\ViewRmAsset;
use App\Services\GridServerSideSearch\Search;

class AssetSearch extends Search{

    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'asset_categorization_title':
                    $this->query->whereIn($key,$filter['values']);
                    break;
                // case 'risk':
                //     $this->query->whereHas('itemRisks',function($q)use($filter){
                //         $q->whereHas('optimizedReports',function($q2)use($filter){
                //             $q2->where('risk' , "like","%".$filter['filter']."%");
                //         });
                //     });
                //     break;
                // case 'impact':
                //     $this->query->whereHas('itemRisks',function($q)use($filter){
                //         $q->whereHas('optimizedReports',function($q2)use($filter){
                //             $q2->where('impact' , "like","%".$filter['filter']."%");
                //         });
                //     });
                //     break;
                // case 'threat':
                //     $this->query->whereHas('itemRisks',function($q)use($filter){
                //         $q->whereHas('optimizedReports',function($q2)use($filter){
                //             $q2->where('threat' , "like","%".$filter['filter']."%");
                //         });
                //     });
                //     break;
                // case 'vulnerability':
                //     $this->query->whereHas('itemRisks',function($q)use($filter){
                //         $q->whereHas('optimizedReports',function($q2)use($filter){
                //             $q2->where('vulnerability' , "like","%".$filter['filter']."%");
                //         });
                //     });
                //     break;
                case 'asset_section_title':
                    $newFilters = $this->findChildren($filter['values'],Section::class);
                    $this->query->whereIn('asset_section_title',$newFilters);
                    $nullKey = array_search("NULL(بدون مقدار)", $newFilters);
                    if($nullKey !== false){
                        $this->query->orWhereNull('asset_section_title');
                    }
                    break;
                case 'asset_organization_title':
                    $this->query->whereIn('asset_organization_title',$filter['values']);
                    break;
                case 'asset_post_title':
                    $newFilters = $this->findChildren($filter['values'],Post::class);
                    $this->query->whereIn('asset_post_title',$newFilters);
                    $nullKey = array_search("NULL(بدون مقدار)", $newFilters);
                    if($nullKey !== false){
                        $this->query->orWhereNull('asset_post_title');
                    }
                    break;
                case 'asset_user_title':
                    $this->query->whereIn('asset_user_title',$filter['values']);
                    $nullKey = array_search("NULL(بدون مقدار)", $filter['values']);
                    if($nullKey !== false){
                        $this->query->orWhereNull('asset_user_title');
                    }
                    break;
                case 'other_owner_name':
                    $this->query->whereIn('other_owner_name',$filter['values']);
                    $nullKey = array_search("NULL(بدون مقدار)", $filter['values']);
                    if($nullKey !== false){
                        $this->query->orWhereNull('other_owner_name');
                    }
                    break;
                case 'item_risks':
                    $this->query->whereHas('mgaItems',function($q)use($filter){
                        $q->where('title' , "like","%".$filter['filter']."%");
                    });
                    break;
                case str_contains($key,"field_id"):
                    $fieldId = str_replace("field_id","",$key);
                    $this->query->whereHas('fields', function ($q) use ($filter,$fieldId){
                        $q->where('rm_asset_field_valuation.rm_asset_field_id' , $fieldId)->where('rm_asset_field_valuation.value',"like","%".$filter['filter']."%");
                    });
                    break;
                case "ip.value":
                    $this->query->whereHas('ip', function ($q) use ($filter){
                        $q->whereIn('rm_asset_field_valuation.rm_asset_field_id' ,explode(",",config('module.ip_fields_id')))->where('rm_asset_field_valuation.value',"like","%".$filter['filter']."%");
                    });
                    break;
                case "assetChildList":
                    $this->query
                        ->join('rm_asset_relation as rar', 'view_rm_asset.id', 'rar.rm_asset_parent_id')
                        ->join('rm_asset as ra', function($join) use ($filter) {
                            $join->on('ra.id', 'rar.rm_asset_child_id')
                                ->where('ra.title',"like","%".$filter['filter']."%");
                        });
                    break;
                case "assetparentList":
                    $this->query
                        ->join('rm_asset_relation', 'view_rm_asset.id', 'rm_asset_relation.rm_asset_child_id')
                        ->join('rm_asset', function($join) use ($filter) {
                            $join->on('rm_asset.id', 'rm_asset_relation.rm_asset_parent_id')
                                ->where('rm_asset.title',"like","%".$filter['filter']."%");
                        });
                    break;
                default:
                    $this->query->where($key,"like","%".$filter['filter']."%");
                    break;
            }
        }
        return $this;

    }

    public function applySorts():Search{
        if(str_contains(request()->headers->get('referer'),"MgaCategorization")){
            return $this;
        }
        $sortCol="view_rm_asset.id";
        $sortType="desc";
        if(count($this->sorts)){
            $sortCol = ($this->sorts)[0]['colId'];
            $sortType = ($this->sorts)[0]['sort'];
        }
        $this->query->orderBy($sortCol,$sortType);

        return $this;
    }


    public function fieldSearch($filter)
    {
        $this->query->whereHas('valuation',function($query)use($filter){
            $query->where('value' , "like","%".$filter."%");
        }); 
    }


    public function findChildren($values,$type){
        $result = $values;
        foreach($values as $value){
            $model = $type::where('title',$value)->first();
            if($model){
                if($model->children){
                    $children = $this->findChildren($model->children->pluck('text')->toArray(),$type);
                    $result = array_merge($result,$children);
                }
            }
        }
        return  $result;
    }
}
