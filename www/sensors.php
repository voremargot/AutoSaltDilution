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
    
    
    
    class chrl_sensorsPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('Sensors');
            $this->SetMenuLabel('Sensors');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."sensors"');
            $this->dataset->addFields(
                array(
                    new IntegerField('sensorid', true, true, true),
                    new IntegerField('siteid'),
                    new IntegerField('probe_number'),
                    new StringField('sensor_type', true),
                    new StringField('serial_number'),
                    new StringField('river_loc'),
                    new DateField('install_date'),
                    new DateField('deactivation_date')
                )
            );
            $this->dataset->AddLookupField('siteid', 'chrl.site_description', new IntegerField('siteid'), new IntegerField('siteid', false, false, false, false, 'LA1', 'LT1'), 'LT1');
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
                new FilterColumn($this->dataset, 'sensorid', 'sensorid', 'SensorID'),
                new FilterColumn($this->dataset, 'siteid', 'LA1', 'SiteID'),
                new FilterColumn($this->dataset, 'probe_number', 'probe_number', 'Probe Number'),
                new FilterColumn($this->dataset, 'sensor_type', 'sensor_type', 'Sensor Type'),
                new FilterColumn($this->dataset, 'serial_number', 'serial_number', 'Serial Number'),
                new FilterColumn($this->dataset, 'river_loc', 'river_loc', 'River Loc'),
                new FilterColumn($this->dataset, 'install_date', 'install_date', 'Install Date'),
                new FilterColumn($this->dataset, 'deactivation_date', 'deactivation_date', 'Deactivation Date')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['sensorid'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['probe_number'])
                ->addColumn($columns['sensor_type'])
                ->addColumn($columns['serial_number'])
                ->addColumn($columns['river_loc'])
                ->addColumn($columns['install_date'])
                ->addColumn($columns['deactivation_date']);
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'sensorid', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key-autogenerated');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Site Description Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for probe_number field
            //
            $column = new NumberViewColumn('probe_number', 'probe_number', 'Probe Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sensor_type field
            //
            $column = new TextViewColumn('sensor_type', 'sensor_type', 'Sensor Type', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for serial_number field
            //
            $column = new TextViewColumn('serial_number', 'serial_number', 'Serial Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for river_loc field
            //
            $column = new TextViewColumn('river_loc', 'river_loc', 'River Loc', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for deactivation_date field
            //
            $column = new DateTimeViewColumn('deactivation_date', 'deactivation_date', 'Deactivation Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
        }
    
        protected function AddSingleRecordViewColumns(Grid $grid)
        {
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'sensorid', 'SensorID', $this->dataset);
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
            // View column for probe_number field
            //
            $column = new NumberViewColumn('probe_number', 'probe_number', 'Probe Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sensor_type field
            //
            $column = new TextViewColumn('sensor_type', 'sensor_type', 'Sensor Type', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for serial_number field
            //
            $column = new TextViewColumn('serial_number', 'serial_number', 'Serial Number', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for river_loc field
            //
            $column = new TextViewColumn('river_loc', 'river_loc', 'River Loc', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for deactivation_date field
            //
            $column = new DateTimeViewColumn('deactivation_date', 'deactivation_date', 'Deactivation Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddSingleRecordViewColumn($column);
        }
    
        protected function AddEditColumns(Grid $grid)
        {
            //
            // Edit column for siteid field
            //
            $editor = new ComboBox('siteid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."site_description"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('siteid', true, true),
                    new DateField('install_date', true),
                    new IntegerField('lat', true),
                    new IntegerField('lon', true),
                    new IntegerField('elevation', true),
                    new IntegerField('width_d'),
                    new IntegerField('max_depth_d'),
                    new IntegerField('width_ec'),
                    new IntegerField('max_depth_ec'),
                    new IntegerField('slope'),
                    new IntegerField('dist_d_ec'),
                    new StringField('active', true),
                    new DateField('deactivation_date')
                )
            );
            $lookupDataset->setOrderByField('siteid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'SiteID', 
                'siteid', 
                $editor, 
                $this->dataset, 'siteid', 'siteid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for probe_number field
            //
            $editor = new TextEdit('probe_number_edit');
            $editor->SetPlaceholder('Probe number in raw data that sensor cooresponds to');
            $editColumn = new CustomEditColumn('Probe Number', 'probe_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for sensor_type field
            //
            $editor = new ComboBox('sensor_type_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('WQ', 'WQ');
            $editor->addChoice('THRECS', 'THRECS');
            $editor->addChoice('Fathom', 'Fathom');
            $editColumn = new CustomEditColumn('Sensor Type', 'sensor_type', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for serial_number field
            //
            $editor = new TextEdit('serial_number_edit');
            $editor->SetMaxLength(15);
            $editColumn = new CustomEditColumn('Serial Number', 'serial_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for river_loc field
            //
            $editor = new ComboBox('river_loc_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('RR', 'RR');
            $editor->addChoice('RL', 'RL');
            $editor->addChoice('RR-L', 'RR-L');
            $editor->addChoice('RR-H', 'RR-H');
            $editor->addChoice('RL-L', 'RL-L');
            $editor->addChoice('RL-H', 'RL-H');
            $editor->addChoice('RC', 'RC');
            $editor->addChoice('Lab', 'Lab');
            $editColumn = new CustomEditColumn('River Loc', 'river_loc', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for install_date field
            //
            $editor = new DateTimeEdit('install_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Install Date', 'install_date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for deactivation_date field
            //
            $editor = new DateTimeEdit('deactivation_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Deactivation Date', 'deactivation_date', $editor, $this->dataset);
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
            $editor = new ComboBox('siteid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."site_description"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('siteid', true, true),
                    new DateField('install_date', true),
                    new IntegerField('lat', true),
                    new IntegerField('lon', true),
                    new IntegerField('elevation', true),
                    new IntegerField('width_d'),
                    new IntegerField('max_depth_d'),
                    new IntegerField('width_ec'),
                    new IntegerField('max_depth_ec'),
                    new IntegerField('slope'),
                    new IntegerField('dist_d_ec'),
                    new StringField('active', true),
                    new DateField('deactivation_date')
                )
            );
            $lookupDataset->setOrderByField('siteid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'SiteID', 
                'siteid', 
                $editor, 
                $this->dataset, 'siteid', 'siteid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for probe_number field
            //
            $editor = new TextEdit('probe_number_edit');
            $editor->SetPlaceholder('Probe number in raw data that sensor cooresponds to');
            $editColumn = new CustomEditColumn('Probe Number', 'probe_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for sensor_type field
            //
            $editor = new ComboBox('sensor_type_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('WQ', 'WQ');
            $editor->addChoice('THRECS', 'THRECS');
            $editor->addChoice('Fathom', 'Fathom');
            $editColumn = new CustomEditColumn('Sensor Type', 'sensor_type', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for serial_number field
            //
            $editor = new TextEdit('serial_number_edit');
            $editor->SetMaxLength(15);
            $editColumn = new CustomEditColumn('Serial Number', 'serial_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for river_loc field
            //
            $editor = new ComboBox('river_loc_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('RR', 'RR');
            $editor->addChoice('RL', 'RL');
            $editor->addChoice('RR-L', 'RR-L');
            $editor->addChoice('RR-H', 'RR-H');
            $editor->addChoice('RL-L', 'RL-L');
            $editor->addChoice('RL-H', 'RL-H');
            $editor->addChoice('RC', 'RC');
            $editor->addChoice('Lab', 'Lab');
            $editColumn = new CustomEditColumn('River Loc', 'river_loc', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for install_date field
            //
            $editor = new DateTimeEdit('install_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Install Date', 'install_date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for deactivation_date field
            //
            $editor = new DateTimeEdit('deactivation_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Deactivation Date', 'deactivation_date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
        }
    
        protected function AddInsertColumns(Grid $grid)
        {
            //
            // Edit column for siteid field
            //
            $editor = new ComboBox('siteid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."site_description"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('siteid', true, true),
                    new DateField('install_date', true),
                    new IntegerField('lat', true),
                    new IntegerField('lon', true),
                    new IntegerField('elevation', true),
                    new IntegerField('width_d'),
                    new IntegerField('max_depth_d'),
                    new IntegerField('width_ec'),
                    new IntegerField('max_depth_ec'),
                    new IntegerField('slope'),
                    new IntegerField('dist_d_ec'),
                    new StringField('active', true),
                    new DateField('deactivation_date')
                )
            );
            $lookupDataset->setOrderByField('siteid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'SiteID', 
                'siteid', 
                $editor, 
                $this->dataset, 'siteid', 'siteid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for probe_number field
            //
            $editor = new TextEdit('probe_number_edit');
            $editor->SetPlaceholder('Probe number in raw data that sensor cooresponds to');
            $editColumn = new CustomEditColumn('Probe Number', 'probe_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for sensor_type field
            //
            $editor = new ComboBox('sensor_type_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('WQ', 'WQ');
            $editor->addChoice('THRECS', 'THRECS');
            $editor->addChoice('Fathom', 'Fathom');
            $editColumn = new CustomEditColumn('Sensor Type', 'sensor_type', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for serial_number field
            //
            $editor = new TextEdit('serial_number_edit');
            $editor->SetMaxLength(15);
            $editColumn = new CustomEditColumn('Serial Number', 'serial_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for river_loc field
            //
            $editor = new ComboBox('river_loc_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('RR', 'RR');
            $editor->addChoice('RL', 'RL');
            $editor->addChoice('RR-L', 'RR-L');
            $editor->addChoice('RR-H', 'RR-H');
            $editor->addChoice('RL-L', 'RL-L');
            $editor->addChoice('RL-H', 'RL-H');
            $editor->addChoice('RC', 'RC');
            $editor->addChoice('Lab', 'Lab');
            $editColumn = new CustomEditColumn('River Loc', 'river_loc', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for install_date field
            //
            $editor = new DateTimeEdit('install_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Install Date', 'install_date', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for deactivation_date field
            //
            $editor = new DateTimeEdit('deactivation_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Deactivation Date', 'deactivation_date', $editor, $this->dataset);
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'sensorid', 'SensorID', $this->dataset);
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
            // View column for probe_number field
            //
            $column = new NumberViewColumn('probe_number', 'probe_number', 'Probe Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for sensor_type field
            //
            $column = new TextViewColumn('sensor_type', 'sensor_type', 'Sensor Type', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for serial_number field
            //
            $column = new TextViewColumn('serial_number', 'serial_number', 'Serial Number', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for river_loc field
            //
            $column = new TextViewColumn('river_loc', 'river_loc', 'River Loc', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddPrintColumn($column);
            
            //
            // View column for deactivation_date field
            //
            $column = new DateTimeViewColumn('deactivation_date', 'deactivation_date', 'Deactivation Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'sensorid', 'SensorID', $this->dataset);
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
            // View column for probe_number field
            //
            $column = new NumberViewColumn('probe_number', 'probe_number', 'Probe Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for sensor_type field
            //
            $column = new TextViewColumn('sensor_type', 'sensor_type', 'Sensor Type', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for serial_number field
            //
            $column = new TextViewColumn('serial_number', 'serial_number', 'Serial Number', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for river_loc field
            //
            $column = new TextViewColumn('river_loc', 'river_loc', 'River Loc', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddExportColumn($column);
            
            //
            // View column for deactivation_date field
            //
            $column = new DateTimeViewColumn('deactivation_date', 'deactivation_date', 'Deactivation Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddExportColumn($column);
        }
    
        private function AddCompareColumns(Grid $grid)
        {
            //
            // View column for siteid field
            //
            $column = new TextViewColumn('siteid', 'LA1', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for probe_number field
            //
            $column = new NumberViewColumn('probe_number', 'probe_number', 'Probe Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for sensor_type field
            //
            $column = new TextViewColumn('sensor_type', 'sensor_type', 'Sensor Type', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for serial_number field
            //
            $column = new TextViewColumn('serial_number', 'serial_number', 'Serial Number', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for river_loc field
            //
            $column = new TextViewColumn('river_loc', 'river_loc', 'River Loc', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddCompareColumn($column);
            
            //
            // View column for deactivation_date field
            //
            $column = new DateTimeViewColumn('deactivation_date', 'deactivation_date', 'Deactivation Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
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
            $defaultSortedColumns[] = new SortColumn('LA1', 'ASC');
            $defaultSortedColumns[] = new SortColumn('install_date', 'ASC');
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
            $this->SetInsertFormTitle('Add new sensor');
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
 		 $this->setDetailedDescription( fread(fopen(			   "HTML/Sensors_Metadata.html",'r'),filesize("HTML/Sensors_Metadata.html")));
    
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
        $Page = new chrl_sensorsPage("chrl_sensors", "sensors.php", GetCurrentUserPermissionsForPage("chrl.sensors"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.sensors"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
