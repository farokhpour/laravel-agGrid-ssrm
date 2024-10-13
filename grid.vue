<template>
  <div
    class="d-flex flex-column h-100"
    style="padding-bottom: 40px"
    :style="{ 'min-height': minHeight }"
  >
    <div>
      <component
        :is="filterExtends"
        :pass-data="componentPassDataForFilterExtends"
      />
      <slot :filters="filters" name="search" />
      <slot :data="slotData" />
    </div>
    <div
      v-if="
        showTopGrid &&
        (exportExcell ||
          pagination ||
          showGlobalSearch ||
          exportExcelUrl ||
          exportExcelQuestionsUrl)
      "
      class="d-flex"
      style="margin: 5px"
    >
      <div style="flex: 1">
        <span v-if="pagination" style="margin-right: 15px">
          اندازه صفحه:
          <select
            v-model="pageSizeDefault"
            v-on:change="onPageSizeChanged"
            id="page-size"
          >
            <option
              v-for="size of [10, 50, 100, 1000, 10000]"
              :value="size + ''"
              :key="size"
            >
              {{ size }}
            </option>
          </select>
        </span>
      </div>

      <div class="text-center">
        <div class="btn-group-grid">
          <component :is="btnExtends" :pass-data="componentPassData" />

          <ClickableIcon v-for="action in topGridActions" v-bind="action" />

          <ClickableIcon
            v-if="exportExcell"
            class="btn-outline-success"
            icon="la la-file-excel-o"
            tooltip="دریافت اکسل"
            :handleClick="onExportExcel"
          />

          <ClickableIcon
            v-if="exportExcelUrl"
            class="btn-outline-success"
            icon="la la-file-excel-o"
            :href="exportExcelUrl"
            tooltip="دریافت اکسل جهت بارگذاری"
          />

          <ClickableIcon
            v-if="exportExcelQuestionsUrl"
            class="btn-outline-success"
            icon="la la-download"
            tooltip="دریافت اکسل پرسشنامه"
            :handleClick="handleExcelQuestionExport"
          />

          <ClickableIcon
            v-if="exportXmlUrl"
            class="btn-outline-warning"
            icon="la la-code"
            :href="exportXmlUrl"
            tooltip="دریافت خروجی XML"
          />

          <ClickableIcon
            v-if="canRemove && multiDelete && !onlyReport && withCheckbox"
            class="btn-outline-danger"
            icon="la la-trash"
            :handle-click="onDeleteSelected"
            tooltip="حذف موارد انتخاب شده"
          />

          <ClickableIcon
            v-if="canRemove && legacyMultiDelete && !onlyReport && withCheckbox"
            class="btn-outline-danger"
            icon="la la-trash"
            :handle-click="onRemoveSelected"
            tooltip="حذف موارد انتخاب شده"
          />

          <ClickableIcon
            class="btn-outline-info"
            icon="la la-refresh"
            :handle-click="restoreGridtoDefault"
            tooltip="پاک کردن تنظیمات جدول"
          />
        </div>
      </div>
    </div>

    <div class="grid-layout" style="width: 100%; flex: 1 1 auto; height: 1px">
      <ag-grid-vue
        v-if="columnDefs"
        class="ag-theme-balham"
        :rowSelection="rowSelection"
        :serverSideInfiniteScroll="true"
        :style="gridStyle"
        :columnDefs="columnDefs"
        :rowData="rowData"
        :defaultColDef="defaultColDef"
        :enableRtl="enableRtl"
        :pagination="pagination"
        :paginationAutoPageSize="false"
        :paginationPageSize="paginationPageSize"
        :modules="modules"
        :localeText="localeText"
        :rowClassRules="rowClassRules"
        :getRowClass="getRowClass"
        :animateRows="true"
        :rowModelType="rowModelType"
        :blockLoadDebounceMillis="1000"
        :suppressServerSideInfiniteScroll="false"
        :masterDetail="masterDetail"
        :isRowMaster="isRowMaster"
        :detailCellRendererParams="detailCellRendererParams"
        :detailRowAutoHeight="detailRowAutoHeight"
        :detailRowHeight="detailRowHeight"
        :sideBar="sideBar"
        :components="components"
        :defaultExportParams="defaultExportParams"
        :cacheBlockSize="cacheBlockSize"
        :isRowSelectable="isRowSelectable"
        :excelStyles="excelStyles"
        :getMainMenuItems="getMainMenuItems"
        :overlayLoadingTemplate="overlayLoadingTemplate"
        :overlayNoRowsTemplate="overlayNoRowsTemplate"
        :enableRangeSelection="false"
        @first-data-rendered="onFirstDataRendered"
        @grid-ready="onGridReady"
        @row-selected="onRowSelected"
        @selection-changed="onSelectionChanged"
        @filter-changed="onFilterChange"
        @sortChanged="onFilterChange"
        @column-resized="onSaveGridColumnState"
        @column-visible="onSaveGridColumnState"
        @column-moved="onSaveGridColumnState"
        @columnPinned="onSaveGridColumnState"
        @paginationChanged="onSaveGridColumnState"
        @cell-edit-request="onCellEditRequest"
        :suppressRowClickSelection="suppressRowClickSelection"
        :enableChartToolPanelsButton="true"
        :groupDisplayType="groupDisplayType"
        :groupRowRendererParams="groupRowRendererParams"
        :getContextMenuItems="() => []"
        :rowGroupPanelShow="rowGroupPanelShow"
        :autoGroupColumnDef="autoGroupColumnDef"
        :aggFuncs="aggFuncs"
        :suppressAggFuncInHeader="true"
        :suppressClickEdit="true"
        :stopEditingWhenCellsLoseFocus="true"
        :tooltipShowDelay="500"
        :readOnlyEdit="readOnlyEdit"
        :getRowId="getRowId"
        :getRowHeight="getRowHeight"
      />
    </div>

    <portal-target name="grid-modal-destination" />
  </div>
</template>

<script>
// Third-Parties
import 'ag-grid-community/styles/ag-grid.css'
import 'ag-grid-community/styles/ag-theme-balham.css'
import { AgGridVue } from 'ag-grid-vue'
import _ from 'lodash'
import 'floating-vue/dist/style.css'

// Components
import DefaultActions from '../components/Actions/default.vue'
import MainActions from '../components/Actions/asset/MainActions.vue'
import SideActions from '../components/Actions/asset/SideActions.vue'
import ClickableIcon from '../../utils/ClickableIcon.vue'
import MgaExportAssessmentModal from '../../Mga/MgaExportAssessmentModal.vue'

import AG_GRID_LOCALE_FA from '../localisation/locale.fa.js'

// Actions
import PostActions from '../components/Actions/postActions'
import SectionActions from '../components/Actions/sectionActions'
import ProjectActions from '../components/Actions/projectActions'
import ProcessManagementActions from '../components/Actions/ProcessManagementActions'
import ProcessManagementDutyActions from '../components/Actions/ProcessManagementDutyActions'
import ProcessManagementAllRequestActions from '../components/Actions/ProcessManagementAllRequestActions'
import ProcessManagementFollowUpActions from '../components/Actions/ProcessManagementFollowUPActions'
import SdProjectActions from '../components/Actions/sdProjectActions'
import AuProjectActions from '../components/Actions/auProjectActions'
import OaProjectActions from '../components/Actions/oaProjectActions'
import EmProjectActions from '../components/Actions/emProjectActions'
import MmProjectActions from '../components/Actions/mmProjectActions'
import MgaReportHistoryActions from '../components/Actions/mgaReportHistoryActions'
import * as componentRegister from '../components'
import PublicSettingActions from '../components/Actions/publicSettingActions'
import objectRender from '../components/objectRender.vue'

// Cell Renderer
import TrBadgeRenderer from '../../grids/components/CellRenderer/TrBadgeRenderer.vue'
import TrHistoryStatusRenderer from '../../grids/components/CellRenderer/TrHistory/TrHistoryStatusRenderer.vue'
import TrHistoryExploitRenderer from '../../grids/components/CellRenderer/TrHistory/TrHistoryExploitRenderer.vue'
import AgBadgeCellRenderer from '../components/agBadgeCellRenderer'

// Cell Editors
import SimpleEditableCell from '../components/CellRenderer/Editing/SimpleEditableCell.vue'
import agRichSelectCellEditorValues from '../components/agRichSelectCellEditorValues.vue'
import MultiSelectEditor from '../components/Editors/MultiSelectEditor.vue'

// ToolTip Renderer
import ArrayToolTipRenderer from '../components/ToolTip/ArrayToolTipRenderer.vue'
import StringToolTipRenderer from '../components/ToolTip/StringToolTipRenderer.vue'

// Filters
import FloatingDateComponent from '../components/Filters/Date/FloatingDateComponent.vue'
import DateComponentVue from '../components/Filters/Date/DateComponent.vue'

// Mixins
import selectionMixin from './selectionMixin.js'
import isAdminMixin from '../../utils/mixins/isAdminMixin.js'

// Utils
import {
  saveGridChanges,
  getGridStateFromLocal,
  clearSavedGridState,
  generateRandomId,
  getColumnIndex,
  handleMultiDelete,
  handleExportExcel,
  fetchRows,
  prepareGridColumns,
} from '../../../utilities/gridHelper.js'
import {
  EDIT_BUTTON,
  DELETE_BUTTON,
} from '../components/Actions/asset/actionButtons.js'

export default {
  mixins: [selectionMixin, isAdminMixin],
  props: {
    // url to get rows data from. can be used on both ssrm and client mode
    dataRoute: {
      type: String,
      required: false,
    },
    // url to get columns data from.
    columnRoute: {
      type: String,
      required: false,
    },
    sideBar: {
      default: function () {
        return {
          toolPanels: [
            {
              id: 'columns',
              labelDefault: 'Columns',
              labelKey: 'columns',
              iconKey: 'columns',
              toolPanel: 'agColumnsToolPanel',
              toolPanelParams: {
                suppressPivotMode: true,
                suppressRowGroups: false,
                suppressValues: true,
              },
            },
          ],
        }
      },
    },
    enableRtl: {
      default: true,
      type: Boolean,
    },
    pagination: {
      default: true,
      type: Boolean,
    },
    isMasterDetail: {
      default: false,
      type: Boolean,
    },
    pageSize: {
      default: 100,
    },
    flex: {
      type: Boolean,
      default: false,
    },
    actionName: {
      type: String,
      default: 'DefaultActions',
    },
    showGlobalSearch: {
      type: Boolean,
      default: true,
    },
    exportExcell: {
      type: Boolean,
      default: true,
    },
    exportExcelUrl: {
      type: String,
    },
    exportExcelQuestionsUrl: {
      type: String,
    },
    // a boolean to determine whether to show a modal on export excel
    selectExcelType: {
      type: Boolean,
      default: false,
    },
    exportXmlUrl: {
      type: String,
    },
    excellName: {
      type: String,
      default: 'export.xlsx',
    },
    maxWidthAction: {
      default: 100,
    },
    readOnlyEdit: {
      type: Boolean,
      default: true,
    },
    multiDelete: {
      type: Boolean,
      default: false,
    },
    // Previously used
    legacyMultiDelete: {
      type: Boolean,
      default: true,
    },
    // Whether to use client actions
    showActions: {
      default: true,
      type: Boolean,
    },
    // Whether to show any action
    onlyReport: {
      type: Boolean,
      default: false,
    },
    withCheckbox: {
      type: Boolean,
      default: true,
    },
    saveState: {
      type: Boolean,
      default: true,
    },
    showPanelGroup: {
      type: Boolean,
      default: false,
    },
    updateColumnRoute: {
      type: String,
      default: '',
    },
    // Whether grid is server side model
    isSsrm: {
      type: Boolean,
      default: false,
    },
    // cacheBlockSize used with ssrm mode
    cacheBlockSize: {
      // type: Number,
      default: 100,
    },
    // Client-Side data to show
    rows: {
      type: Array,
      default: () => [],
    },
    columns: {
      type: Array,
      // default: () => [],
    },
    getGridOptions: {
      type: Function,
      required: false,
    },
    getGridInstance: {
      type: Function,
      required: false,
    },
    showTopGrid: {
      type: Boolean,
      default: true,
    },
    minHeight: {
      type: String,
      default: '610px',
    },
  },
  data() {
    return {
      topGridActions: [],
      readyToSave: false,
      paginationPageSize: 100,
      gridStyle: {
        width: '100%',
        height: '100%',
      },
      api: {
        route: null,
        params: null,
      },
      filters: {},
      canRemove: false,
      search: '',
      gridOptions: null,
      columnDefs: null,
      rowData: null,
      gridApi: null,
      columnApi: null,
      defaultColDef: null,
      autoGroupColumnDef: null,
      localeText: null,
      suppressRowClickSelection: true,
      rowModelType: null,
      masterDetail: this.isMasterDetail,
      isRowMaster: null,
      detailCellRendererParams: null,
      rowClassRules: null,
      defaultExportParams: null,
      excellParams: {
        fileName: this.excellName,
      },
      excelStyles: null,
      modules: [],
      btnExtends: '',
      componentPassData: {},
      filterExtends: '',
      componentPassDataForFilterExtends: {},
      isRowSelectable: null,
      overlayLoadingTemplate: null,
      overlayNoRowsTemplate: null,
      pageSizeDefault: 100,
      groupDisplayType: null,
      groupRowRendererParams: null,
      aggFuncList: ['sum', 'min', 'max', 'count', 'avg'],
      aggFunc: 'avg',
      rowGroupPanelShow: this.showPanelGroup ? 'always' : null,
      maxRowIndex: 90,
      detailRowAutoHeight: false,
      detailRowHeight: 200,
      serverSideFilterParams: null,
    }
  },
  computed: {
    slotData: function () {},
  },
  components: {
    Option,
    AgGridVue,
    DefaultActions,
    MainActions,
    SideActions,
    PostActions,
    SectionActions,
    ProjectActions,
    SdProjectActions,
    OaProjectActions,
    ProcessManagementActions,
    ProcessManagementDutyActions,
    ProcessManagementAllRequestActions,
    ProcessManagementFollowUpActions,
    AuProjectActions,
    EmProjectActions,
    MmProjectActions,
    MgaReportHistoryActions,
    PublicSettingActions,
    SimpleEditableCell,
    agRichSelectCellEditorValues,
    MultiSelectEditor,
    AgBadgeCellRenderer,
    ArrayToolTipRenderer,
    StringToolTipRenderer,
    objectRender,
    ClickableIcon,
    TrBadgeRenderer,
    TrHistoryStatusRenderer,
    TrHistoryExploitRenderer,
    // filter: 'agDateColumnFilter' can be used in columnDef to use this component
    agDateInput: DateComponentVue,
    FloatingDateComponent,
  },
  async created() {
    this.getRowId = (params) => {
      if (params.data.uuid) return params.data.uuid
      else if (params.data.id) return params.data.id
      else return generateRandomId()
    }

    this.aggFuncs = {}
    if (
      (this.legacyMultiDelete || this.multiDelete) &&
      (await this.checkAdminModule())
    )
      this.canRemove = true

    if (this.canRemove) {
      const formElement = document.querySelector('form#destroy-item-selected')
      if (formElement != null && formElement.action)
        this.removeUrl = formElement.action
    }

    this.overlayLoadingTemplate =
      '<span class="ag-overlay-loading-center">اطلاعات در حال بارگذاری است. لطفا صبر کنید...</span>'
    this.overlayNoRowsTemplate =
      '<span class="g-overlay-loading-center">اطلاعاتی در این صفحه موجود نیست</span>'
  },
  watch: {
    rows(newValue, oldValue) {
      this.handleClientRows()
    },
  },
  methods: {
    // Value has changed after editing. Only fires when doing Read Only Edits, ie readOnlyEdit=true
    onCellEditRequest(event) {
      if (!this.updateColumnRoute.trim()) {
        return
      }

      this.handleCellEdit(event, this.updateColumnRoute)
    },
    /**
     * @param {object} event
     * @param {string} apiUrl
     * @returns {void}
     * */
    handleCellEdit(event, apiUrl) {
      const field = event.colDef.field
      const newValue = event.newValue

      window.axios
        .post(apiUrl, {
          id: event.data.id,
          frm: {
            field,
            value: newValue,
          },
        })
        .then(({ data }) => {
          const oldData = event.data
          const newData = oldData
          const value = 'value' in data ? data.value : newValue

          _.set(newData, field, value)

          const tx = {
            update: [this.beforeUpdateData(newData, field)],
          }

          if (this.rowModelType === 'serverSide')
            event.api.applyServerSideTransaction(tx)
          else event.api.applyTransaction(tx)
        })
        .catch((e) => {
          toastr.options.positionClass = 'toast-top-left'
          toastr.error('خطایی رخ داده است')
        })
    },
    onFirstDataRendered() {
      /**
       * باگی وجود دارد. زمانی که هیچ دیتایی برای نمایش وجود نداشته باشد. این تابع
       * اجرا نمیشود
       */
      // A Hack to avoid resize events on flex grids & page size event
      setTimeout(() => {
        this.readyToSave = true
      }, 1000)
    },

    restoreGridtoDefault() {
      this.pageSizeDefault = 100
      this.gridApi.paginationSetPageSize(100)

      clearSavedGridState()
      // debugger

      // this.columnApi.applyColumnState({
      //   state: this.columnDefs,
      //   applyOrder: true, // Order of Columns
      //   defaultState: {
      //     // important to say 'null' as undefined means 'do nothing'
      //     sort: null, // sort
      //     pinned: undefined, // pin
      //     hide: false, // visibility
      //     // flex: 1, // resize
      //   },
      // })
      this.columnApi.resetColumnState()

      // Reset Filter State
      this.gridApi.setFilterModel(null)
    },
    onFilterChange(gridOptions) {
      // Save filter model in vuex
      this.$store.dispatch(
        'GridModule/setFilterModel',
        gridOptions.api.getFilterModel()
      )

      this.onSaveGridColumnState(gridOptions)
    },
    onSaveGridColumnState: function (gridOptions) {
      saveGridChanges(this, gridOptions)
    },
    searchFormChanged(e) {
      this.filters = $(e.target.closest('form')).serialize()

      if (this.rowModelType == 'serverSide')
        this.gridApi.refreshServerSideStore({ purge: false })
    },
    onGridReady(params) {
      this.gridApi = params.api
      this.columnApi = params.columnApi

      if (this.getGridOptions) this.getGridOptions(params)
      if (this.getGridInstance) this.getGridInstance(this)

      if (this.onAfterGridReady) this.onAfterGridReady()

      this.gridApi.closeToolPanel()

      if (!this.masterDetail)
        this.gridApi.addEventListener('modelUpdated', this.modelUpdated)

      // console.log(`this.columnDefs:`)
      // console.log(this.columnDefs)

      this.applyGridSetting()

      // SSRM
      if (this.rowModelType == 'serverSide') {
        const datasource = {
          getRows: (params) => {
            this.serverSideFilterParams = params.request
            const customFilters = this.applyCustomFilters
              ? this.applyCustomFilters()
              : {}

            this.fetchPostData({ ...params.request, ...customFilters }).then(
              ({ rowData, rowCount }) => {
                params.success({
                  rowData,
                  rowCount,
                })
              }
            )
          },
        }

        this.gridApi.setServerSideDatasource(datasource)
      } else this.handleClientRows()
    },

    handleClientRows() {
      this.fetchData().then((data) => {
        this.updateData(data)
      })
    },
    fetchPostData(params) {
      return fetchRows(this.dataRoute, params)
    },
    fetchData() {
      return new Promise((resolve, reject) => {
        if (!this.dataRoute) return resolve(this.getRowData())

        this.$axios
          .get(this.dataRoute)
          .then(({ data }) => {
            resolve(data)
          })
          .catch((e) => {
            reject(e)
          })
      })
    },

    getRowData() {
      return this.rows
    },

    updateData(data) {
      // if data exists & gridApi is loaded
      if (data && this.gridApi) {
        this.gridApi.setRowData(data)
      }
    },

    handleExcelQuestionExport() {
      if (this.selectExcelType) {
        this.$modal.show(MgaExportAssessmentModal, {
          exportExcelQuestionsUrl: this.exportExcelQuestionsUrl,
        })
        //
      } else window.location.href = this.exportExcelQuestionsUrl
    },

    onFilterTextBoxChanged() {
      this.gridApi.setQuickFilter(
        document.getElementById('filter-text-box').value
      )
    },
    setDefaultColDef() {
      const filterParams = {}

      // limit filter option for ssrm grid
      if (this.isSsrm) filterParams.filterOptions = ['contains']

      return {
        flex: this.flex ? 1 : 0,
        sortable: true,
        resizable: true,

        filter: 'agTextColumnFilter',
        floatingFilter: true,

        // Disable menu tabs
        menuTabs: [
          // 'filterMenuTab',
          // 'generalMenuTab' ,
          // 'columnsMenuTab'
        ],
        filterParams,
      }
    },
    getMainMenuItems(params) {
      const newParams = params.defaultItems.filter((d) => d != 'pinSubMenu')
      return newParams
    },
    columnAction() {
      return {
        maxWidth: this.maxWidthAction,
        // cellRenderer: this.actionName,

        cellRenderer: 'MainActions',
        cellRendererParams: {
          btnList: [
            {
              ...EDIT_BUTTON,
              showCondition: (params) => !!params.data.editPath,
            },
            {
              ...DELETE_BUTTON,
              showCondition: (params) => !!params.data.removePath,
              method: 'get',
            },
          ],
        },
      }
    },
    columnRowIndex() {
      return { ...getColumnIndex(this) }
    },

    onExportExcel() {
      handleExportExcel(this, {})
    },

    onRemoveSelected() {
      handleMultiDelete(this.gridApi, {
        url: this.removeUrl,
        method: 'delete',
      })
    },

    searchHandler() {
      this.gridApi.setQuickFilter(this.search)
    },
    onPageSizeChanged(event) {
      this.pageSizeDefault = event.target.value
      this.gridApi.paginationSetPageSize(event.target.value)
    },
    callBeforeMount() {
      const columns = []

      this.columnDefs.map((column) => {
        column = this.filterDate(column)
        columns.push(this.mapColumn(column))
      })

      this.columnDefs = columns
    },

    mapColumn(column) {
      return column
    },
    filterDate(column) {
      if (this.updateColumnRoute.trim() !== '' && column.editable === true)
        column = { cellRenderer: 'SimpleEditableCell', ...column }

      return column
    },

    setRowClassRules() {
      return {}
    },
    getRowClass() {
      return ''
    },
    setFrameworkComponents() {
      return {}
    },
    setRowModelType() {
      if (this.isSsrm) return 'serverSide'
      else return 'clientSide'
    },
    setDetailCellRendererParams() {
      return {}
    },
    registerComponentGrid() {
      return {
        ...componentRegister,
        ...this.setFrameworkComponents(),
      }
    },
    setIsRowMaster(dataItem) {
      return false
    },
    setDefaultExportParams() {
      return null
    },
    applyGridSetting() {
      // *** RESTORE FILTER STATE
      const savedState = getGridStateFromLocal(window.location.pathname)

      if (this.saveState && savedState) {
        // Column State
        this.columnApi.applyColumnState({
          state: savedState.colState,
          applyOrder: true, // Order of Columns
          defaultState: {
            // important to say 'null' as undefined means 'do nothing'
            sort: null, // sort
            pinned: undefined, // pin
            hide: false, // visibility
            // flex: 1, // resize
          },
        })

        // Filter State
        this.gridApi.setFilterModel(savedState.filterModel)

        // Pagination Size
        this.pageSizeDefault = savedState.pageSize
        this.gridApi.paginationSetPageSize(savedState.pageSize)
      }
    },
    beforeUpdateData(newData, field) {
      return newData
    },
    getRowHeight(params) {
      return
    },
  },
  beforeMount() {
    // console.log("beforeMount")

    this.gridOptions = {}

    this.components = this.registerComponentGrid()

    this.localeText = AG_GRID_LOCALE_FA

    this.defaultColDef = this.setDefaultColDef()

    this.rowClassRules = this.setRowClassRules()

    this.rowModelType = this.setRowModelType()
    this.detailCellRendererParams = this.setDetailCellRendererParams

    this.defaultExportParams = this.setDefaultExportParams()

    this.isRowMaster = this.setIsRowMaster

    // Handle ColumnDefs
    // Columns from Props
    if (this.columns) this.columnDefs = [this.columnRowIndex(), ...this.columns]
    // Columns from getColumnDefs method
    else if (!this.columnRoute && this.getColumnDefs)
      this.columnDefs = this.getColumnDefs()
    // Columns from setColumnDefsAsync method
    else if (this.setColumnDefsAsync) this.setColumnDefsAsync()
    // Columns from api request to columnRoute
    else if (this.columnRoute) {
      this.$axios.get(this.columnRoute).then(({ data }) => {
        prepareGridColumns(this, data)
      })
      // if there is no columnRoute and getColumnDefs
    } else this.columnDefs = []
  },
  mounted() {
    const elForm = this.$el.querySelector('#searchForm')
    if (elForm) {
      $(elForm).change(this.searchFormChanged)
      $(elForm.querySelector('#searchFormBtn')).click((e) => {
        toastr.options.positionClass = 'toast-top-left'

        axios
          .post(elForm.action, this.submitForm())
          .then((res) => {
            toastr.success('اطلاعات با موفقیت ثبت شد')
          })
          .catch((err) => console.log(err))
      })
    }

    // For blade form
    this.$el.addEventListener('grid:selected_node', (e) => {
      e.detail.getSelectedId = () => {
        return this.gridApi.getSelectedRows().map((item) => item.id)
      }
    })
  },
}

window.cell = function cell(text, styleId) {
  return {
    styleId: styleId,
    data: {
      type: /^\d+$/.test(text) ? 'Number' : 'String',
      value: String(text),
    },
  }
}
</script>

<style type="text/css">
.custom-tooltip {
  position: absolute;
  width: 170px;
  height: auto;
  border: 1px solid cornflowerblue;
  overflow: hidden;
  pointer-events: none;
  transition: opacity 1s;
  background-color: white;
  padding: 5px;
}

.custom-tooltip.ag-tooltip-hiding {
  opacity: 0;
}

.custom-tooltip p {
  margin: 5px;
  white-space: nowrap;
}

.grid.dropdown-menu.show {
  display: flex;
  align-items: stretch;
  flex-direction: column;
}

.my-chart .ag-chart {
  min-height: 200px;
}

.ag-theme-balham {
  --ag-header-foreground-color: white;
  --ag-header-background-color: var(--menu-primary-color);
  --ag-foreground-color: black;
  --ag-font-size: 10px !important;
}

.btn-group-grid .btn,
.btn-group-grid .btn {
  padding: 0.25rem 0.5rem;
}

.btn-group-grid .btn i,
.btn-group-grid .btn i {
  font-size: 16px;
}

.ag-theme-balham .ag-rich-select-row {
  padding-right: 12px;
  padding-left: initial;
}

.grid-action-btn {
  padding: 2px !important;
}

.grid-action-btn i {
  font-size: 1.3rem !important;
}

.ag-theme-balham .ag-body-viewport {
  font-weight: bold;
}

.ag-theme-balham .ag-floating-filter-body {
  color: black;
  font-weight: bold;
}

.ag-header-cell-menu-button {
  color: white !important;
}
</style>
