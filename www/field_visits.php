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
    
    
    
    class chrl_field_visitsPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('Field Visits');
            $this->SetMenuLabel('Field Visits');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."field_visits"');
            $this->dataset->addFields(
                array(
                    new IntegerField('fid', true, true, true),
                    new IntegerField('siteid'),
                    new DateField('date'),
                    new TimeField('time'),
                    new StringField('technicians'),
                    new StringField('barrel_fill'),
                    new StringField('cf_collection'),
                    new StringField('sensor_change'),
                    new StringField('weather'),
                    new StringField('repairs_adjustments'),
                    new StringField('todo'),
                    new StringField('other'),
                    new StringField('upstream_pic'),
                    new StringField('downstream_pic'),
                    new StringField('dmid')
                )
            );
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
                new FilterColumn($this->dataset, 'fid', 'fid', 'FID'),
                new FilterColumn($this->dataset, 'siteid', 'siteid', 'SiteID'),
                new FilterColumn($this->dataset, 'date', 'date', 'Date'),
                new FilterColumn($this->dataset, 'time', 'time', 'Time'),
                new FilterColumn($this->dataset, 'technicians', 'technicians', 'Technicians'),
                new FilterColumn($this->dataset, 'barrel_fill', 'barrel_fill', 'Barrel Fill'),
                new FilterColumn($this->dataset, 'cf_collection', 'cf_collection', 'CF Collection'),
                new FilterColumn($this->dataset, 'sensor_change', 'sensor_change', 'Sensor Change'),
                new FilterColumn($this->dataset, 'weather', 'weather', 'Weather'),
                new FilterColumn($this->dataset, 'repairs_adjustments', 'repairs_adjustments', 'Repairs and Adjustments'),
                new FilterColumn($this->dataset, 'todo', 'todo', 'To Dos'),
                new FilterColumn($this->dataset, 'other', 'other', 'Other'),
                new FilterColumn($this->dataset, 'upstream_pic', 'upstream_pic', 'Upstream Picture'),
                new FilterColumn($this->dataset, 'downstream_pic', 'downstream_pic', 'Downstream Picture'),
                new FilterColumn($this->dataset, 'dmid', 'dmid', 'Dmid')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['fid'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['date'])
                ->addColumn($columns['time'])
                ->addColumn($columns['technicians'])
                ->addColumn($columns['barrel_fill'])
                ->addColumn($columns['cf_collection'])
                ->addColumn($columns['sensor_change'])
                ->addColumn($columns['weather'])
                ->addColumn($columns['repairs_adjustments'])
                ->addColumn($columns['todo'])
                ->addColumn($columns['other'])
                ->addColumn($columns['upstream_pic'])
                ->addColumn($columns['downstream_pic']);
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
            // View column for fid field
            //
            $column = new NumberViewColumn('fid', 'fid', 'FID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key- autogenerated');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Site Description Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for date field
            //
            $column = new DateTimeViewColumn('date', 'date', 'Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('YYYY-MM-DD');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for time field
            //
            $column = new DateTimeViewColumn('time', 'time', 'Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Local Time (PST/PDT)');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for technicians field
            //
            $column = new TextViewColumn('technicians', 'technicians', 'Technicians', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for barrel_fill field
            //
            $column = new TextViewColumn('barrel_fill', 'barrel_fill', 'Barrel Fill', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for cf_collection field
            //
            $column = new TextViewColumn('cf_collection', 'cf_collection', 'CF Collection', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sensor_change field
            //
            $column = new TextViewColumn('sensor_change', 'sensor_change', 'Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for weather field
            //
            $column = new TextViewColumn('weather', 'weather', 'Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for repairs_adjustments field
            //
            $column = new TextViewColumn('repairs_adjustments', 'repairs_adjustments', 'Repairs and Adjustments', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for todo field
            //
            $column = new TextViewColumn('todo', 'todo', 'To Dos', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for other field
            //
            $column = new TextViewColumn('other', 'other', 'Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for upstream_pic field
            //
            $column = new TextViewColumn('upstream_pic', 'upstream_pic', 'Upstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for downstream_pic field
            //
            $column = new TextViewColumn('downstream_pic', 'downstream_pic', 'Downstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
        }
    
        protected function AddSingleRecordViewColumns(Grid $grid)
        {
            //
            // View column for fid field
            //
            $column = new NumberViewColumn('fid', 'fid', 'FID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
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
            // View column for time field
            //
            $column = new DateTimeViewColumn('time', 'time', 'Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for technicians field
            //
            $column = new TextViewColumn('technicians', 'technicians', 'Technicians', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for barrel_fill field
            //
            $column = new TextViewColumn('barrel_fill', 'barrel_fill', 'Barrel Fill', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for cf_collection field
            //
            $column = new TextViewColumn('cf_collection', 'cf_collection', 'CF Collection', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sensor_change field
            //
            $column = new TextViewColumn('sensor_change', 'sensor_change', 'Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for weather field
            //
            $column = new TextViewColumn('weather', 'weather', 'Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for repairs_adjustments field
            //
            $column = new TextViewColumn('repairs_adjustments', 'repairs_adjustments', 'Repairs and Adjustments', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for todo field
            //
            $column = new TextViewColumn('todo', 'todo', 'To Dos', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for other field
            //
            $column = new TextViewColumn('other', 'other', 'Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for upstream_pic field
            //
            $column = new TextViewColumn('upstream_pic', 'upstream_pic', 'Upstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for downstream_pic field
            //
            $column = new TextViewColumn('downstream_pic', 'downstream_pic', 'Downstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
        }
    
        protected function AddEditColumns(Grid $grid)
        {
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for date field
            //
            $editor = new DateTimeEdit('date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Date', 'date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for time field
            //
            $editor = new TimeEdit('time_edit', 'H:i:s');
            $editColumn = new CustomEditColumn('Time', 'time', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for technicians field
            //
            $editor = new TextAreaEdit('technicians_edit', 50, 8);
            $editColumn = new CustomEditColumn('Technicians', 'technicians', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for barrel_fill field
            //
            $editor = new ComboBox('barrel_fill_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Barrel Fill', 'barrel_fill', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for cf_collection field
            //
            $editor = new ComboBox('cf_collection_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('CF Collection', 'cf_collection', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for sensor_change field
            //
            $editor = new ComboBox('sensor_change_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Sensor Change', 'sensor_change', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for weather field
            //
            $editor = new TextAreaEdit('weather_edit', 50, 8);
            $editColumn = new CustomEditColumn('Weather', 'weather', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for repairs_adjustments field
            //
            $editor = new TextAreaEdit('repairs_adjustments_edit', 50, 8);
            $editColumn = new CustomEditColumn('Repairs and Adjustments', 'repairs_adjustments', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for todo field
            //
            $editor = new TextAreaEdit('todo_edit', 50, 8);
            $editColumn = new CustomEditColumn('To Dos', 'todo', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for other field
            //
            $editor = new TextAreaEdit('other_edit', 50, 8);
            $editColumn = new CustomEditColumn('Other', 'other', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for upstream_pic field
            //
            $editor = new TextEdit('upstream_pic_edit');
            $editColumn = new CustomEditColumn('Upstream Picture', 'upstream_pic', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for downstream_pic field
            //
            $editor = new TextEdit('downstream_pic_edit');
            $editColumn = new CustomEditColumn('Downstream Picture', 'downstream_pic', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
        }
    
        protected function AddMultiEditColumns(Grid $grid)
        {
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for date field
            //
            $editor = new DateTimeEdit('date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Date', 'date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for time field
            //
            $editor = new TimeEdit('time_edit', 'H:i:s');
            $editColumn = new CustomEditColumn('Time', 'time', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for technicians field
            //
            $editor = new TextAreaEdit('technicians_edit', 50, 8);
            $editColumn = new CustomEditColumn('Technicians', 'technicians', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for barrel_fill field
            //
            $editor = new ComboBox('barrel_fill_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Barrel Fill', 'barrel_fill', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for cf_collection field
            //
            $editor = new ComboBox('cf_collection_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('CF Collection', 'cf_collection', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for sensor_change field
            //
            $editor = new ComboBox('sensor_change_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Sensor Change', 'sensor_change', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for weather field
            //
            $editor = new TextAreaEdit('weather_edit', 50, 8);
            $editColumn = new CustomEditColumn('Weather', 'weather', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for repairs_adjustments field
            //
            $editor = new TextAreaEdit('repairs_adjustments_edit', 50, 8);
            $editColumn = new CustomEditColumn('Repairs and Adjustments', 'repairs_adjustments', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for todo field
            //
            $editor = new TextAreaEdit('todo_edit', 50, 8);
            $editColumn = new CustomEditColumn('To Dos', 'todo', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for other field
            //
            $editor = new TextAreaEdit('other_edit', 50, 8);
            $editColumn = new CustomEditColumn('Other', 'other', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for upstream_pic field
            //
            $editor = new TextEdit('upstream_pic_edit');
            $editColumn = new CustomEditColumn('Upstream Picture', 'upstream_pic', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for downstream_pic field
            //
            $editor = new TextEdit('downstream_pic_edit');
            $editColumn = new CustomEditColumn('Downstream Picture', 'downstream_pic', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
        }
    
        protected function AddInsertColumns(Grid $grid)
        {
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for date field
            //
            $editor = new DateTimeEdit('date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Date', 'date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for time field
            //
            $editor = new TimeEdit('time_edit', 'H:i:s');
            $editColumn = new CustomEditColumn('Time', 'time', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for technicians field
            //
            $editor = new TextAreaEdit('technicians_edit', 50, 8);
            $editColumn = new CustomEditColumn('Technicians', 'technicians', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for barrel_fill field
            //
            $editor = new ComboBox('barrel_fill_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Barrel Fill', 'barrel_fill', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for cf_collection field
            //
            $editor = new ComboBox('cf_collection_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('CF Collection', 'cf_collection', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for sensor_change field
            //
            $editor = new ComboBox('sensor_change_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Sensor Change', 'sensor_change', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for weather field
            //
            $editor = new TextAreaEdit('weather_edit', 50, 8);
            $editColumn = new CustomEditColumn('Weather', 'weather', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for repairs_adjustments field
            //
            $editor = new TextAreaEdit('repairs_adjustments_edit', 50, 8);
            $editColumn = new CustomEditColumn('Repairs and Adjustments', 'repairs_adjustments', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for todo field
            //
            $editor = new TextAreaEdit('todo_edit', 50, 8);
            $editColumn = new CustomEditColumn('To Dos', 'todo', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for other field
            //
            $editor = new TextAreaEdit('other_edit', 50, 8);
            $editColumn = new CustomEditColumn('Other', 'other', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for upstream_pic field
            //
            $editor = new TextEdit('upstream_pic_edit');
            $editColumn = new CustomEditColumn('Upstream Picture', 'upstream_pic', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for downstream_pic field
            //
            $editor = new TextEdit('downstream_pic_edit');
            $editColumn = new CustomEditColumn('Downstream Picture', 'downstream_pic', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            $grid->SetShowAddButton(true && $this->GetSecurityInfo()->HasAddGrant());
        }
    
        private function AddMultiUploadColumn(Grid $grid)
        {
    
        }
    
        protected function AddPrintColumns(Grid $grid)
        {
            //
            // View column for fid field
            //
            $column = new NumberViewColumn('fid', 'fid', 'FID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
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
            // View column for time field
            //
            $column = new DateTimeViewColumn('time', 'time', 'Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddPrintColumn($column);
            
            //
            // View column for technicians field
            //
            $column = new TextViewColumn('technicians', 'technicians', 'Technicians', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for barrel_fill field
            //
            $column = new TextViewColumn('barrel_fill', 'barrel_fill', 'Barrel Fill', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for cf_collection field
            //
            $column = new TextViewColumn('cf_collection', 'cf_collection', 'CF Collection', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sensor_change field
            //
            $column = new TextViewColumn('sensor_change', 'sensor_change', 'Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for weather field
            //
            $column = new TextViewColumn('weather', 'weather', 'Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for repairs_adjustments field
            //
            $column = new TextViewColumn('repairs_adjustments', 'repairs_adjustments', 'Repairs and Adjustments', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for todo field
            //
            $column = new TextViewColumn('todo', 'todo', 'To Dos', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for other field
            //
            $column = new TextViewColumn('other', 'other', 'Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for upstream_pic field
            //
            $column = new TextViewColumn('upstream_pic', 'upstream_pic', 'Upstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for downstream_pic field
            //
            $column = new TextViewColumn('downstream_pic', 'downstream_pic', 'Downstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for fid field
            //
            $column = new NumberViewColumn('fid', 'fid', 'FID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
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
            // View column for time field
            //
            $column = new DateTimeViewColumn('time', 'time', 'Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddExportColumn($column);
            
            //
            // View column for technicians field
            //
            $column = new TextViewColumn('technicians', 'technicians', 'Technicians', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for barrel_fill field
            //
            $column = new TextViewColumn('barrel_fill', 'barrel_fill', 'Barrel Fill', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for cf_collection field
            //
            $column = new TextViewColumn('cf_collection', 'cf_collection', 'CF Collection', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for sensor_change field
            //
            $column = new TextViewColumn('sensor_change', 'sensor_change', 'Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for weather field
            //
            $column = new TextViewColumn('weather', 'weather', 'Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for repairs_adjustments field
            //
            $column = new TextViewColumn('repairs_adjustments', 'repairs_adjustments', 'Repairs and Adjustments', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for todo field
            //
            $column = new TextViewColumn('todo', 'todo', 'To Dos', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for other field
            //
            $column = new TextViewColumn('other', 'other', 'Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for upstream_pic field
            //
            $column = new TextViewColumn('upstream_pic', 'upstream_pic', 'Upstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for downstream_pic field
            //
            $column = new TextViewColumn('downstream_pic', 'downstream_pic', 'Downstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
        }
    
        private function AddCompareColumns(Grid $grid)
        {
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
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
            // View column for time field
            //
            $column = new DateTimeViewColumn('time', 'time', 'Time', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddCompareColumn($column);
            
            //
            // View column for technicians field
            //
            $column = new TextViewColumn('technicians', 'technicians', 'Technicians', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for barrel_fill field
            //
            $column = new TextViewColumn('barrel_fill', 'barrel_fill', 'Barrel Fill', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for cf_collection field
            //
            $column = new TextViewColumn('cf_collection', 'cf_collection', 'CF Collection', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sensor_change field
            //
            $column = new TextViewColumn('sensor_change', 'sensor_change', 'Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for weather field
            //
            $column = new TextViewColumn('weather', 'weather', 'Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for repairs_adjustments field
            //
            $column = new TextViewColumn('repairs_adjustments', 'repairs_adjustments', 'Repairs and Adjustments', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for todo field
            //
            $column = new TextViewColumn('todo', 'todo', 'To Dos', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for other field
            //
            $column = new TextViewColumn('other', 'other', 'Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for upstream_pic field
            //
            $column = new TextViewColumn('upstream_pic', 'upstream_pic', 'Upstream Picture', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for downstream_pic field
            //
            $column = new TextViewColumn('downstream_pic', 'downstream_pic', 'Downstream Picture', $this->dataset);
            $column->SetOrderable(true);
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
	    $this->setDetailedDescription( fread(fopen("HTML/Field_Visit_Metadata.html",'r'),filesize("HTML/Field_Visit_Metadata.html")));
    
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
        $Page = new chrl_field_visitsPage("chrl_field_visits", "field_visits.php", GetCurrentUserPermissionsForPage("chrl.field_visits"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.field_visits"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
