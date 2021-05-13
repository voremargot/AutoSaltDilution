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
    
    
    
    class chrl_all_discharge_calcsPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('All Discharge Calcs');
            $this->SetMenuLabel('All Discharge Calcs');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."all_discharge_calcs"');
            $this->dataset->addFields(
                array(
                    new IntegerField('dischargeid', true, true, true),
                    new IntegerField('eventid', true),
                    new IntegerField('siteid', true),
                    new IntegerField('sensorid', true),
                    new IntegerField('cfid', true),
                    new IntegerField('discharge'),
                    new IntegerField('uncertainty'),
                    new StringField('used')
                )
            );
            $this->dataset->AddLookupField('sensorid', 'chrl.sensors', new IntegerField('sensorid'), new IntegerField('sensorid', false, false, false, false, 'LA1', 'LT1'), 'LT1');
            $this->dataset->AddLookupField('cfid', 'chrl.calibration_results', new IntegerField('calresultsid'), new IntegerField('calresultsid', false, false, false, false, 'LA2', 'LT2'), 'LT2');
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
                new FilterColumn($this->dataset, 'dischargeid', 'dischargeid', 'DischargeID'),
                new FilterColumn($this->dataset, 'eventid', 'eventid', 'EventID'),
                new FilterColumn($this->dataset, 'siteid', 'siteid', 'SiteID'),
                new FilterColumn($this->dataset, 'sensorid', 'LA1', 'SensorID'),
                new FilterColumn($this->dataset, 'cfid', 'LA2', 'CFID'),
                new FilterColumn($this->dataset, 'discharge', 'discharge', 'Discharge'),
                new FilterColumn($this->dataset, 'uncertainty', 'uncertainty', 'Uncertainty'),
                new FilterColumn($this->dataset, 'used', 'used', 'Used')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['dischargeid'])
                ->addColumn($columns['eventid'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['sensorid'])
                ->addColumn($columns['cfid'])
                ->addColumn($columns['discharge'])
                ->addColumn($columns['uncertainty'])
                ->addColumn($columns['used']);
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
            // View column for dischargeid field
            //
            $column = new NumberViewColumn('dischargeid', 'dischargeid', 'DischargeID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key-autogenerated');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for eventid field
            //
            $column = new NumberViewColumn('eventid', 'eventid', 'EventID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Autosalt Summary Table');
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
            $column->SetDescription('FK- Autosalt Summary Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA1', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Sensors Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('cfid', 'LA2', 'CFID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Calibration Results Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for discharge field
            //
            $column = new NumberViewColumn('discharge', 'discharge', 'Discharge', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('m3/s');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for uncertainty field
            //
            $column = new NumberViewColumn('uncertainty', 'uncertainty', 'Uncertainty', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('%');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for used field
            //
            $column = new TextViewColumn('used', 'used', 'Used', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
        }
    
        protected function AddSingleRecordViewColumns(Grid $grid)
        {
            //
            // View column for dischargeid field
            //
            $column = new NumberViewColumn('dischargeid', 'dischargeid', 'DischargeID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
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
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA1', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('cfid', 'LA2', 'CFID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for discharge field
            //
            $column = new NumberViewColumn('discharge', 'discharge', 'Discharge', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for uncertainty field
            //
            $column = new NumberViewColumn('uncertainty', 'uncertainty', 'Uncertainty', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for used field
            //
            $column = new TextViewColumn('used', 'used', 'Used', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
        }
    
        protected function AddEditColumns(Grid $grid)
        {
            //
            // Edit column for eventid field
            //
            $editor = new TextEdit('eventid_edit');
            $editColumn = new CustomEditColumn('EventID', 'eventid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
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
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for cfid field
            //
            $editor = new ComboBox('cfid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_results"');
            $lookupDataset->addFields(
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
            $lookupDataset->setOrderByField('calresultsid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'CFID', 
                'cfid', 
                $editor, 
                $this->dataset, 'calresultsid', 'calresultsid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for discharge field
            //
            $editor = new TextEdit('discharge_edit');
            $editColumn = new CustomEditColumn('Discharge', 'discharge', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for uncertainty field
            //
            $editor = new TextEdit('uncertainty_edit');
            $editColumn = new CustomEditColumn('Uncertainty', 'uncertainty', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for used field
            //
            $editor = new ComboBox('used_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Used', 'used', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
        }
    
        protected function AddMultiEditColumns(Grid $grid)
        {
            //
            // Edit column for eventid field
            //
            $editor = new TextEdit('eventid_edit');
            $editColumn = new CustomEditColumn('EventID', 'eventid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
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
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for cfid field
            //
            $editor = new ComboBox('cfid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_results"');
            $lookupDataset->addFields(
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
            $lookupDataset->setOrderByField('calresultsid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'CFID', 
                'cfid', 
                $editor, 
                $this->dataset, 'calresultsid', 'calresultsid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for discharge field
            //
            $editor = new TextEdit('discharge_edit');
            $editColumn = new CustomEditColumn('Discharge', 'discharge', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for uncertainty field
            //
            $editor = new TextEdit('uncertainty_edit');
            $editColumn = new CustomEditColumn('Uncertainty', 'uncertainty', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for used field
            //
            $editor = new ComboBox('used_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Used', 'used', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
        }
    
        protected function AddInsertColumns(Grid $grid)
        {
            //
            // Edit column for eventid field
            //
            $editor = new TextEdit('eventid_edit');
            $editColumn = new CustomEditColumn('EventID', 'eventid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for siteid field
            //
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
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
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for cfid field
            //
            $editor = new ComboBox('cfid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."calibration_results"');
            $lookupDataset->addFields(
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
            $lookupDataset->setOrderByField('calresultsid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'CFID', 
                'cfid', 
                $editor, 
                $this->dataset, 'calresultsid', 'calresultsid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for discharge field
            //
            $editor = new TextEdit('discharge_edit');
            $editColumn = new CustomEditColumn('Discharge', 'discharge', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for uncertainty field
            //
            $editor = new TextEdit('uncertainty_edit');
            $editColumn = new CustomEditColumn('Uncertainty', 'uncertainty', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for used field
            //
            $editor = new ComboBox('used_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Used', 'used', $editor, $this->dataset);
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
            // View column for dischargeid field
            //
            $column = new NumberViewColumn('dischargeid', 'dischargeid', 'DischargeID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
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
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA1', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('cfid', 'LA2', 'CFID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for discharge field
            //
            $column = new NumberViewColumn('discharge', 'discharge', 'Discharge', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for uncertainty field
            //
            $column = new NumberViewColumn('uncertainty', 'uncertainty', 'Uncertainty', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for used field
            //
            $column = new TextViewColumn('used', 'used', 'Used', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for dischargeid field
            //
            $column = new NumberViewColumn('dischargeid', 'dischargeid', 'DischargeID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
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
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA1', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('cfid', 'LA2', 'CFID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for discharge field
            //
            $column = new NumberViewColumn('discharge', 'discharge', 'Discharge', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for uncertainty field
            //
            $column = new NumberViewColumn('uncertainty', 'uncertainty', 'Uncertainty', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for used field
            //
            $column = new TextViewColumn('used', 'used', 'Used', $this->dataset);
            $column->SetOrderable(true);
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
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for sensorid field
            //
            $column = new NumberViewColumn('sensorid', 'LA1', 'SensorID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for calresultsid field
            //
            $column = new NumberViewColumn('cfid', 'LA2', 'CFID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for discharge field
            //
            $column = new NumberViewColumn('discharge', 'discharge', 'Discharge', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for uncertainty field
            //
            $column = new NumberViewColumn('uncertainty', 'uncertainty', 'Uncertainty', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(3);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for used field
            //
            $column = new TextViewColumn('used', 'used', 'Used', $this->dataset);
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
            $defaultSortedColumns = array();
            $defaultSortedColumns[] = new SortColumn('eventid', 'DESC');
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
 		 $this->setDetailedDescription( fread(fopen(			   "HTML/All_Discharge_Metadata.html",'r'),filesize("HTML/All_Discharge_Metadata.html")));
    
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
        $Page = new chrl_all_discharge_calcsPage("chrl_all_discharge_calcs", "all_discharge_calcs.php", GetCurrentUserPermissionsForPage("chrl.all_discharge_calcs"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.all_discharge_calcs"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
