<?php
namespace App\Services\GridServerSideSearch\UserSearch;

use App\Services\GridServerSideSearch\Search;

class UserSearch extends Search{

    public function applyFilters():Search{
        foreach($this->filters as $key => $filter){
            if($key == "userType"){
                if(str_contains('مالک دارایی', $filter['filter'])){
                    $this->query->where("email",null);
                }else{
                    $this->query->where("email","like","%".$filter['filter']."%");
                }
                
            }else if($key == "isEnable"){
                if(count($filter['values'])){
                    // be dalil in k in field dar database ba name is_disable vojod darad pas agar useri faal bashad be mani in ast k is_disable on 0 ast.
                    $filter = in_array('فعال',$filter['values'])?0:1;
                    $this->query->where('is_disable',$filter);
                }else{
                    //baraye khali bargardadan query
                    $this->query->where('is_disable',10);
                }         
            }else{
                $this->query->where($key,"like","%".$filter['filter']."%");
            }
        }
        return $this;
        
    }

    public function applySorts():Search{
        $sortCol="id";
        $sortType="desc";
        if($this->sorts){
            if(count($this->sorts)){
                if(($this->sorts)[0]['colId'] == "userType"){
                    $sortCol = "email";
                }else if(($this->sorts)[0]['colId'] == "isEnable"){
                    $sortCol = "is_disable";
                }else{
                    $sortCol = ($this->sorts)[0]['colId'];
                }
                $sortType = ($this->sorts)[0]['sort'];
            }
        }
        $this->query->orderBy($sortCol,$sortType);

        return $this;
    }
}
