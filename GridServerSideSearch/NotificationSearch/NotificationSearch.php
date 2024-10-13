<?php
namespace App\Services\GridServerSideSearch\NotificationSearch;

use App\Model\File;
use App\Model\SdDocumentState;
use App\Services\GridServerSideSearch\Search;
use DB;

class NotificationSearch extends Search{
    
    public function applyFilters():Search{
    
        foreach($this->filters as $key => $filter){
            
            $filterSearch = $filter['filter'];
            $this->query->where(function ($query) use ($filterSearch,$key) {
                $query->whereRaw("json_extract(data, '$.$key') like ?", ['%' . $filterSearch . '%']);
            });

        }
        return $this;
    }

    public function applySorts():Search{

        return $this;
    }

}
