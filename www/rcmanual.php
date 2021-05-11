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
    
    
    
    class chrl_rcmanualPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('RC Manual');
            $this->SetMenuLabel('RC Manual');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."rcmanual"');
            $this->dataset->addFields(
                array(
                    new IntegerField('rcmanualid', true, true, true),
                    new IntegerField('siteid', true),
                    new IntegerField('mdisid', true),
                    new IntegerField('rcid', true),
                    new IntegerField('eventno')
                )
            );
            $this->dataset->AddLookupField('siteid', 'chrl.site_description', new IntegerField('siteid'), new IntegerField('siteid', false, false, false, false, 'LA1', 'LT1'), 'LT1');
            $this->dataset->AddLookupField('mdisid', 'chrl.manual_discharge', new IntegerField('mdisid'), new IntegerField('mdisid', false, false, false, false, 'LA2', 'LT2'), 'LT2');
            $this->dataset->AddLookupField('rcid', 'chrl.rc_summary', new IntegerField('rcid'), new IntegerField('rcid', false, false, false, false, 'LA3', 'LT3'), 'LT3');
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
                new FilterColumn($this->dataset, 'rcmanualid', 'rcmanualid', 'RCmanualID'),
                new FilterColumn($this->dataset, 'siteid', 'LA1', 'SiteID'),
                new FilterColumn($this->dataset, 'mdisid', 'LA2', 'MDisID'),
                new FilterColumn($this->dataset, 'rcid', 'LA3', 'RCID'),
                new FilterColumn($this->dataset, 'eventno', 'eventno', 'EventNo')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['rcmanualid'])
                ->addColumn($columns['siteid'])
                ->addColumn($columns['mdisid'])
                ->addColumn($columns['rcid'])
                ->addColumn($columns['eventno']);
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
            // View column for rcmanualid field
            //
            $column = new NumberViewColumn('rcmanualid', 'rcmanualid', 'RCmanualID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key-autogenerated');
            $column->SetFixedWidth('5cm');
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
            // View column for mdisid field
            //
            $column = new NumberViewColumn('mdisid', 'LA2', 'MDisID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- Manual Discharge Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for rcid field
            //
            $column = new NumberViewColumn('rcid', 'LA3', 'RCID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('FK- RC Summary Table');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for eventno field
            //
            $column = new NumberViewColumn('eventno', 'eventno', 'EventNo', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
        }
    
        protected function AddSingleRecordViewColumns(Grid $grid)
        {
            //
            // View column for rcmanualid field
            //
            $column = new NumberViewColumn('rcmanualid', 'rcmanualid', 'RCmanualID', $this->dataset);
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
            // View column for mdisid field
            //
            $column = new NumberViewColumn('mdisid', 'LA2', 'MDisID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for rcid field
            //
            $column = new NumberViewColumn('rcid', 'LA3', 'RCID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for eventno field
            //
            $column = new NumberViewColumn('eventno', 'eventno', 'EventNo', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
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
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for mdisid field
            //
            $editor = new ComboBox('mdisid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."manual_discharge"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('mdisid', true, true, true),
                    new IntegerField('siteid', true),
                    new DateField('date', true),
                    new TimeField('time'),
                    new StringField('instream_loc'),
                    new IntegerField('stage'),
                    new IntegerField('discharge', true),
                    new IntegerField('uncert'),
                    new StringField('method', true),
                    new StringField('comment'),
                    new BlobField('images'),
                    new StringField('link')
                )
            );
            $lookupDataset->setOrderByField('mdisid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'MDisID', 
                'mdisid', 
                $editor, 
                $this->dataset, 'mdisid', 'mdisid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for rcid field
            //
            $editor = new ComboBox('rcid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."rc_summary"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('rcid', true, true, true),
                    new IntegerField('siteid', true),
                    new IntegerField('version', true),
                    new DateField('start_date', true),
                    new DateField('end_date', true),
                    new StringField('shift'),
                    new StringField('notes'),
                    new StringField('link1'),
                    new StringField('link2')
                )
            );
            $lookupDataset->setOrderByField('rcid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'RCID', 
                'rcid', 
                $editor, 
                $this->dataset, 'rcid', 'rcid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for eventno field
            //
            $editor = new TextEdit('eventno_edit');
            $editColumn = new CustomEditColumn('EventNo', 'eventno', $editor, $this->dataset);
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
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for mdisid field
            //
            $editor = new ComboBox('mdisid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."manual_discharge"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('mdisid', true, true, true),
                    new IntegerField('siteid', true),
                    new DateField('date', true),
                    new TimeField('time'),
                    new StringField('instream_loc'),
                    new IntegerField('stage'),
                    new IntegerField('discharge', true),
                    new IntegerField('uncert'),
                    new StringField('method', true),
                    new StringField('comment'),
                    new BlobField('images'),
                    new StringField('link')
                )
            );
            $lookupDataset->setOrderByField('mdisid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'MDisID', 
                'mdisid', 
                $editor, 
                $this->dataset, 'mdisid', 'mdisid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for rcid field
            //
            $editor = new ComboBox('rcid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."rc_summary"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('rcid', true, true, true),
                    new IntegerField('siteid', true),
                    new IntegerField('version', true),
                    new DateField('start_date', true),
                    new DateField('end_date', true),
                    new StringField('shift'),
                    new StringField('notes'),
                    new StringField('link1'),
                    new StringField('link2')
                )
            );
            $lookupDataset->setOrderByField('rcid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'RCID', 
                'rcid', 
                $editor, 
                $this->dataset, 'rcid', 'rcid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for eventno field
            //
            $editor = new TextEdit('eventno_edit');
            $editColumn = new CustomEditColumn('EventNo', 'eventno', $editor, $this->dataset);
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
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for mdisid field
            //
            $editor = new ComboBox('mdisid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."manual_discharge"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('mdisid', true, true, true),
                    new IntegerField('siteid', true),
                    new DateField('date', true),
                    new TimeField('time'),
                    new StringField('instream_loc'),
                    new IntegerField('stage'),
                    new IntegerField('discharge', true),
                    new IntegerField('uncert'),
                    new StringField('method', true),
                    new StringField('comment'),
                    new BlobField('images'),
                    new StringField('link')
                )
            );
            $lookupDataset->setOrderByField('mdisid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'MDisID', 
                'mdisid', 
                $editor, 
                $this->dataset, 'mdisid', 'mdisid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for rcid field
            //
            $editor = new ComboBox('rcid_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $lookupDataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."rc_summary"');
            $lookupDataset->addFields(
                array(
                    new IntegerField('rcid', true, true, true),
                    new IntegerField('siteid', true),
                    new IntegerField('version', true),
                    new DateField('start_date', true),
                    new DateField('end_date', true),
                    new StringField('shift'),
                    new StringField('notes'),
                    new StringField('link1'),
                    new StringField('link2')
                )
            );
            $lookupDataset->setOrderByField('rcid', 'ASC');
            $editColumn = new LookUpEditColumn(
                'RCID', 
                'rcid', 
                $editor, 
                $this->dataset, 'rcid', 'rcid', $lookupDataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for eventno field
            //
            $editor = new TextEdit('eventno_edit');
            $editColumn = new CustomEditColumn('EventNo', 'eventno', $editor, $this->dataset);
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
            // View column for rcmanualid field
            //
            $column = new NumberViewColumn('rcmanualid', 'rcmanualid', 'RCmanualID', $this->dataset);
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
            // View column for mdisid field
            //
            $column = new NumberViewColumn('mdisid', 'LA2', 'MDisID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for rcid field
            //
            $column = new NumberViewColumn('rcid', 'LA3', 'RCID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for eventno field
            //
            $column = new NumberViewColumn('eventno', 'eventno', 'EventNo', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
        }
    
        protected function AddExportColumns(Grid $grid)
        {
            //
            // View column for rcmanualid field
            //
            $column = new NumberViewColumn('rcmanualid', 'rcmanualid', 'RCmanualID', $this->dataset);
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
            // View column for mdisid field
            //
            $column = new NumberViewColumn('mdisid', 'LA2', 'MDisID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for rcid field
            //
            $column = new NumberViewColumn('rcid', 'LA3', 'RCID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for eventno field
            //
            $column = new NumberViewColumn('eventno', 'eventno', 'EventNo', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
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
            // View column for mdisid field
            //
            $column = new NumberViewColumn('mdisid', 'LA2', 'MDisID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for rcid field
            //
            $column = new NumberViewColumn('rcid', 'LA3', 'RCID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for eventno field
            //
            $column = new NumberViewColumn('eventno', 'eventno', 'EventNo', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
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
            $this->SetInsertFormTitle('Add New Discharge to Rating Curve');
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
 		 $this->setDetailedDescription( fread(fopen(			   "HTML/RC_Manual_Metadata.html",'r'),filesize("HTML/RC_Manual_Metadata.html")));
    
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
        $Page = new chrl_rcmanualPage("chrl_rcmanual", "rcmanual.php", GetCurrentUserPermissionsForPage("chrl.rcmanual"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.rcmanual"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
