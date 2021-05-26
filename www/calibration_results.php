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
    
    
    
    class chrl_calibration_resultsPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('Calibration Results');
            $this->SetMenuLabel('Calibration Results');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_results"');
            $this->dataset->addFields(
                array(
                    new IntegerField('calresultsid', true, true, true),
                    new IntegerField('caleventid'),
                    new IntegerField('siteid'),
                    new IntegerField('sensorid'),
                    new IntegerField('trial_number'),
                    new IntegerField('temp'),
                    new IntegerField('cf_value'),
                    new IntegerField('per_err'),
                    new StringField('flags'),
                    new StringField('notes'),
                    new StringField('link')
                )
            );
            $this->dataset->AddLookupField('caleventid', 'chrl.calibration_events', new IntegerField('caleventid'), new IntegerField('caleventid', false, false, false, false, 'LA1', 'LT1'), 'LT1');
            $this->dataset->AddLookupField('sensorid', 'chrl.sensors', new IntegerField('sensorid'), new IntegerField('sensorid', false, false, false, false, 'LA2', 'LT2'), 'LT2');
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
                new FilterColumn($this->dataset, 'calresultsid', 'calresultsid', 'CalresultsID'),
                new FilterColumn($this->dataset, 'caleventid', 'LA1', 'CaleventID'),
                new FilterColumn($this->dataset, 'siteid', 'siteid', 'SiteID'),
                new FilterColumn($this->dataset, 'sensorid', 'LA2', 'SensorID'),
                new FilterColumn($this->dataset, 'trial_number', 'trial_number', 'Trial Number'),
                new FilterColumn($this->dataset, 'temp', 'temp', 'Temp'),
                new FilterColumn($this->dataset, 'cf_value', 'cf_value', 'CF Value'),
                new FilterColumn($this->dataset, 'per_err', 'per_err', 'Per Err'),
                new FilterColumn($this->dataset, 'flags', 'flags', 'Flags'),
                new FilterColumn($this->dataset, 'notes', 'notes', 'Notes'),
                new FilterColumn($this->dataset, 'link', 'link', 'Link')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['calresultsid'])
                ->addColumn($columns['caleventid'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['sensorid'])
                ->addColumn($columns['trial_number'])
                ->addColumn($columns['temp'])
                ->addColumn($columns['cf_value'])
                ->addColumn($columns['per_err'])
                ->addColumn($columns['flags'])
                ->addColumn($columns['notes'])
                ->addColumn($columns['link']);
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
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('calresultsid', 'calresultsid', 'CalresultsID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key-autogenerated');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for caleventid field
            //
            $column = new NumberViewColumn('caleventid', 'LA1', 'CaleventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Calibration Event Table');
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA2', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Sensors Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for trial_number field
            //
            $column = new NumberViewColumn('trial_number', 'trial_number', 'Trial Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
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
            // View column for cf_value field
            //
            $column = new NumberViewColumn('cf_value', 'cf_value', 'CF Value', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Scaled by 10^6');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for per_err field
            //
            $column = new NumberViewColumn('per_err', 'per_err', 'Per Err', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
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
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for link field
            //
            $column = new TextViewColumn('link', 'link', 'Link', $this->dataset);
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
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('calresultsid', 'calresultsid', 'CalresultsID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for caleventid field
            //
            $column = new NumberViewColumn('caleventid', 'LA1', 'CaleventID', $this->dataset);
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA2', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for trial_number field
            //
            $column = new NumberViewColumn('trial_number', 'trial_number', 'Trial Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
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
            // View column for cf_value field
            //
            $column = new NumberViewColumn('cf_value', 'cf_value', 'CF Value', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for per_err field
            //
            $column = new NumberViewColumn('per_err', 'per_err', 'Per Err', $this->dataset);
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
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for link field
            //
            $column = new TextViewColumn('link', 'link', 'Link', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
        }
    
        protected function AddEditColumns(Grid $grid)
        {
            //
            // Edit column for caleventid field
            //
            $editor = new ComboBox('caleventid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_events"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('caleventid', true, true, true),
                    new IntegerField('periodid'),
                    new IntegerField('siteid'),
                    new DateField('date', true),
                    new StringField('pmp', true),
                    new IntegerField('trial', true),
                    new StringField('location', true)
                )
            );
            $lookupDataset->setOrderByField('caleventid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'CaleventID', 
                'caleventid', 
                $editor, 
                $this->dataset, 'caleventid', 'caleventid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
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
            // Edit column for sensorid field
            //
            $editor = new ComboBox('sensorid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."sensors"');
            $lookupDataset->addFields(
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
            $lookupDataset->setOrderByField('sensorid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'SensorID', 
                'sensorid', 
                $editor, 
                $this->dataset, 'sensorid', 'sensorid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for trial_number field
            //
            $editor = new TextEdit('trial_number_edit');
            $editColumn = new CustomEditColumn('Trial Number', 'trial_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for temp field
            //
            $editor = new TextEdit('temp_edit');
            $editColumn = new CustomEditColumn('Temp', 'temp', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for cf_value field
            //
            $editor = new TextEdit('cf_value_edit');
            $editColumn = new CustomEditColumn('CF Value', 'cf_value', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for per_err field
            //
            $editor = new TextEdit('per_err_edit');
            $editColumn = new CustomEditColumn('Per Err', 'per_err', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for flags field
            //
            $editor = new ComboBox('flags_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('L', 'L');
            $editor->addChoice('H', 'H');
            $editColumn = new CustomEditColumn('Flags', 'flags', $editor, $this->dataset);
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
            
            //
            // Edit column for link field
            //
            $editor = new TextAreaEdit('link_edit', 50, 8);
            $editColumn = new CustomEditColumn('Link', 'link', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
        }
    
        protected function AddMultiEditColumns(Grid $grid)
        {
            //
            // Edit column for caleventid field
            //
            $editor = new ComboBox('caleventid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_events"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('caleventid', true, true, true),
                    new IntegerField('periodid'),
                    new IntegerField('siteid'),
                    new DateField('date', true),
                    new StringField('pmp', true),
                    new IntegerField('trial', true),
                    new StringField('location', true)
                )
            );
            $lookupDataset->setOrderByField('caleventid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'CaleventID', 
                'caleventid', 
                $editor, 
                $this->dataset, 'caleventid', 'caleventid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for sensorid field
            //
            $editor = new ComboBox('sensorid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."sensors"');
            $lookupDataset->addFields(
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
            $lookupDataset->setOrderByField('sensorid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'SensorID', 
                'sensorid', 
                $editor, 
                $this->dataset, 'sensorid', 'sensorid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for trial_number field
            //
            $editor = new TextEdit('trial_number_edit');
            $editColumn = new CustomEditColumn('Trial Number', 'trial_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for temp field
            //
            $editor = new TextEdit('temp_edit');
            $editColumn = new CustomEditColumn('Temp', 'temp', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for cf_value field
            //
            $editor = new TextEdit('cf_value_edit');
            $editColumn = new CustomEditColumn('CF Value', 'cf_value', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for per_err field
            //
            $editor = new TextEdit('per_err_edit');
            $editColumn = new CustomEditColumn('Per Err', 'per_err', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for flags field
            //
            $editor = new ComboBox('flags_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('L', 'L');
            $editor->addChoice('H', 'H');
            $editColumn = new CustomEditColumn('Flags', 'flags', $editor, $this->dataset);
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
            
            //
            // Edit column for link field
            //
            $editor = new TextAreaEdit('link_edit', 50, 8);
            $editColumn = new CustomEditColumn('Link', 'link', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
        }
    
        protected function AddInsertColumns(Grid $grid)
        {
            //
            // Edit column for caleventid field
            //
            $editor = new ComboBox('caleventid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_events"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('caleventid', true, true, true),
                    new IntegerField('periodid'),
                    new IntegerField('siteid'),
                    new DateField('date', true),
                    new StringField('pmp', true),
                    new IntegerField('trial', true),
                    new StringField('location', true)
                )
            );
            $lookupDataset->setOrderByField('caleventid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'CaleventID', 
                'caleventid', 
                $editor, 
                $this->dataset, 'caleventid', 'caleventid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for sensorid field
            //
            $editor = new ComboBox('sensorid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."sensors"');
            $lookupDataset->addFields(
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
            $lookupDataset->setOrderByField('sensorid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'SensorID', 
                'sensorid', 
                $editor, 
                $this->dataset, 'sensorid', 'sensorid', $lookupDataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for trial_number field
            //
            $editor = new TextEdit('trial_number_edit');
            $editColumn = new CustomEditColumn('Trial Number', 'trial_number', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for temp field
            //
            $editor = new TextEdit('temp_edit');
            $editColumn = new CustomEditColumn('Temp', 'temp', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for cf_value field
            //
            $editor = new TextEdit('cf_value_edit');
            $editColumn = new CustomEditColumn('CF Value', 'cf_value', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for per_err field
            //
            $editor = new TextEdit('per_err_edit');
            $editColumn = new CustomEditColumn('Per Err', 'per_err', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for flags field
            //
            $editor = new ComboBox('flags_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('L', 'L');
            $editor->addChoice('H', 'H');
            $editColumn = new CustomEditColumn('Flags', 'flags', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for notes field
            //
            $editor = new TextAreaEdit('notes_edit', 50, 8);
            $editColumn = new CustomEditColumn('Notes', 'notes', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for link field
            //
            $editor = new TextAreaEdit('link_edit', 50, 8);
            $editColumn = new CustomEditColumn('Link', 'link', $editor, $this->dataset);
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
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('calresultsid', 'calresultsid', 'CalresultsID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for caleventid field
            //
            $column = new NumberViewColumn('caleventid', 'LA1', 'CaleventID', $this->dataset);
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA2', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for trial_number field
            //
            $column = new NumberViewColumn('trial_number', 'trial_number', 'Trial Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
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
            // View column for cf_value field
            //
            $column = new NumberViewColumn('cf_value', 'cf_value', 'CF Value', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for per_err field
            //
            $column = new NumberViewColumn('per_err', 'per_err', 'Per Err', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
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
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for link field
            //
            $column = new TextViewColumn('link', 'link', 'Link', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('calresultsid', 'calresultsid', 'CalresultsID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for caleventid field
            //
            $column = new NumberViewColumn('caleventid', 'LA1', 'CaleventID', $this->dataset);
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA2', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for trial_number field
            //
            $column = new NumberViewColumn('trial_number', 'trial_number', 'Trial Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
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
            // View column for cf_value field
            //
            $column = new NumberViewColumn('cf_value', 'cf_value', 'CF Value', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for per_err field
            //
            $column = new NumberViewColumn('per_err', 'per_err', 'Per Err', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
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
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for link field
            //
            $column = new TextViewColumn('link', 'link', 'Link', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
        }
    
        private function AddCompareColumns(Grid $grid)
        {
            //
            // View column for caleventid field
            //
            $column = new NumberViewColumn('caleventid', 'LA1', 'CaleventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
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
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA2', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for trial_number field
            //
            $column = new NumberViewColumn('trial_number', 'trial_number', 'Trial Number', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator(',');
            $column->setDecimalSeparator('');
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
            // View column for cf_value field
            //
            $column = new NumberViewColumn('cf_value', 'cf_value', 'CF Value', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for per_err field
            //
            $column = new NumberViewColumn('per_err', 'per_err', 'Per Err', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
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
            // View column for notes field
            //
            $column = new TextViewColumn('notes', 'notes', 'Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for link field
            //
            $column = new TextViewColumn('link', 'link', 'Link', $this->dataset);
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
            $defaultSortedColumns[] = new SortColumn('LA1', 'DESC');
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
 		 $this->setDetailedDescription( fread(fopen(			   "HTML/Calibration_Results_Metadata.html",'r'),filesize("HTML/Calibration_Results_Metadata.html")));
    
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
        $Page = new chrl_calibration_resultsPage("chrl_calibration_results", "calibration_results.php", GetCurrentUserPermissionsForPage("chrl.calibration_results"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.calibration_results"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
