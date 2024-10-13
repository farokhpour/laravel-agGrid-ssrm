<?php
namespace App\Services\GridServerSideSearch\SdDocumentSearch;

use App\Model\File;
use App\Model\SdDocumentState;
use App\Services\GridServerSideSearch\Search;
use DB;

class SdDocumentSearch extends Search{
    
    public function applyFilters():Search{
    
        foreach($this->filters as $key => $filter){
            switch ($key) {
                case 'category.title':
                    $new_filter = $filter['values'];
                    $this->query
                        ->whereHas('category',function($subQuery) use($new_filter){
                            $subQuery->whereIn('title',$new_filter);
                        });
                    break;
                case 'group.title':
                    $new_filter = $filter['values'];
                    $this->query
                        ->whereHas('group',function($subQuery) use($new_filter){
                            $subQuery->whereIn('title',$new_filter);
                        });
                    break;
                case 'gridLastStateVersion':
                    $new_filter = $filter['filter'];
                    $documtnts = SdDocumentState::where('version',"like","%".$new_filter."%")
                        ->whereIn('id', function ($query){
                        $query->selectRaw('MAX(id)')
                            ->from('sd_document_state')
                            ->groupBy('sd_document_id');
                        })
                        ->pluck('sd_document_id');
                   
                    $this->query->whereIn('id',$documtnts);
                    break;
                case 'gridLastStateUserName':
                    $new_filter = $filter['filter'];
                    $documtnts = SdDocumentState::
                        whereIn('id', function ($query){
                            $query->selectRaw('MAX(id)')
                                ->from('sd_document_state')
                                ->groupBy('sd_document_id');
                            })
                        ->whereHas('user',function($subQuery) use($new_filter){
                            $subQuery->where('name',"like","%".$new_filter."%");
                        })
                        ->pluck('sd_document_id');

                    $this->query->whereIn('id',$documtnts);
                    break;
                case 'gridTransState':
                    if (count($filter['values']))
                    {
                        $new_filter = $this->findState($filter['values']);
                        $documtnts = SdDocumentState::whereIn('state',$new_filter)
                            ->whereIn('id', function ($query){
                            $query->selectRaw('MAX(id)')
                                ->from('sd_document_state')
                                ->groupBy('sd_document_id');
                            })
                            ->pluck('sd_document_id');
                       
                        $this->query->whereIn('id',$documtnts);
                    }
                    else
                        $this->query->where('code','nothing');

                    break;
                case 'classification.title':
                    $new_filter = $filter['filter'];
                    $this->query
                        ->whereHas('classification',function($subQuery) use($new_filter){
                            $subQuery->where('title',"like","%".$new_filter."%");
                        });
                    break;
                case 'gridFile':
                    $new_filter = $filter['filter'];
                    $uploaded_files = File::where('reference_table','SdDocument')->pluck('reference_id');
                    if ($new_filter == 'بارگذاری شده')
                        $this->query->whereIn('id',$uploaded_files);
                    else
                        $this->query->whereNotIn('id',$uploaded_files);
                    break;
                default:
                    $this->query->where($key, 'like' ,  '%'.$filter['filter'].'%');
                    break;
            }

        }
        return $this;
    }

    public function applySorts():Search{

        return $this;
    }

    public function findState($states)
    {

        $result = [];
        $state_maps = [
            'در حال ایجاد شده' => 'Edit',
            'در حال بازبینی' => 'Review',
            'در حال تایید' => 'Accepted',
            'تایید' => 'View',
        ];
        foreach($states as $state)
        {
            $result[] = $state_maps[$state];
        }

        return $result;
    }
}
