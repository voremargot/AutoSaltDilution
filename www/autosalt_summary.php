<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *                                   ATTENTION!
 * If you see this message in your browser (Internet Explorer, Mozilla Firefox, Google Chrome, etc.)
 * this means that PHP is not properly installed on your web server. Please refer to the PHP manual
 * for more details: http://php.net/manual/install.php 
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

    include_once dirname(__FILE__) . '/components/startup.php';
    include_once dirname(__FILE__) . '/components/application.php';
    include_once dirname(__FILE__) . '/' . 'authorization.php';


    include_once dirname(__FILE__) . '/' . 'database_engine/pgsql_engine.php';
    include_once dirname(__FILE__) . '/' . 'components/page/page_includes.php';

    function GetConnectionOptions()
    {
        $result = GetGlobalConnectionOptions();
        $result['client_encoding'] = 'utf8';
        GetApplication()->GetUserAuthentication()->applyIdentityToConnectionOptions($result);
        return $result;
    }

    
    
    
    // OnBeforePageExecute event handler
    
    
    
    class chrl_autosalt_summaryPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('Autosalt Summary');
            $this->SetMenuLabel('Autosalt Summary');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."autosalt_summary"');
            $this->dataset->addFields(
                array(
                    new IntegerField('eventid', true, true),
                    new IntegerField('siteid', true, true),
                    new IntegerField('periodid'),
                    new DateField('date', true),
                    new IntegerField('temp'),
                    new TimeField('start_time'),
                    new IntegerField('stage_start'),
                    new IntegerField('stage_average'),
                    new IntegerField('stage_min'),
                    new IntegerField('stage_max'),
                    new IntegerField('stage_std'),
                    new StringField('stage_dir'),
                    new IntegerField('salt_volume'),
                    new IntegerField('discharge_avg'),
                    new IntegerField('uncert'),
                    new StringField('flags'),
                    new StringField('ecb'),
                    new IntegerField('mixing'),
                    new StringField('notes')
                )
            );
            $this->dataset->AddLookupField('siteid', 'chrl.site_description', new IntegerField('siteid'), new IntegerField('siteid', false, false, false, false, 'LA1', 'LT1'), 'LT1');
            $this->dataset->AddLookupField('periodid', 'chrl.barrel_periods', new IntegerField('periodid'), new IntegerField('periodid', false, false, false, false, 'LA2', 'LT2'), 'LT2');
        }
    
        protected function DoPrepare() {
    
        }
    
        protected function CreatePageNavigator()
        {
            $result = new CompositePageNavigator($this);
            
            $partitionNavigator = new PageNavigator('pnav', $this, $this->dataset);
            $partitionNavigator->SetRowsPerPage(15);
            $result->AddPageNavigator($partitionNavigator);
            
            return $result;
        }
    
        protected function CreateRssGenerator()
        {
            return null;
        }
    
        protected function setupCharts()
        {
    
        }
    
        protected function getFiltersColumns()
        {
            return array(
                new FilterColumn($this->dataset, 'eventid', 'eventid', 'EventID'),
                new FilterColumn($this->dataset, 'siteid', 'LA1', 'SiteID'),
                new FilterColumn($this->dataset, 'periodid', 'LA2', 'PeriodID'),
                new FilterColumn($this->dataset, 'date', 'date', 'Date'),
                new FilterColumn($this->dataset, 'temp', 'temp', 'Temp'),
                new FilterColumn($this->dataset, 'start_time', 'start_time', 'Start Time'),
                new FilterColumn($this->dataset, 'stage_start', 'stage_start', 'Stage Start'),
                new FilterColumn($this->dataset, 'stage_average', 'stage_average', 'Stage Average'),
                new FilterColumn($this->dataset, 'stage_min', 'stage_min', 'Stage Min'),
                new FilterColumn($this->dataset, 'stage_max', 'stage_max', 'Stage Max'),
                new FilterColumn($this->dataset, 'stage_std', 'stage_std', 'Stage Std'),
                new FilterColumn($this->dataset, 'stage_dir', 'stage_dir', 'Stage Dir'),
                new FilterColumn($this->dataset, 'salt_volume', 'salt_volume', 'Salt Volume'),
                new FilterColumn($this->dataset, 'discharge_avg', 'discharge_avg', 'Discharge Avg'),
                new FilterColumn($this->dataset, 'uncert', 'uncert', 'Uncert'),
                new FilterColumn($this->dataset, 'flags', 'flags', 'Flags'),
                new FilterColumn($this->dataset, 'ecb', 'ecb', 'ECB'),
                new FilterColumn($this->dataset, 'mixing', 'mixing', 'Mixing'),
                new FilterColumn($this->dataset, 'notes', 'notes', 'Notes')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['eventid'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['periodid'])
                ->addColumn($columns['date'])
                ->addColumn($columns['temp'])
                ->addColumn($columns['start_time'])
                ->addColumn($columns['stage_start'])
                ->addColumn($columns['stage_average'])
                ->addColumn($columns['stage_min'])
                ->addColumn($columns['stage_max'])
                ->addColumn($columns['stage_std'])
                ->addColumn($columns['stage_dir'])
                ->addColumn($columns['salt_volume'])
                ->addColumn($columns['discharge_avg'])
                ->addColumn($columns['uncert'])
                ->addColumn($columns['flags'])
                ->addColumn($columns['ecb'])
                ->addColumn($columns['mixing'])
                ->addColumn($columns['notes']);
        }
    
        protected function setupColumnFilter(ColumnFilter $columnFilter)
        {
    
        }
    
        protected function setupFilterBuilder(FilterBuilder $filterBuilder, FixedKeysArray $columns)
        {
    
        }
    
        protected function AddOperationsColumns(Grid $grid)
        {
            $actions = $grid->getActions();
            $actions->setCaption($this->GetLocalizerCaptions()->GetMessageString('Actions'));
            $actions->setPosition(ActionList::POSITION_RIGHT);
            
            if ($this->GetSecurityInfo()->HasViewGrant())
            {
                $operation = new LinkOperation($this->GetLocalizerCaptions()->GetMessageString('View'), OPERATION_VIEW, $this->dataset, $grid);
                $operation->setUseImage(true);
                $actions->addOperation($operation);
            }
            
            if ($this->GetSecurityInfo()->HasEditGrant())
            {
                $operation = new LinkOperation($this->GetLocalizerCaptions()->GetMessageString('Edit'), OPERATION_EDIT, $this->dataset, $grid);
                $operation->setUseImage(true);
                $actions->addOperation($operation);
                $operation->OnShow->AddListener('ShowEditButtonHandler', $this);
            }
            
            if ($this->GetSecurityInfo()->HasDeleteGrant())
            {
                $operation = new LinkOperation($this->GetLocalizerCaptions()->GetMessageString('Delete'), OPERATION_DELETE, $this->dataset, $grid);
                $operation->setUseImage(true);
                $actions->addOperation($operation);
                $operation->OnShow->AddListener('ShowDeleteButtonHandler', $this);
                $operation->SetAdditionalAttribute('data-modal-operation', 'delete');
                $operation->SetAdditionalAttribute('data-delete-handler-name', $this->GetModalGridDeleteHandler());
            }
        }
    
        protected function AddFieldColumns(Grid $grid, $withDetails = true)
        {
            //
            // View column for eventid field
            //
            $column = new NumberViewColumn('eventid', 'eventid', 'EventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for periodid field
            //
            $column = new NumberViewColumn('periodid', 'LA2', 'PeriodID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Barrel Periods Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for date field
            //
            $column = new DateTimeViewColumn('date', 'date', 'Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for temp field
            //
            $column = new NumberViewColumn('temp', 'temp', 'Temp', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('degree C');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for start_time field
            //
            $column = new DateTimeViewColumn('start_time', 'start_time', 'Start Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for stage_start field
            //
            $column = new NumberViewColumn('stage_start', 'stage_start', 'Stage Start', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('cm');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for stage_average field
            //
            $column = new NumberViewColumn('stage_average', 'stage_average', 'Stage Average', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('cm');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for stage_min field
            //
            $column = new NumberViewColumn('stage_min', 'stage_min', 'Stage Min', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('cm');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for stage_max field
            //
            $column = new NumberViewColumn('stage_max', 'stage_max', 'Stage Max', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('cm');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for stage_std field
            //
            $column = new NumberViewColumn('stage_std', 'stage_std', 'Stage Std', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('cm');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for stage_dir field
            //
            $column = new TextViewColumn('stage_dir', 'stage_dir', 'Stage Dir', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for salt_volume field
            //
            $column = new NumberViewColumn('salt_volume', 'salt_volume', 'Salt Volume', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('L');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for discharge_avg field
            //
            $column = new NumberViewColumn('discharge_avg', 'discharge_avg', 'Discharge Avg', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('m3/s');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for uncert field
            //
            $column = new NumberViewColumn('uncert', 'uncert', 'Uncert', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('%');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for flags field
            //
            $column = new TextViewColumn('flags', 'flags', 'Flags', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for ecb field
            //
            $column = new TextViewColumn('ecb', 'ecb', 'ECB', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for mixing field
            //
            $column = new NumberViewColumn('mixing', 'mixing', 'Mixing', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('%');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
        }
    
        protected function AddSingleRecordViewColumns(Grid $grid)
        {
            //
            // View column for eventid field
            //
            $column = new NumberViewColumn('eventid', 'eventid', 'EventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for periodid field
            //
            $column = new NumberViewColumn('periodid', 'LA2', 'PeriodID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for date field
            //
            $column = new DateTimeViewColumn('date', 'date', 'Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for temp field
            //
            $column = new NumberViewColumn('temp', 'temp', 'Temp', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for start_time field
            //
            $column = new DateTimeViewColumn('start_time', 'start_time', 'Start Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for stage_start field
            //
            $column = new NumberViewColumn('stage_start', 'stage_start', 'Stage Start', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for stage_average field
            //
            $column = new NumberViewColumn('stage_average', 'stage_average', 'Stage Average', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for stage_min field
            //
            $column = new NumberViewColumn('stage_min', 'stage_min', 'Stage Min', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for stage_max field
            //
            $column = new NumberViewColumn('stage_max', 'stage_max', 'Stage Max', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for stage_std field
            //
            $column = new NumberViewColumn('stage_std', 'stage_std', 'Stage Std', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for stage_dir field
            //
            $column = new TextViewColumn('stage_dir', 'stage_dir', 'Stage Dir', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for salt_volume field
            //
            $column = new NumberViewColumn('salt_volume', 'salt_volume', 'Salt Volume', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for discharge_avg field
            //
            $column = new NumberViewColumn('discharge_avg', 'discharge_avg', 'Discharge Avg', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for uncert field
            //
            $column = new NumberViewColumn('uncert', 'uncert', 'Uncert', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for flags field
            //
            $column = new TextViewColumn('flags', 'flags', 'Flags', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for ecb field
            //
            $column = new TextViewColumn('ecb', 'ecb', 'ECB', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for mixing field
            //
            $column = new NumberViewColumn('mixing', 'mixing', 'Mixing', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
        }
    
        protected function AddEditColumns(Grid $grid)
        {
            //
            // Edit column for flags field
            //
            $editor = new TextEdit('flags_edit');
            $editor->SetMaxLength(30);
            $editColumn = new CustomEditColumn('Flags', 'flags', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for ecb field
            //
            $editor = new TextEdit('ecb_edit');
            $editor->SetMaxLength(10);
            $editColumn = new CustomEditColumn('ECB', 'ecb', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for notes field
            //
            $editor = new TextAreaEdit('notes_edit', 50, 8);
            $editColumn = new CustomEditColumn('Notes', 'notes', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
        }
    
        protected function AddMultiEditColumns(Grid $grid)
        {
            //
            // Edit column for flags field
            //
            $editor = new TextEdit('flags_edit');
            $editor->SetMaxLength(30);
            $editColumn = new CustomEditColumn('Flags', 'flags', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for ecb field
            //
            $editor = new TextEdit('ecb_edit');
            $editor->SetMaxLength(10);
            $editColumn = new CustomEditColumn('ECB', 'ecb', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for notes field
            //
            $editor = new TextAreaEdit('notes_edit', 50, 8);
            $editColumn = new CustomEditColumn('Notes', 'notes', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
        }
    
        protected function AddInsertColumns(Grid $grid)
        {
    
            $grid->SetShowAddButton(true && $this->GetSecurityInfo()->HasAddGrant());
        }
    
        private function AddMultiUploadColumn(Grid $grid)
        {
    
        }
    
        protected function AddPrintColumns(Grid $grid)
        {
            //
            // View column for eventid field
            //
            $column = new NumberViewColumn('eventid', 'eventid', 'EventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for periodid field
            //
            $column = new NumberViewColumn('periodid', 'LA2', 'PeriodID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for date field
            //
            $column = new DateTimeViewColumn('date', 'date', 'Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddPrintColumn($column);
            
            //
            // View column for temp field
            //
            $column = new NumberViewColumn('temp', 'temp', 'Temp', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for start_time field
            //
            $column = new DateTimeViewColumn('start_time', 'start_time', 'Start Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddPrintColumn($column);
            
            //
            // View column for stage_start field
            //
            $column = new NumberViewColumn('stage_start', 'stage_start', 'Stage Start', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for stage_average field
            //
            $column = new NumberViewColumn('stage_average', 'stage_average', 'Stage Average', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for stage_min field
            //
            $column = new NumberViewColumn('stage_min', 'stage_min', 'Stage Min', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for stage_max field
            //
            $column = new NumberViewColumn('stage_max', 'stage_max', 'Stage Max', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for stage_std field
            //
            $column = new NumberViewColumn('stage_std', 'stage_std', 'Stage Std', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for stage_dir field
            //
            $column = new TextViewColumn('stage_dir', 'stage_dir', 'Stage Dir', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for salt_volume field
            //
            $column = new NumberViewColumn('salt_volume', 'salt_volume', 'Salt Volume', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for discharge_avg field
            //
            $column = new NumberViewColumn('discharge_avg', 'discharge_avg', 'Discharge Avg', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for uncert field
            //
            $column = new NumberViewColumn('uncert', 'uncert', 'Uncert', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for flags field
            //
            $column = new TextViewColumn('flags', 'flags', 'Flags', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for ecb field
            //
            $column = new TextViewColumn('ecb', 'ecb', 'ECB', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for mixing field
            //
            $column = new NumberViewColumn('mixing', 'mixing', 'Mixing', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for eventid field
            //
            $column = new NumberViewColumn('eventid', 'eventid', 'EventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for periodid field
            //
            $column = new NumberViewColumn('periodid', 'LA2', 'PeriodID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for date field
            //
            $column = new DateTimeViewColumn('date', 'date', 'Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddExportColumn($column);
            
            //
            // View column for temp field
            //
            $column = new NumberViewColumn('temp', 'temp', 'Temp', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for start_time field
            //
            $column = new DateTimeViewColumn('start_time', 'start_time', 'Start Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddExportColumn($column);
            
            //
            // View column for stage_start field
            //
            $column = new NumberViewColumn('stage_start', 'stage_start', 'Stage Start', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for stage_average field
            //
            $column = new NumberViewColumn('stage_average', 'stage_average', 'Stage Average', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for stage_min field
            //
            $column = new NumberViewColumn('stage_min', 'stage_min', 'Stage Min', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for stage_max field
            //
            $column = new NumberViewColumn('stage_max', 'stage_max', 'Stage Max', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for stage_std field
            //
            $column = new NumberViewColumn('stage_std', 'stage_std', 'Stage Std', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for stage_dir field
            //
            $column = new TextViewColumn('stage_dir', 'stage_dir', 'Stage Dir', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for salt_volume field
            //
            $column = new NumberViewColumn('salt_volume', 'salt_volume', 'Salt Volume', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for discharge_avg field
            //
            $column = new NumberViewColumn('discharge_avg', 'discharge_avg', 'Discharge Avg', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for uncert field
            //
            $column = new NumberViewColumn('uncert', 'uncert', 'Uncert', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for flags field
            //
            $column = new TextViewColumn('flags', 'flags', 'Flags', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for ecb field
            //
            $column = new TextViewColumn('ecb', 'ecb', 'ECB', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for mixing field
            //
            $column = new NumberViewColumn('mixing', 'mixing', 'Mixing', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
        }
    
        private function AddCompareColumns(Grid $grid)
        {
            //
            // View column for eventid field
            //
            $column = new NumberViewColumn('eventid', 'eventid', 'EventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for periodid field
            //
            $column = new NumberViewColumn('periodid', 'LA2', 'PeriodID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for date field
            //
            $column = new DateTimeViewColumn('date', 'date', 'Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddCompareColumn($column);
            
            //
            // View column for temp field
            //
            $column = new NumberViewColumn('temp', 'temp', 'Temp', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for start_time field
            //
            $column = new DateTimeViewColumn('start_time', 'start_time', 'Start Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddCompareColumn($column);
            
            //
            // View column for stage_start field
            //
            $column = new NumberViewColumn('stage_start', 'stage_start', 'Stage Start', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for stage_average field
            //
            $column = new NumberViewColumn('stage_average', 'stage_average', 'Stage Average', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for stage_min field
            //
            $column = new NumberViewColumn('stage_min', 'stage_min', 'Stage Min', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for stage_max field
            //
            $column = new NumberViewColumn('stage_max', 'stage_max', 'Stage Max', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for stage_std field
            //
            $column = new NumberViewColumn('stage_std', 'stage_std', 'Stage Std', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for stage_dir field
            //
            $column = new TextViewColumn('stage_dir', 'stage_dir', 'Stage Dir', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for salt_volume field
            //
            $column = new NumberViewColumn('salt_volume', 'salt_volume', 'Salt Volume', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for discharge_avg field
            //
            $column = new NumberViewColumn('discharge_avg', 'discharge_avg', 'Discharge Avg', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for uncert field
            //
            $column = new NumberViewColumn('uncert', 'uncert', 'Uncert', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for flags field
            //
            $column = new TextViewColumn('flags', 'flags', 'Flags', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for ecb field
            //
            $column = new TextViewColumn('ecb', 'ecb', 'ECB', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for mixing field
            //
            $column = new NumberViewColumn('mixing', 'mixing', 'Mixing', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
        }
    
        private function AddCompareHeaderColumns(Grid $grid)
        {
    
        }
    
        public function GetPageDirection()
        {
            return null;
        }
    
        public function isFilterConditionRequired()
        {
            return false;
        }
    
        protected function ApplyCommonColumnEditProperties(CustomEditColumn $column)
        {
            $column->SetDisplaySetToNullCheckBox(false);
            $column->SetDisplaySetToDefaultCheckBox(false);
    		$column->SetVariableContainer($this->GetColumnVariableContainer());
        }
    
        function GetCustomClientScript()
        {
            return ;
        }
        
        function GetOnPageLoadedClientScript()
        {
            return ;
        }
        protected function GetEnableModalGridDelete() { return true; }
    
        protected function CreateGrid()
        {
            $result = new Grid($this, $this->dataset);
            if ($this->GetSecurityInfo()->HasDeleteGrant())
               $result->SetAllowDeleteSelected(false);
            else
               $result->SetAllowDeleteSelected(false);   
            
            ApplyCommonPageSettings($this, $result);
            
            $result->SetUseImagesForActions(true);
            $defaultSortedColumns = array();
            $defaultSortedColumns[] = new SortColumn('date', 'DESC');
            $result->setDefaultOrdering($defaultSortedColumns);
            $result->SetUseFixedHeader(false);
            $result->SetShowLineNumbers(false);
            $result->SetShowKeyColumnsImagesInHeader(false);
            $result->setAllowSortingByDialog(false);
            $result->SetViewMode(ViewMode::TABLE);
            $result->setEnableRuntimeCustomization(false);
            $result->setAllowAddMultipleRecords(false);
            $result->setMultiEditAllowed($this->GetSecurityInfo()->HasEditGrant() && false);
            $result->setTableBordered(true);
            $result->setTableCondensed(false);
            $result->setReloadPageAfterAjaxOperation(true);
            
            $result->SetHighlightRowAtHover(true);
            $result->SetWidth('');
    
            $this->AddFieldColumns($result);
            $this->AddSingleRecordViewColumns($result);
            $this->AddEditColumns($result);
            $this->AddMultiEditColumns($result);
            $this->AddInsertColumns($result);
            $this->AddPrintColumns($result);
            $this->AddExportColumns($result);
            $this->AddMultiUploadColumn($result);
    
            $this->AddOperationsColumns($result);
            $this->SetInsertFormTitle('Add New Autosalt Event');
            $this->SetShowPageList(true);
            $this->SetShowTopPageNavigator(true);
            $this->SetShowBottomPageNavigator(true);
            $this->setPrintListAvailable(false);
            $this->setPrintListRecordAvailable(false);
            $this->setPrintOneRecordAvailable(false);
            $this->setAllowPrintSelectedRecords(false);
            $this->setOpenPrintFormInNewTab(false);
            $this->setExportListAvailable(array());
            $this->setExportSelectedRecordsAvailable(array());
            $this->setExportListRecordAvailable(array());
            $this->setExportOneRecordAvailable(array());
            $this->setOpenExportedPdfInNewTab(false);
            $this->setShowFormErrorsOnTop(true);
	    $this->setDetailedDescription( fread(fopen("HTML/AutoSalt_Summary_Metadata.html",'r'),filesize("HTML/AutoSalt_Summary_Metadata.html")));
    
            return $result;
        }
     
        protected function setClientSideEvents(Grid $grid) {
    
        }
    
        protected function doRegisterHandlers() {
            
            
        }
       
        protected function doCustomRenderColumn($fieldName, $fieldData, $rowData, &$customText, &$handled)
        { 
    
        }
    
        protected function doCustomRenderPrintColumn($fieldName, $fieldData, $rowData, &$customText, &$handled)
        { 
    
        }
    
        protected function doCustomRenderExportColumn($exportType, $fieldName, $fieldData, $rowData, &$customText, &$handled)
        { 
    
        }
    
        protected function doCustomDrawRow($rowData, &$cellFontColor, &$cellFontSize, &$cellBgColor, &$cellItalicAttr, &$cellBoldAttr)
        {
    
        }
    
        protected function doExtendedCustomDrawRow($rowData, &$rowCellStyles, &$rowStyles, &$rowClasses, &$cellClasses)
        {
    
        }
    
        protected function doCustomRenderTotal($totalValue, $aggregate, $columnName, &$customText, &$handled)
        {
    
        }
    
        protected function doCustomDefaultValues(&$values, &$handled) 
        {
    
        }
    
        protected function doCustomCompareColumn($columnName, $valueA, $valueB, &$result)
        {
    
        }
    
        protected function doBeforeInsertRecord($page, &$rowData, $tableName, &$cancel, &$message, &$messageDisplayTime)
        {
    
        }
    
        protected function doBeforeUpdateRecord($page, $oldRowData, &$rowData, $tableName, &$cancel, &$message, &$messageDisplayTime)
        {
    
        }
    
        protected function doBeforeDeleteRecord($page, &$rowData, $tableName, &$cancel, &$message, &$messageDisplayTime)
        {
    
        }
    
        protected function doAfterInsertRecord($page, $rowData, $tableName, &$success, &$message, &$messageDisplayTime)
        {
    
        }
    
        protected function doAfterUpdateRecord($page, $oldRowData, $rowData, $tableName, &$success, &$message, &$messageDisplayTime)
        {
    
        }
    
        protected function doAfterDeleteRecord($page, $rowData, $tableName, &$success, &$message, &$messageDisplayTime)
        {
    
        }
    
        protected function doCustomHTMLHeader($page, &$customHtmlHeaderText)
        { 
    
        }
    
        protected function doGetCustomTemplate($type, $part, $mode, &$result, &$params)
        {
    
        }
    
        protected function doGetCustomExportOptions(Page $page, $exportType, $rowData, &$options)
        {
    
        }
    
        protected function doFileUpload($fieldName, $rowData, &$result, &$accept, $originalFileName, $originalFileExtension, $fileSize, $tempFileName)
        {
    
        }
    
        protected function doPrepareChart(Chart $chart)
        {
    
        }
    
        protected function doPrepareColumnFilter(ColumnFilter $columnFilter)
        {
    
        }
    
        protected function doPrepareFilterBuilder(FilterBuilder $filterBuilder, FixedKeysArray $columns)
        {
    
        }
    
        protected function doGetSelectionFilters(FixedKeysArray $columns, &$result)
        {
    
        }
    
        protected function doGetCustomFormLayout($mode, FixedKeysArray $columns, FormLayout $layout)
        {
    
        }
    
        protected function doGetCustomColumnGroup(FixedKeysArray $columns, ViewColumnGroup $columnGroup)
        {
    
        }
    
        protected function doPageLoaded()
        {
    
        }
    
        protected function doCalculateFields($rowData, $fieldName, &$value)
        {
    
        }
    
        protected function doGetCustomRecordPermissions(Page $page, &$usingCondition, $rowData, &$allowEdit, &$allowDelete, &$mergeWithDefault, &$handled)
        {
    
        }
    
        protected function doAddEnvironmentVariables(Page $page, &$variables)
        {
    
        }
    
    }

    SetUpUserAuthorization();

    try
    {
        $Page = new chrl_autosalt_summaryPage("chrl_autosalt_summary", "autosalt_summary.php", GetCurrentUserPermissionsForPage("chrl.autosalt_summary"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.autosalt_summary"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
