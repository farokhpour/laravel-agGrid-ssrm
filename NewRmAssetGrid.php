<?php

namespace App\Modules\AssetInventory\RmAsset\Controllers;

use App\Http\Controllers\Gateway\TechnicalRiskController;
use App\Http\Requests\Request;
use Mga;
use App\Model\MgaOutcomeCriteria;
use App\Model\Post;
use App\Modules\Settings\Section\Models\Section;
use App\User;
use App\Http\Requests\SearchAssetRequest;
use App\Model\MgaProject;
use App\Model\Organization;
use App\Model\RmAssetCategorization;
use App\Model\RmAssetField;
use App\Model\ViewRmAsset;
use App\Services\ExportExcelService\AssetExportExcel;
use App\Services\GridServerSideSearch\AssetSearch\AssetSearch;
use Auth;
use DB;

trait NewRmAssetGrid
{
    public function newGridColumn(SearchAssetRequest $request)
    {
        $columns = $this->defaultColumns();

        $outcomeColumns = $this->getOutcomeColumns();

        $globalFields = $this->getGlobalFields();

        $specialFields = $this->getSpecialFieldsWithCategoryId($request);

        return array_merge($columns, $outcomeColumns, $globalFields, $specialFields);
    }


    public function excelGridColumn(SearchAssetRequest $request)
    {
        $columns = $this->excelColumns();

        $outcomeColumns = $this->getOutcomeColumns();

        $singleColumn = [[
            'headerName' => "مالک جانشین",
            'field' => "other_owner_name",
        ]];
        
        $globalFields = $this->getExcelGlobalFields();

        $specialFields = $this->getExcelSpecialFieldsWithCategoryId($request);

        $otherFields = $this->otherExcelColumn();

        return array_merge($columns, $outcomeColumns, $singleColumn ,$globalFields, $specialFields, $otherFields);
    }


    public function newGridData(SearchAssetRequest $request, $mga_categorization_id=null)
    {
        $res = app(TechnicalRiskController::class)->assetRelatedHostList();
        config()->set('asset_relation_with_hosts', $res->isOk() ? $res->getOriginalContent() : []);

        if ($this->isReportGrid($request))
            $this->cacheFilterModel($request,$mga_categorization_id);

        $input = $request->all();
        $filteredAsset = $this->queryProcessing($request,$mga_categorization_id);
        if (array_key_exists('excel', $input)){
            $this->authorize('excel', \App\Model\RmAsset::class);
            $data['rowData'] =  $filteredAsset->getDataForExportExcel();
            $headers = $this->excelGridColumn($request);    
            $exportService = new AssetExportExcel($data['rowData'],$headers,null,'Assets');
            $exportService->setHeader();
            return $exportService->getExportFile();
        }
        return $filteredAsset->response();
    }


    /**
     * query for find all assets
     */
    public function queryProcessing($request,$mga_categorization_id){

        $query = ViewRmAsset::query();
        $query->withCount('gapPeriods');
        $query->withAvg('gapPeriods','gap_percent');

        if($mga_categorization_id){
            $query = ViewRmAsset::select("*", "view_rm_asset.id as id");
        }
        $query->filterByOwner($request);
        $query
        ->with('fields:rm_asset_field.id,title,type','mgaOutcomeCriterias','mgaItems','assetParentList:title,rm_asset.id','assetChildList:title,rm_asset.id','valuation:id,rm_asset_id,value');
        
        if(!checkReportGridAndDashboardReferer()){
            $query->withAccess(auth()->user(), $request);
        }
        $query->withCount('mgaItems');
    
        if ($request->has('filterCategories') && count($request->filterCategories)) {
            if ($request->filterCategories[0] != "all") {
                $query->whereIn('rm_asset_categorization_id', $request->filterCategories);
            }
        }
        $query->filterByElastic($request);
        $filteredAsset = new AssetSearch($query, $request['filterModel'], $request['sortModel'], $request['startRow'], $request['endRow']);
        $filteredAsset->getFinalQueryBuilder();
        $this->assetSearch($query, $request);
        $filteredAsset->setQuery($query);


        if (isset($request['field_search']))
            $filteredAsset->fieldSearch($request['field_search']);

        return $filteredAsset;

    }


        /**
     * check requst comes from report dashbord or not
     */
    public function isReportGrid($request){

        $result = false;
        $host_url = $request->header('origin');
        $referer =  $request->header('referer');
        if ($referer == $host_url.'/'.'Reports/GridReport')
            $result = true;

        return $result;
    }

    /**
     * store user fileter in redis cache
     */
    public function cacheFilterModel($request,$mga_categorization_id){

        cache()->set('RmAsset_'.Auth::id(),json_encode([
            'mga_categorization_id' => $mga_categorization_id
        ],true));
    }


    /**
     * @return array
     */
    protected function getOutcomeColumns(): array
    {
        $outcomeCriterias = MgaOutcomeCriteria::organizationScope()->pluck('title');
        $outcomeColumns = [];
        //add mga_outcome_critera dropdown
        $levelsMap = [
            3 => 'threeLevelsColor',
            4 => 'fourLevelsColor',
            5 => 'fiveLevelsColor'
        ];
        $mgaPorjectInformation = MgaProject::find(session('mga_project_id'))->information;
		$mgaMap = [ Mga::class,$levelsMap[$mgaPorjectInformation['IMPACT_LEVEL']['level']] ];
		$impactLevelsCollection = call_user_func($mgaMap);
		$impactLevels = [];
		foreach($impactLevelsCollection as $key => $impactLevel){
			$impactLevels[$mgaPorjectInformation['LEVEL_VALUATION']['impact_values'][$key]] = $impactLevel['title'];
		}
        foreach ($outcomeCriterias as $outcomeCriteria) {
            $outcomeColumns[] = [
                'headerName' => $outcomeCriteria,
                'field' => $outcomeCriteria,
                'cellRenderer' =>  'mgaOutcomeRmAssetRender',
                "cellRendererParams" => ['columnTitle' => $outcomeCriteria,'impactLevels' => $impactLevels],
                "filter" => false,
                "sortable" => false,
                'outcome' => true
            ];
        }
        return $outcomeColumns;
    }

    /**
     * @return array
     */
    protected function getGlobalFields(): array
    {
        $globalFields = [];

        $rmAssetFieldList = RmAssetField::organizationScope()
            ->select('id', 'title')
            ->where('type', 'all')
            ->get();
        foreach ($rmAssetFieldList as $rmAssetField) {
            $globalFields[] = [
                'headerName' => $rmAssetField->title,
                "field" => "field_id".strval($rmAssetField->id),
                'valueGetter' => "getFieldValue(data , $rmAssetField->id)",
                "sortable" => false,
                'field_id' => strval($rmAssetField->id)
            ];
        }
        // todo must add if ip ad global field

        return $globalFields;
    }


    /**
     * @return array
     */
    protected function getExcelGlobalFields(): array
    {
        $globalFields = [];

        $rmAssetFieldList = RmAssetField::organizationScope()
            ->select('id', 'title')
            ->where('type', 'all')
            ->get();
        foreach ($rmAssetFieldList as $rmAssetField) {
            $globalFields[] = [
                'headerName' => 'FAL('.$rmAssetField->title.')',
                "field" => "field_id".strval($rmAssetField->id),
                'field_id' => strval($rmAssetField->id)
            ];
        }
        // todo must add if ip ad global field

        return $globalFields;
    }


    /**
     * @param SearchAssetRequest $request
     * @return array
     */
    protected function getSpecialFieldsWithCategoryId(SearchAssetRequest $request): array
    {
        $specialFields = [];
        $key = 'filterCategories';
        if ((request()->has($key) && count(request()->get($key)) === 1) || isset($request->q['where']['eq']["view_rm_asset.rm_asset_categorization_id"])) {
            $categoryId = isset($request->q['where']['eq']["view_rm_asset.rm_asset_categorization_id"])
                ? $request->q['where']['eq']["view_rm_asset.rm_asset_categorization_id"]
                : request()->get($key)[0];

            $specialFieldList = RmAssetField::organizationScope()
                ->select('rm_asset_field.*', 'rm_asset_field.id as id')
                ->leftJoin('rm_asset_field_categorization as c', 'c.rm_asset_field_id', 'rm_asset_field.id')
                ->where('type', '!=', 'all')
                ->where('c.rm_asset_categorization_id', $categoryId)
                ->groupBy('rm_asset_field.id')
                ->get();

            foreach ($specialFieldList as $rmAssetField) {
                $specialFields[] = [
                    'headerName' => $rmAssetField->title,
                    "field" => "field_id".strval($rmAssetField->id),
                    'valueGetter' => "getFieldValue(data , $rmAssetField->id)",
                    "sortable" => false,
                    'field_id' => strval($rmAssetField->id)
                ];
            }
        }
        return $specialFields;
    }


    /**
     * @param SearchAssetRequest $request
     * @return array
     */
    protected function getExcelSpecialFieldsWithCategoryId(SearchAssetRequest $request): array
    {
        $specialFields = [];
        $key = 'filterCategories';
        if ((request()->has($key) && count(request()->get($key)) === 1) || isset($request->q['where']['eq']["view_rm_asset.rm_asset_categorization_id"])) {
            $categoryId = isset($request->q['where']['eq']["view_rm_asset.rm_asset_categorization_id"])
                ? $request->q['where']['eq']["view_rm_asset.rm_asset_categorization_id"]
                : request()->get($key)[0];

            $specialFieldList = RmAssetField::organizationScope()
                ->select('rm_asset_field.*', 'rm_asset_field.id as id')
                ->leftJoin('rm_asset_field_categorization as c', 'c.rm_asset_field_id', 'rm_asset_field.id')
                ->where('type', '!=', 'all')
                ->where('c.rm_asset_categorization_id', $categoryId)
                ->groupBy('rm_asset_field.id')
                ->get();

            foreach ($specialFieldList as $rmAssetField) {
                $specialFields[] = [
                    'headerName' => 'FCA('.$rmAssetField->title.')',
                    "field" => "field_id".strval($rmAssetField->id),
                    'valueGetter' => "getFieldValue(data , $rmAssetField->id)",
                    "sortable" => false,
                    'field_id' => strval($rmAssetField->id)
                ];
            }
        }
        return $specialFields;
    }


    /**
     * @return \string[][]
     */
    protected function defaultColumns(): array
    {
        $columns = [
            [
                'headerName' => "عنوان دارایی",
                'field' => "asset_title",
            ],
            [
                'headerName' => "کد مشخصه دارایی",
                'field' => "asset_code",
            ],
            [
                'headerName' => __('panel.asset_categorization'),
                'field' => "asset_categorization_title",
                "filter" => 'agSetColumnFilter',
                "filterParams" => [
                    'values' => checkReportGridAndDashboardReferer() ? RmAssetCategorization::pluck('title')->toArray() :RmAssetCategorization::organizationScope()->pluck('title')->toArray()
                ]
            ],
            [
                'headerName' => "نوع مالک دارایی",
                'field' => "owner_type",
            ],
            [
                'headerName' => "بخش",
                'field' => "asset_section_title",
                'filter'=> 'agSetColumnFilter',
                'filterParams' => [
                    "values" => Section::pluck('title')->prepend('NULL(بدون مقدار)'),
                    "suppressSorting" => true
                ],
                'menuTabs' => ['filterMenuTab'],
            ],
            [
                'headerName' => "سمت",
                'field' => "asset_post_title",
                'filter'=> 'agSetColumnFilter',
                'filterParams' => [
                    "values" => Post::pluck('title')->prepend('NULL(بدون مقدار)'),
                    "suppressSorting" => true
                ],
                'menuTabs' => ['filterMenuTab'],
            ],
            [
                'headerName' => "کاربر",
                'field' => "asset_user_title",
                'filter'=> 'agSetColumnFilter',
                'filterParams' => [
                    "values" => User::where('is_disable',0)->pluck('name')->prepend('NULL(بدون مقدار)'),
                    "suppressSorting" => true
                ],
                'menuTabs' => ['filterMenuTab'],
            ],
            [
                'headerName' => "مالک جانشین",
                'field' => "other_owner_name",
                'filter'=> 'agSetColumnFilter',
                'filterParams' => [
                    "values" => User::where('is_disable',0)->pluck('name')->prepend('NULL(بدون مقدار)'),
                    "suppressSorting" => true
                ],
                'menuTabs' => ['filterMenuTab'],
            ],
            [
                'headerName' => 'نوع دارایی',
                'field' => 'isServiceAsset',
                "filter" => 'agSetColumnFilter',
                "floatingFilter" => false,
                "filterParams" => "filterParams",
                "sortable" => false
            ],
            [
                'headerName' => "ایجاد کننده",
                'field' => "name_with_email",
            ],
            [
                'headerName' => "آیتم ریسک مدیریتی",
                'field' => "item_risks",
                'cellRenderer' =>  'itemRiskRender',
                "sortable" => false,
            ],
            [
                'headerName' => "توضیحات",
                'field' => "description",
            ],
            [
                "headerName" => "دارایی های والد",
                "field" => "assetparentList",
                'cellRenderer' =>  'assetRelationRender',
                "cellRendererParams" => ['type' => 'parent'],
                "parent" => true,
                "sortable" => false,
            ],
            [
                "headerName" => "دارایی های فرزند",
                "field" => "assetChildList",
                'cellRenderer' =>  'assetRelationRender',
                "cellRendererParams" => ['type' => 'child'],
                "child" => true,
                "sortable" => false,

            ],
            [
                "headerName" => "میزان انطباق",
                "field" => "gap_periods_avg_gap_percent",
                "sortable" => false,
                "filter" => false
            ],
        ];
        if(checkReportGridAndDashboardReferer ()){
            array_unshift($columns, [
                'headerName' => "عنوان سازمان",
                'field' => "asset_organization_title",
                "filter" => 'agSetColumnFilter',
                "filterParams" => [
                    'values' => Organization::pluck('title')->toArray()
                ]

            ]);
        }
        return $columns;
    }


        /**
     * @return \string[][]
     */
    protected function excelColumns(): array
    {
        $columns = [
            [
                'headerName' => __('panel.asset_categorization'),
                'field' => "asset_categorization_title",
            ],
            [
                'headerName' => "عنوان دارایی",
                'field' => "asset_title",
            ],
            [
                'headerName' => "کد مشخصه دارایی",
                'field' => "asset_code",
            ],
            [
                'headerName' => "بخش",
                'field' => "asset_section_title",
            ],
            [
                'headerName' => "سمت",
                'field' => "asset_post_title",
            ],
            [
                'headerName' => "کاربر",
                'field' => "asset_user_title",
            ],
            [
                'headerName' => "توضیحات",
                'field' => "description",
            ],
        ];
        return $columns;
    }


    public function otherExcelColumn()
    {

        $columns = [
            [
                'headerName' => "نوع مالک دارایی",
                'field' => "owner_type",
            ],
            [
                'headerName' => 'نوع دارایی',
                'field' => 'isServiceAsset',
            ],
            [
                'headerName' => "ایجاد کننده",
                'field' => "name_with_email",
            ],
            [
                'headerName' => "آیتم ریسک مدیریتی",
                'field' => "item_risks",
            ],
            [
                "headerName" => "دارایی های والد",
                "field" => "assetparentList",
            ],
            [
                "headerName" => "دارایی های فرزند",
                "field" => "assetChildList",

            ]
        ];
        if(checkReportGridAndDashboardReferer ()){
            array_unshift($columns, [
                'headerName' => "عنوان سازمان",
                'field' => "asset_organization_title",
            ]);
        };

        return $columns;
    }

}
