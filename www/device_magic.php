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
    
    
    
    class chrl_device_magicPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('Device Magic');
            $this->SetMenuLabel('Device Magic');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."device_magic"');
            $this->dataset->addFields(
                array(
                    new IntegerField('dmid', true, true, true),
                    new DateTimeField('submitted'),
                    new DateField('date_visit'),
                    new TimeField('time_visit'),
                    new IntegerField('siteid'),
                    new StringField('technician'),
                    new StringField('technician_other'),
                    new StringField('upstream_photo'),
                    new StringField('downstream_photo'),
                    new StringField('barrel_fill'),
                    new StringField('cf_event'),
                    new StringField('ec_sensor_change'),
                    new IntegerField('volume_solution'),
                    new IntegerField('salt_added'),
                    new IntegerField('water_added'),
                    new IntegerField('volume_depart'),
                    new IntegerField('salt_remaining_site'),
                    new StringField('barrel_fill_notes'),
                    new StringField('time_barrel_period'),
                    new StringField('trials_cf'),
                    new StringField('action'),
                    new StringField('reason'),
                    new StringField('sen_r_removed_type'),
                    new StringField('sen_r_removed_type_other'),
                    new StringField('sen_r_removed_sn'),
                    new StringField('sen_r_removed_probenum'),
                    new StringField('sen_r_new_type'),
                    new StringField('sen_r_new_type_other'),
                    new StringField('sen_r_new_sn'),
                    new StringField('sen_r_new_rivloc'),
                    new StringField('sen_r_new_rivloc_other'),
                    new StringField('sen_r_new_probenum'),
                    new StringField('sen_remove_type'),
                    new StringField('sen_remove_type_other'),
                    new StringField('sen_remove_sn'),
                    new StringField('sen_remove_probenum'),
                    new StringField('sen_add_type'),
                    new StringField('sen_add_type_other'),
                    new StringField('sen_add_sn'),
                    new StringField('sen_add_riverloc'),
                    new StringField('sen_add_riverloc_other'),
                    new StringField('sen_add_probenum'),
                    new StringField('notes_weather'),
                    new StringField('notes_repairs'),
                    new StringField('notes_todo'),
                    new StringField('notes_other'),
                    new StringField('new')
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
                new FilterColumn($this->dataset, 'dmid', 'dmid', 'Dmid'),
                new FilterColumn($this->dataset, 'submitted', 'submitted', 'Submitted'),
                new FilterColumn($this->dataset, 'date_visit', 'date_visit', 'Date Visit'),
                new FilterColumn($this->dataset, 'time_visit', 'time_visit', 'Time Visit'),
                new FilterColumn($this->dataset, 'siteid', 'siteid', 'Siteid'),
                new FilterColumn($this->dataset, 'technician', 'technician', 'Technician'),
                new FilterColumn($this->dataset, 'technician_other', 'technician_other', 'Technician Other'),
                new FilterColumn($this->dataset, 'upstream_photo', 'upstream_photo', 'Upstream Photo'),
                new FilterColumn($this->dataset, 'downstream_photo', 'downstream_photo', 'Downstream Photo'),
                new FilterColumn($this->dataset, 'barrel_fill', 'barrel_fill', 'Barrel Fill'),
                new FilterColumn($this->dataset, 'cf_event', 'cf_event', 'Cf Event'),
                new FilterColumn($this->dataset, 'ec_sensor_change', 'ec_sensor_change', 'Ec Sensor Change'),
                new FilterColumn($this->dataset, 'volume_solution', 'volume_solution', 'Volume Solution'),
                new FilterColumn($this->dataset, 'salt_added', 'salt_added', 'Salt Added'),
                new FilterColumn($this->dataset, 'water_added', 'water_added', 'Water Added'),
                new FilterColumn($this->dataset, 'volume_depart', 'volume_depart', 'Volume Depart'),
                new FilterColumn($this->dataset, 'salt_remaining_site', 'salt_remaining_site', 'Salt Remaining Site'),
                new FilterColumn($this->dataset, 'barrel_fill_notes', 'barrel_fill_notes', 'Barrel Fill Notes'),
                new FilterColumn($this->dataset, 'time_barrel_period', 'time_barrel_period', 'Time Barrel Period'),
                new FilterColumn($this->dataset, 'trials_cf', 'trials_cf', 'Trials Cf'),
                new FilterColumn($this->dataset, 'action', 'action', 'Action'),
                new FilterColumn($this->dataset, 'reason', 'reason', 'Reason'),
                new FilterColumn($this->dataset, 'sen_r_removed_type', 'sen_r_removed_type', 'Sen R Removed Type'),
                new FilterColumn($this->dataset, 'sen_r_removed_type_other', 'sen_r_removed_type_other', 'Sen R Removed Type Other'),
                new FilterColumn($this->dataset, 'sen_r_removed_sn', 'sen_r_removed_sn', 'Sen R Removed Sn'),
                new FilterColumn($this->dataset, 'sen_r_removed_probenum', 'sen_r_removed_probenum', 'Sen R Removed Probenum'),
                new FilterColumn($this->dataset, 'sen_r_new_type', 'sen_r_new_type', 'Sen R New Type'),
                new FilterColumn($this->dataset, 'sen_r_new_type_other', 'sen_r_new_type_other', 'Sen R New Type Other'),
                new FilterColumn($this->dataset, 'sen_r_new_sn', 'sen_r_new_sn', 'Sen R New Sn'),
                new FilterColumn($this->dataset, 'sen_r_new_rivloc', 'sen_r_new_rivloc', 'Sen R New Rivloc'),
                new FilterColumn($this->dataset, 'sen_r_new_rivloc_other', 'sen_r_new_rivloc_other', 'Sen R New Rivloc Other'),
                new FilterColumn($this->dataset, 'sen_r_new_probenum', 'sen_r_new_probenum', 'Sen R New Probenum'),
                new FilterColumn($this->dataset, 'sen_remove_type', 'sen_remove_type', 'Sen Remove Type'),
                new FilterColumn($this->dataset, 'sen_remove_type_other', 'sen_remove_type_other', 'Sen Remove Type Other'),
                new FilterColumn($this->dataset, 'sen_remove_sn', 'sen_remove_sn', 'Sen Remove Sn'),
                new FilterColumn($this->dataset, 'sen_remove_probenum', 'sen_remove_probenum', 'Sen Remove Probenum'),
                new FilterColumn($this->dataset, 'sen_add_type', 'sen_add_type', 'Sen Add Type'),
                new FilterColumn($this->dataset, 'sen_add_type_other', 'sen_add_type_other', 'Sen Add Type Other'),
                new FilterColumn($this->dataset, 'sen_add_sn', 'sen_add_sn', 'Sen Add Sn'),
                new FilterColumn($this->dataset, 'sen_add_riverloc', 'sen_add_riverloc', 'Sen Add Riverloc'),
                new FilterColumn($this->dataset, 'sen_add_riverloc_other', 'sen_add_riverloc_other', 'Sen Add Riverloc Other'),
                new FilterColumn($this->dataset, 'sen_add_probenum', 'sen_add_probenum', 'Sen Add Probenum'),
                new FilterColumn($this->dataset, 'notes_weather', 'notes_weather', 'Notes Weather'),
                new FilterColumn($this->dataset, 'notes_repairs', 'notes_repairs', 'Notes Repairs'),
                new FilterColumn($this->dataset, 'notes_todo', 'notes_todo', 'Notes Todo'),
                new FilterColumn($this->dataset, 'notes_other', 'notes_other', 'Notes Other'),
                new FilterColumn($this->dataset, 'new', 'new', 'New')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['dmid'])
                ->addColumn($columns['submitted'])
                ->addColumn($columns['date_visit'])
                ->addColumn($columns['time_visit'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['technician'])
                ->addColumn($columns['technician_other'])
                ->addColumn($columns['upstream_photo'])
                ->addColumn($columns['downstream_photo'])
                ->addColumn($columns['barrel_fill'])
                ->addColumn($columns['cf_event'])
                ->addColumn($columns['ec_sensor_change'])
                ->addColumn($columns['volume_solution'])
                ->addColumn($columns['salt_added'])
                ->addColumn($columns['water_added'])
                ->addColumn($columns['volume_depart'])
                ->addColumn($columns['salt_remaining_site'])
                ->addColumn($columns['barrel_fill_notes'])
                ->addColumn($columns['time_barrel_period'])
                ->addColumn($columns['trials_cf'])
                ->addColumn($columns['action'])
                ->addColumn($columns['reason'])
                ->addColumn($columns['sen_r_removed_type'])
                ->addColumn($columns['sen_r_removed_type_other'])
                ->addColumn($columns['sen_r_removed_sn'])
                ->addColumn($columns['sen_r_removed_probenum'])
                ->addColumn($columns['sen_r_new_type'])
                ->addColumn($columns['sen_r_new_type_other'])
                ->addColumn($columns['sen_r_new_sn'])
                ->addColumn($columns['sen_r_new_rivloc'])
                ->addColumn($columns['sen_r_new_rivloc_other'])
                ->addColumn($columns['sen_r_new_probenum'])
                ->addColumn($columns['sen_remove_type'])
                ->addColumn($columns['sen_remove_type_other'])
                ->addColumn($columns['sen_remove_sn'])
                ->addColumn($columns['sen_remove_probenum'])
                ->addColumn($columns['sen_add_type'])
                ->addColumn($columns['sen_add_type_other'])
                ->addColumn($columns['sen_add_sn'])
                ->addColumn($columns['sen_add_riverloc'])
                ->addColumn($columns['sen_add_riverloc_other'])
                ->addColumn($columns['sen_add_probenum'])
                ->addColumn($columns['notes_weather'])
                ->addColumn($columns['notes_repairs'])
                ->addColumn($columns['notes_todo'])
                ->addColumn($columns['notes_other'])
                ->addColumn($columns['new']);
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
            // View column for dmid field
            //
            $column = new NumberViewColumn('dmid', 'dmid', 'Dmid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for submitted field
            //
            $column = new DateTimeViewColumn('submitted', 'submitted', 'Submitted', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d H:i:s');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for date_visit field
            //
            $column = new DateTimeViewColumn('date_visit', 'date_visit', 'Date Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for time_visit field
            //
            $column = new DateTimeViewColumn('time_visit', 'time_visit', 'Time Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'Siteid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for technician field
            //
            $column = new TextViewColumn('technician', 'technician', 'Technician', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for technician_other field
            //
            $column = new TextViewColumn('technician_other', 'technician_other', 'Technician Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for upstream_photo field
            //
            $column = new TextViewColumn('upstream_photo', 'upstream_photo', 'Upstream Photo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for downstream_photo field
            //
            $column = new TextViewColumn('downstream_photo', 'downstream_photo', 'Downstream Photo', $this->dataset);
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
            // View column for cf_event field
            //
            $column = new TextViewColumn('cf_event', 'cf_event', 'Cf Event', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for ec_sensor_change field
            //
            $column = new TextViewColumn('ec_sensor_change', 'ec_sensor_change', 'Ec Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for volume_solution field
            //
            $column = new NumberViewColumn('volume_solution', 'volume_solution', 'Volume Solution', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for salt_added field
            //
            $column = new NumberViewColumn('salt_added', 'salt_added', 'Salt Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for water_added field
            //
            $column = new NumberViewColumn('water_added', 'water_added', 'Water Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for volume_depart field
            //
            $column = new NumberViewColumn('volume_depart', 'volume_depart', 'Volume Depart', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for salt_remaining_site field
            //
            $column = new NumberViewColumn('salt_remaining_site', 'salt_remaining_site', 'Salt Remaining Site', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for barrel_fill_notes field
            //
            $column = new TextViewColumn('barrel_fill_notes', 'barrel_fill_notes', 'Barrel Fill Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for time_barrel_period field
            //
            $column = new TextViewColumn('time_barrel_period', 'time_barrel_period', 'Time Barrel Period', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for trials_cf field
            //
            $column = new TextViewColumn('trials_cf', 'trials_cf', 'Trials Cf', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for action field
            //
            $column = new TextViewColumn('action', 'action', 'Action', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for reason field
            //
            $column = new TextViewColumn('reason', 'reason', 'Reason', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_removed_type field
            //
            $column = new TextViewColumn('sen_r_removed_type', 'sen_r_removed_type', 'Sen R Removed Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_removed_type_other field
            //
            $column = new TextViewColumn('sen_r_removed_type_other', 'sen_r_removed_type_other', 'Sen R Removed Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_removed_sn field
            //
            $column = new TextViewColumn('sen_r_removed_sn', 'sen_r_removed_sn', 'Sen R Removed Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_removed_probenum field
            //
            $column = new TextViewColumn('sen_r_removed_probenum', 'sen_r_removed_probenum', 'Sen R Removed Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_new_type field
            //
            $column = new TextViewColumn('sen_r_new_type', 'sen_r_new_type', 'Sen R New Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_new_type_other field
            //
            $column = new TextViewColumn('sen_r_new_type_other', 'sen_r_new_type_other', 'Sen R New Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_new_sn field
            //
            $column = new TextViewColumn('sen_r_new_sn', 'sen_r_new_sn', 'Sen R New Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_new_rivloc field
            //
            $column = new TextViewColumn('sen_r_new_rivloc', 'sen_r_new_rivloc', 'Sen R New Rivloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_new_rivloc_other field
            //
            $column = new TextViewColumn('sen_r_new_rivloc_other', 'sen_r_new_rivloc_other', 'Sen R New Rivloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_r_new_probenum field
            //
            $column = new TextViewColumn('sen_r_new_probenum', 'sen_r_new_probenum', 'Sen R New Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_remove_type field
            //
            $column = new TextViewColumn('sen_remove_type', 'sen_remove_type', 'Sen Remove Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_remove_type_other field
            //
            $column = new TextViewColumn('sen_remove_type_other', 'sen_remove_type_other', 'Sen Remove Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_remove_sn field
            //
            $column = new TextViewColumn('sen_remove_sn', 'sen_remove_sn', 'Sen Remove Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_remove_probenum field
            //
            $column = new TextViewColumn('sen_remove_probenum', 'sen_remove_probenum', 'Sen Remove Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_add_type field
            //
            $column = new TextViewColumn('sen_add_type', 'sen_add_type', 'Sen Add Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_add_type_other field
            //
            $column = new TextViewColumn('sen_add_type_other', 'sen_add_type_other', 'Sen Add Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_add_sn field
            //
            $column = new TextViewColumn('sen_add_sn', 'sen_add_sn', 'Sen Add Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_add_riverloc field
            //
            $column = new TextViewColumn('sen_add_riverloc', 'sen_add_riverloc', 'Sen Add Riverloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_add_riverloc_other field
            //
            $column = new TextViewColumn('sen_add_riverloc_other', 'sen_add_riverloc_other', 'Sen Add Riverloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for sen_add_probenum field
            //
            $column = new TextViewColumn('sen_add_probenum', 'sen_add_probenum', 'Sen Add Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for notes_weather field
            //
            $column = new TextViewColumn('notes_weather', 'notes_weather', 'Notes Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for notes_repairs field
            //
            $column = new TextViewColumn('notes_repairs', 'notes_repairs', 'Notes Repairs', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for notes_todo field
            //
            $column = new TextViewColumn('notes_todo', 'notes_todo', 'Notes Todo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for notes_other field
            //
            $column = new TextViewColumn('notes_other', 'notes_other', 'Notes Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for new field
            //
            $column = new TextViewColumn('new', 'new', 'New', $this->dataset);
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
            // View column for dmid field
            //
            $column = new NumberViewColumn('dmid', 'dmid', 'Dmid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for submitted field
            //
            $column = new DateTimeViewColumn('submitted', 'submitted', 'Submitted', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d H:i:s');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for date_visit field
            //
            $column = new DateTimeViewColumn('date_visit', 'date_visit', 'Date Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for time_visit field
            //
            $column = new DateTimeViewColumn('time_visit', 'time_visit', 'Time Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'Siteid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for technician field
            //
            $column = new TextViewColumn('technician', 'technician', 'Technician', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for technician_other field
            //
            $column = new TextViewColumn('technician_other', 'technician_other', 'Technician Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for upstream_photo field
            //
            $column = new TextViewColumn('upstream_photo', 'upstream_photo', 'Upstream Photo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for downstream_photo field
            //
            $column = new TextViewColumn('downstream_photo', 'downstream_photo', 'Downstream Photo', $this->dataset);
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
            // View column for cf_event field
            //
            $column = new TextViewColumn('cf_event', 'cf_event', 'Cf Event', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for ec_sensor_change field
            //
            $column = new TextViewColumn('ec_sensor_change', 'ec_sensor_change', 'Ec Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for volume_solution field
            //
            $column = new NumberViewColumn('volume_solution', 'volume_solution', 'Volume Solution', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for salt_added field
            //
            $column = new NumberViewColumn('salt_added', 'salt_added', 'Salt Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for water_added field
            //
            $column = new NumberViewColumn('water_added', 'water_added', 'Water Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for volume_depart field
            //
            $column = new NumberViewColumn('volume_depart', 'volume_depart', 'Volume Depart', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for salt_remaining_site field
            //
            $column = new NumberViewColumn('salt_remaining_site', 'salt_remaining_site', 'Salt Remaining Site', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for barrel_fill_notes field
            //
            $column = new TextViewColumn('barrel_fill_notes', 'barrel_fill_notes', 'Barrel Fill Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for time_barrel_period field
            //
            $column = new TextViewColumn('time_barrel_period', 'time_barrel_period', 'Time Barrel Period', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for trials_cf field
            //
            $column = new TextViewColumn('trials_cf', 'trials_cf', 'Trials Cf', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for action field
            //
            $column = new TextViewColumn('action', 'action', 'Action', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for reason field
            //
            $column = new TextViewColumn('reason', 'reason', 'Reason', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_removed_type field
            //
            $column = new TextViewColumn('sen_r_removed_type', 'sen_r_removed_type', 'Sen R Removed Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_removed_type_other field
            //
            $column = new TextViewColumn('sen_r_removed_type_other', 'sen_r_removed_type_other', 'Sen R Removed Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_removed_sn field
            //
            $column = new TextViewColumn('sen_r_removed_sn', 'sen_r_removed_sn', 'Sen R Removed Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_removed_probenum field
            //
            $column = new TextViewColumn('sen_r_removed_probenum', 'sen_r_removed_probenum', 'Sen R Removed Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_new_type field
            //
            $column = new TextViewColumn('sen_r_new_type', 'sen_r_new_type', 'Sen R New Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_new_type_other field
            //
            $column = new TextViewColumn('sen_r_new_type_other', 'sen_r_new_type_other', 'Sen R New Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_new_sn field
            //
            $column = new TextViewColumn('sen_r_new_sn', 'sen_r_new_sn', 'Sen R New Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_new_rivloc field
            //
            $column = new TextViewColumn('sen_r_new_rivloc', 'sen_r_new_rivloc', 'Sen R New Rivloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_new_rivloc_other field
            //
            $column = new TextViewColumn('sen_r_new_rivloc_other', 'sen_r_new_rivloc_other', 'Sen R New Rivloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_r_new_probenum field
            //
            $column = new TextViewColumn('sen_r_new_probenum', 'sen_r_new_probenum', 'Sen R New Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_remove_type field
            //
            $column = new TextViewColumn('sen_remove_type', 'sen_remove_type', 'Sen Remove Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_remove_type_other field
            //
            $column = new TextViewColumn('sen_remove_type_other', 'sen_remove_type_other', 'Sen Remove Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_remove_sn field
            //
            $column = new TextViewColumn('sen_remove_sn', 'sen_remove_sn', 'Sen Remove Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_remove_probenum field
            //
            $column = new TextViewColumn('sen_remove_probenum', 'sen_remove_probenum', 'Sen Remove Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_add_type field
            //
            $column = new TextViewColumn('sen_add_type', 'sen_add_type', 'Sen Add Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_add_type_other field
            //
            $column = new TextViewColumn('sen_add_type_other', 'sen_add_type_other', 'Sen Add Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_add_sn field
            //
            $column = new TextViewColumn('sen_add_sn', 'sen_add_sn', 'Sen Add Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_add_riverloc field
            //
            $column = new TextViewColumn('sen_add_riverloc', 'sen_add_riverloc', 'Sen Add Riverloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_add_riverloc_other field
            //
            $column = new TextViewColumn('sen_add_riverloc_other', 'sen_add_riverloc_other', 'Sen Add Riverloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for sen_add_probenum field
            //
            $column = new TextViewColumn('sen_add_probenum', 'sen_add_probenum', 'Sen Add Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for notes_weather field
            //
            $column = new TextViewColumn('notes_weather', 'notes_weather', 'Notes Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for notes_repairs field
            //
            $column = new TextViewColumn('notes_repairs', 'notes_repairs', 'Notes Repairs', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for notes_todo field
            //
            $column = new TextViewColumn('notes_todo', 'notes_todo', 'Notes Todo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for notes_other field
            //
            $column = new TextViewColumn('notes_other', 'notes_other', 'Notes Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for new field
            //
            $column = new TextViewColumn('new', 'new', 'New', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddSingleRecordViewColumn($column);
        }
    
        protected function AddEditColumns(Grid $grid)
        {
    
        }
    
        protected function AddMultiEditColumns(Grid $grid)
        {
    
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
            // View column for dmid field
            //
            $column = new NumberViewColumn('dmid', 'dmid', 'Dmid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for submitted field
            //
            $column = new DateTimeViewColumn('submitted', 'submitted', 'Submitted', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d H:i:s');
            $grid->AddPrintColumn($column);
            
            //
            // View column for date_visit field
            //
            $column = new DateTimeViewColumn('date_visit', 'date_visit', 'Date Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddPrintColumn($column);
            
            //
            // View column for time_visit field
            //
            $column = new DateTimeViewColumn('time_visit', 'time_visit', 'Time Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddPrintColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'Siteid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for technician field
            //
            $column = new TextViewColumn('technician', 'technician', 'Technician', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for technician_other field
            //
            $column = new TextViewColumn('technician_other', 'technician_other', 'Technician Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for upstream_photo field
            //
            $column = new TextViewColumn('upstream_photo', 'upstream_photo', 'Upstream Photo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for downstream_photo field
            //
            $column = new TextViewColumn('downstream_photo', 'downstream_photo', 'Downstream Photo', $this->dataset);
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
            // View column for cf_event field
            //
            $column = new TextViewColumn('cf_event', 'cf_event', 'Cf Event', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for ec_sensor_change field
            //
            $column = new TextViewColumn('ec_sensor_change', 'ec_sensor_change', 'Ec Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddPrintColumn($column);
            
            //
            // View column for volume_solution field
            //
            $column = new NumberViewColumn('volume_solution', 'volume_solution', 'Volume Solution', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for salt_added field
            //
            $column = new NumberViewColumn('salt_added', 'salt_added', 'Salt Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for water_added field
            //
            $column = new NumberViewColumn('water_added', 'water_added', 'Water Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for volume_depart field
            //
            $column = new NumberViewColumn('volume_depart', 'volume_depart', 'Volume Depart', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for salt_remaining_site field
            //
            $column = new NumberViewColumn('salt_remaining_site', 'salt_remaining_site', 'Salt Remaining Site', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for barrel_fill_notes field
            //
            $column = new TextViewColumn('barrel_fill_notes', 'barrel_fill_notes', 'Barrel Fill Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for time_barrel_period field
            //
            $column = new TextViewColumn('time_barrel_period', 'time_barrel_period', 'Time Barrel Period', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for trials_cf field
            //
            $column = new TextViewColumn('trials_cf', 'trials_cf', 'Trials Cf', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for action field
            //
            $column = new TextViewColumn('action', 'action', 'Action', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for reason field
            //
            $column = new TextViewColumn('reason', 'reason', 'Reason', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_removed_type field
            //
            $column = new TextViewColumn('sen_r_removed_type', 'sen_r_removed_type', 'Sen R Removed Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_removed_type_other field
            //
            $column = new TextViewColumn('sen_r_removed_type_other', 'sen_r_removed_type_other', 'Sen R Removed Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_removed_sn field
            //
            $column = new TextViewColumn('sen_r_removed_sn', 'sen_r_removed_sn', 'Sen R Removed Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_removed_probenum field
            //
            $column = new TextViewColumn('sen_r_removed_probenum', 'sen_r_removed_probenum', 'Sen R Removed Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_new_type field
            //
            $column = new TextViewColumn('sen_r_new_type', 'sen_r_new_type', 'Sen R New Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_new_type_other field
            //
            $column = new TextViewColumn('sen_r_new_type_other', 'sen_r_new_type_other', 'Sen R New Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_new_sn field
            //
            $column = new TextViewColumn('sen_r_new_sn', 'sen_r_new_sn', 'Sen R New Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_new_rivloc field
            //
            $column = new TextViewColumn('sen_r_new_rivloc', 'sen_r_new_rivloc', 'Sen R New Rivloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_new_rivloc_other field
            //
            $column = new TextViewColumn('sen_r_new_rivloc_other', 'sen_r_new_rivloc_other', 'Sen R New Rivloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_r_new_probenum field
            //
            $column = new TextViewColumn('sen_r_new_probenum', 'sen_r_new_probenum', 'Sen R New Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_remove_type field
            //
            $column = new TextViewColumn('sen_remove_type', 'sen_remove_type', 'Sen Remove Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_remove_type_other field
            //
            $column = new TextViewColumn('sen_remove_type_other', 'sen_remove_type_other', 'Sen Remove Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_remove_sn field
            //
            $column = new TextViewColumn('sen_remove_sn', 'sen_remove_sn', 'Sen Remove Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_remove_probenum field
            //
            $column = new TextViewColumn('sen_remove_probenum', 'sen_remove_probenum', 'Sen Remove Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_add_type field
            //
            $column = new TextViewColumn('sen_add_type', 'sen_add_type', 'Sen Add Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_add_type_other field
            //
            $column = new TextViewColumn('sen_add_type_other', 'sen_add_type_other', 'Sen Add Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_add_sn field
            //
            $column = new TextViewColumn('sen_add_sn', 'sen_add_sn', 'Sen Add Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_add_riverloc field
            //
            $column = new TextViewColumn('sen_add_riverloc', 'sen_add_riverloc', 'Sen Add Riverloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_add_riverloc_other field
            //
            $column = new TextViewColumn('sen_add_riverloc_other', 'sen_add_riverloc_other', 'Sen Add Riverloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for sen_add_probenum field
            //
            $column = new TextViewColumn('sen_add_probenum', 'sen_add_probenum', 'Sen Add Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for notes_weather field
            //
            $column = new TextViewColumn('notes_weather', 'notes_weather', 'Notes Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for notes_repairs field
            //
            $column = new TextViewColumn('notes_repairs', 'notes_repairs', 'Notes Repairs', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for notes_todo field
            //
            $column = new TextViewColumn('notes_todo', 'notes_todo', 'Notes Todo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for notes_other field
            //
            $column = new TextViewColumn('notes_other', 'notes_other', 'Notes Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
            
            //
            // View column for new field
            //
            $column = new TextViewColumn('new', 'new', 'New', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for dmid field
            //
            $column = new NumberViewColumn('dmid', 'dmid', 'Dmid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for submitted field
            //
            $column = new DateTimeViewColumn('submitted', 'submitted', 'Submitted', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d H:i:s');
            $grid->AddExportColumn($column);
            
            //
            // View column for date_visit field
            //
            $column = new DateTimeViewColumn('date_visit', 'date_visit', 'Date Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddExportColumn($column);
            
            //
            // View column for time_visit field
            //
            $column = new DateTimeViewColumn('time_visit', 'time_visit', 'Time Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddExportColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'Siteid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for technician field
            //
            $column = new TextViewColumn('technician', 'technician', 'Technician', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for technician_other field
            //
            $column = new TextViewColumn('technician_other', 'technician_other', 'Technician Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for upstream_photo field
            //
            $column = new TextViewColumn('upstream_photo', 'upstream_photo', 'Upstream Photo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for downstream_photo field
            //
            $column = new TextViewColumn('downstream_photo', 'downstream_photo', 'Downstream Photo', $this->dataset);
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
            // View column for cf_event field
            //
            $column = new TextViewColumn('cf_event', 'cf_event', 'Cf Event', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for ec_sensor_change field
            //
            $column = new TextViewColumn('ec_sensor_change', 'ec_sensor_change', 'Ec Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddExportColumn($column);
            
            //
            // View column for volume_solution field
            //
            $column = new NumberViewColumn('volume_solution', 'volume_solution', 'Volume Solution', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for salt_added field
            //
            $column = new NumberViewColumn('salt_added', 'salt_added', 'Salt Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for water_added field
            //
            $column = new NumberViewColumn('water_added', 'water_added', 'Water Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for volume_depart field
            //
            $column = new NumberViewColumn('volume_depart', 'volume_depart', 'Volume Depart', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for salt_remaining_site field
            //
            $column = new NumberViewColumn('salt_remaining_site', 'salt_remaining_site', 'Salt Remaining Site', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for barrel_fill_notes field
            //
            $column = new TextViewColumn('barrel_fill_notes', 'barrel_fill_notes', 'Barrel Fill Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for time_barrel_period field
            //
            $column = new TextViewColumn('time_barrel_period', 'time_barrel_period', 'Time Barrel Period', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for trials_cf field
            //
            $column = new TextViewColumn('trials_cf', 'trials_cf', 'Trials Cf', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for action field
            //
            $column = new TextViewColumn('action', 'action', 'Action', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for reason field
            //
            $column = new TextViewColumn('reason', 'reason', 'Reason', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_removed_type field
            //
            $column = new TextViewColumn('sen_r_removed_type', 'sen_r_removed_type', 'Sen R Removed Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_removed_type_other field
            //
            $column = new TextViewColumn('sen_r_removed_type_other', 'sen_r_removed_type_other', 'Sen R Removed Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_removed_sn field
            //
            $column = new TextViewColumn('sen_r_removed_sn', 'sen_r_removed_sn', 'Sen R Removed Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_removed_probenum field
            //
            $column = new TextViewColumn('sen_r_removed_probenum', 'sen_r_removed_probenum', 'Sen R Removed Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_new_type field
            //
            $column = new TextViewColumn('sen_r_new_type', 'sen_r_new_type', 'Sen R New Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_new_type_other field
            //
            $column = new TextViewColumn('sen_r_new_type_other', 'sen_r_new_type_other', 'Sen R New Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_new_sn field
            //
            $column = new TextViewColumn('sen_r_new_sn', 'sen_r_new_sn', 'Sen R New Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_new_rivloc field
            //
            $column = new TextViewColumn('sen_r_new_rivloc', 'sen_r_new_rivloc', 'Sen R New Rivloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_new_rivloc_other field
            //
            $column = new TextViewColumn('sen_r_new_rivloc_other', 'sen_r_new_rivloc_other', 'Sen R New Rivloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_r_new_probenum field
            //
            $column = new TextViewColumn('sen_r_new_probenum', 'sen_r_new_probenum', 'Sen R New Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_remove_type field
            //
            $column = new TextViewColumn('sen_remove_type', 'sen_remove_type', 'Sen Remove Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_remove_type_other field
            //
            $column = new TextViewColumn('sen_remove_type_other', 'sen_remove_type_other', 'Sen Remove Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_remove_sn field
            //
            $column = new TextViewColumn('sen_remove_sn', 'sen_remove_sn', 'Sen Remove Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_remove_probenum field
            //
            $column = new TextViewColumn('sen_remove_probenum', 'sen_remove_probenum', 'Sen Remove Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_add_type field
            //
            $column = new TextViewColumn('sen_add_type', 'sen_add_type', 'Sen Add Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_add_type_other field
            //
            $column = new TextViewColumn('sen_add_type_other', 'sen_add_type_other', 'Sen Add Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_add_sn field
            //
            $column = new TextViewColumn('sen_add_sn', 'sen_add_sn', 'Sen Add Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_add_riverloc field
            //
            $column = new TextViewColumn('sen_add_riverloc', 'sen_add_riverloc', 'Sen Add Riverloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_add_riverloc_other field
            //
            $column = new TextViewColumn('sen_add_riverloc_other', 'sen_add_riverloc_other', 'Sen Add Riverloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for sen_add_probenum field
            //
            $column = new TextViewColumn('sen_add_probenum', 'sen_add_probenum', 'Sen Add Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for notes_weather field
            //
            $column = new TextViewColumn('notes_weather', 'notes_weather', 'Notes Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for notes_repairs field
            //
            $column = new TextViewColumn('notes_repairs', 'notes_repairs', 'Notes Repairs', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for notes_todo field
            //
            $column = new TextViewColumn('notes_todo', 'notes_todo', 'Notes Todo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for notes_other field
            //
            $column = new TextViewColumn('notes_other', 'notes_other', 'Notes Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
            
            //
            // View column for new field
            //
            $column = new TextViewColumn('new', 'new', 'New', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddExportColumn($column);
        }
    
        private function AddCompareColumns(Grid $grid)
        {
            //
            // View column for submitted field
            //
            $column = new DateTimeViewColumn('submitted', 'submitted', 'Submitted', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d H:i:s');
            $grid->AddCompareColumn($column);
            
            //
            // View column for date_visit field
            //
            $column = new DateTimeViewColumn('date_visit', 'date_visit', 'Date Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddCompareColumn($column);
            
            //
            // View column for time_visit field
            //
            $column = new DateTimeViewColumn('time_visit', 'time_visit', 'Time Visit', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('H:i:s');
            $grid->AddCompareColumn($column);
            
            //
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'Siteid', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for technician field
            //
            $column = new TextViewColumn('technician', 'technician', 'Technician', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for technician_other field
            //
            $column = new TextViewColumn('technician_other', 'technician_other', 'Technician Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for upstream_photo field
            //
            $column = new TextViewColumn('upstream_photo', 'upstream_photo', 'Upstream Photo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for downstream_photo field
            //
            $column = new TextViewColumn('downstream_photo', 'downstream_photo', 'Downstream Photo', $this->dataset);
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
            // View column for cf_event field
            //
            $column = new TextViewColumn('cf_event', 'cf_event', 'Cf Event', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for ec_sensor_change field
            //
            $column = new TextViewColumn('ec_sensor_change', 'ec_sensor_change', 'Ec Sensor Change', $this->dataset);
            $column->SetOrderable(true);
            $grid->AddCompareColumn($column);
            
            //
            // View column for volume_solution field
            //
            $column = new NumberViewColumn('volume_solution', 'volume_solution', 'Volume Solution', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for salt_added field
            //
            $column = new NumberViewColumn('salt_added', 'salt_added', 'Salt Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for water_added field
            //
            $column = new NumberViewColumn('water_added', 'water_added', 'Water Added', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for volume_depart field
            //
            $column = new NumberViewColumn('volume_depart', 'volume_depart', 'Volume Depart', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for salt_remaining_site field
            //
            $column = new NumberViewColumn('salt_remaining_site', 'salt_remaining_site', 'Salt Remaining Site', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for barrel_fill_notes field
            //
            $column = new TextViewColumn('barrel_fill_notes', 'barrel_fill_notes', 'Barrel Fill Notes', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for time_barrel_period field
            //
            $column = new TextViewColumn('time_barrel_period', 'time_barrel_period', 'Time Barrel Period', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for trials_cf field
            //
            $column = new TextViewColumn('trials_cf', 'trials_cf', 'Trials Cf', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for action field
            //
            $column = new TextViewColumn('action', 'action', 'Action', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for reason field
            //
            $column = new TextViewColumn('reason', 'reason', 'Reason', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_removed_type field
            //
            $column = new TextViewColumn('sen_r_removed_type', 'sen_r_removed_type', 'Sen R Removed Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_removed_type_other field
            //
            $column = new TextViewColumn('sen_r_removed_type_other', 'sen_r_removed_type_other', 'Sen R Removed Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_removed_sn field
            //
            $column = new TextViewColumn('sen_r_removed_sn', 'sen_r_removed_sn', 'Sen R Removed Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_removed_probenum field
            //
            $column = new TextViewColumn('sen_r_removed_probenum', 'sen_r_removed_probenum', 'Sen R Removed Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_new_type field
            //
            $column = new TextViewColumn('sen_r_new_type', 'sen_r_new_type', 'Sen R New Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_new_type_other field
            //
            $column = new TextViewColumn('sen_r_new_type_other', 'sen_r_new_type_other', 'Sen R New Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_new_sn field
            //
            $column = new TextViewColumn('sen_r_new_sn', 'sen_r_new_sn', 'Sen R New Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_new_rivloc field
            //
            $column = new TextViewColumn('sen_r_new_rivloc', 'sen_r_new_rivloc', 'Sen R New Rivloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_new_rivloc_other field
            //
            $column = new TextViewColumn('sen_r_new_rivloc_other', 'sen_r_new_rivloc_other', 'Sen R New Rivloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_r_new_probenum field
            //
            $column = new TextViewColumn('sen_r_new_probenum', 'sen_r_new_probenum', 'Sen R New Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_remove_type field
            //
            $column = new TextViewColumn('sen_remove_type', 'sen_remove_type', 'Sen Remove Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_remove_type_other field
            //
            $column = new TextViewColumn('sen_remove_type_other', 'sen_remove_type_other', 'Sen Remove Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_remove_sn field
            //
            $column = new TextViewColumn('sen_remove_sn', 'sen_remove_sn', 'Sen Remove Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_remove_probenum field
            //
            $column = new TextViewColumn('sen_remove_probenum', 'sen_remove_probenum', 'Sen Remove Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_add_type field
            //
            $column = new TextViewColumn('sen_add_type', 'sen_add_type', 'Sen Add Type', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_add_type_other field
            //
            $column = new TextViewColumn('sen_add_type_other', 'sen_add_type_other', 'Sen Add Type Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_add_sn field
            //
            $column = new TextViewColumn('sen_add_sn', 'sen_add_sn', 'Sen Add Sn', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_add_riverloc field
            //
            $column = new TextViewColumn('sen_add_riverloc', 'sen_add_riverloc', 'Sen Add Riverloc', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_add_riverloc_other field
            //
            $column = new TextViewColumn('sen_add_riverloc_other', 'sen_add_riverloc_other', 'Sen Add Riverloc Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for sen_add_probenum field
            //
            $column = new TextViewColumn('sen_add_probenum', 'sen_add_probenum', 'Sen Add Probenum', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for notes_weather field
            //
            $column = new TextViewColumn('notes_weather', 'notes_weather', 'Notes Weather', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for notes_repairs field
            //
            $column = new TextViewColumn('notes_repairs', 'notes_repairs', 'Notes Repairs', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for notes_todo field
            //
            $column = new TextViewColumn('notes_todo', 'notes_todo', 'Notes Todo', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for notes_other field
            //
            $column = new TextViewColumn('notes_other', 'notes_other', 'Notes Other', $this->dataset);
            $column->SetOrderable(true);
            $column->SetMaxLength(75);
            $grid->AddCompareColumn($column);
            
            //
            // View column for new field
            //
            $column = new TextViewColumn('new', 'new', 'New', $this->dataset);
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
        $Page = new chrl_device_magicPage("chrl_device_magic", "device_magic.php", GetCurrentUserPermissionsForPage("chrl.device_magic"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.device_magic"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
