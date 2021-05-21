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
    
    
    
    class chrl_site_descriptionPage extends Page
    {
        protected function DoBeforeCreate()
        {
            $this->SetTitle('Site Description');
            $this->SetMenuLabel('Site Description');
    
            $this->dataset = new TableDataset(
                PgConnectionFactory::getInstance(),
                GetConnectionOptions(),
                '"chrl"."site_description"');
            $this->dataset->addFields(
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
                new FilterColumn($this->dataset, 'siteid', 'siteid', 'SiteID'),
                new FilterColumn($this->dataset, 'install_date', 'install_date', 'Install Date'),
                new FilterColumn($this->dataset, 'lat', 'lat', 'Lat'),
                new FilterColumn($this->dataset, 'lon', 'lon', 'Lon'),
                new FilterColumn($this->dataset, 'elevation', 'elevation', 'Elevation'),
                new FilterColumn($this->dataset, 'width_d', 'width_d', 'Width D'),
                new FilterColumn($this->dataset, 'max_depth_d', 'max_depth_d', 'Max Depth D'),
                new FilterColumn($this->dataset, 'width_ec', 'width_ec', 'Width EC'),
                new FilterColumn($this->dataset, 'max_depth_ec', 'max_depth_ec', 'Max Depth EC'),
                new FilterColumn($this->dataset, 'slope', 'slope', 'Slope'),
                new FilterColumn($this->dataset, 'dist_d_ec', 'dist_d_ec', 'Dist D EC'),
                new FilterColumn($this->dataset, 'active', 'active', 'Active'),
                new FilterColumn($this->dataset, 'deactivation_date', 'deactivation_date', 'Deactivation Date')
            );
        }
    
        protected function setupQuickFilter(QuickFilter $quickFilter, FixedKeysArray $columns)
        {
            $quickFilter
                ->addColumn($columns['siteid'])
                ->addColumn($columns['install_date'])
                ->addColumn($columns['lat'])
                ->addColumn($columns['lon'])
                ->addColumn($columns['elevation'])
                ->addColumn($columns['width_d'])
                ->addColumn($columns['max_depth_d'])
                ->addColumn($columns['width_ec'])
                ->addColumn($columns['max_depth_ec'])
                ->addColumn($columns['slope'])
                ->addColumn($columns['dist_d_ec'])
                ->addColumn($columns['active'])
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
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('Primary Key');
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
            // View column for lat field
            //
            $column = new NumberViewColumn('lat', 'lat', 'Lat', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for lon field
            //
            $column = new NumberViewColumn('lon', 'lon', 'Lon', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for elevation field
            //
            $column = new NumberViewColumn('elevation', 'elevation', 'Elevation', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for width_d field
            //
            $column = new NumberViewColumn('width_d', 'width_d', 'Width D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
            $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for max_depth_d field
            //
            $column = new NumberViewColumn('max_depth_d', 'max_depth_d', 'Max Depth D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
		 $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for width_ec field
            //
            $column = new NumberViewColumn('width_ec', 'width_ec', 'Width EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
		 $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for max_depth_ec field
            //
            $column = new NumberViewColumn('max_depth_ec', 'max_depth_ec', 'Max Depth EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
		 $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for slope field
            //
            $column = new NumberViewColumn('slope', 'slope', 'Slope', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE);
		 $column->SetDescription('');
            $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for dist_d_ec field
            //
            $column = new NumberViewColumn('dist_d_ec', 'dist_d_ec', 'Dist D EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $column->setMinimalVisibility(ColumnVisibility::PHONE); 
		 $column->SetDescription('');
           	 $column->SetFixedWidth(null);
            $grid->AddViewColumn($column);
            
            //
            // View column for active field
            //
            $column = new TextViewColumn('active', 'active', 'Active', $this->dataset);
            $column->SetOrderable(true);
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
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for lat field
            //
            $column = new NumberViewColumn('lat', 'lat', 'Lat', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for lon field
            //
            $column = new NumberViewColumn('lon', 'lon', 'Lon', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for elevation field
            //
            $column = new NumberViewColumn('elevation', 'elevation', 'Elevation', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for width_d field
            //
            $column = new NumberViewColumn('width_d', 'width_d', 'Width D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for max_depth_d field
            //
            $column = new NumberViewColumn('max_depth_d', 'max_depth_d', 'Max Depth D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for width_ec field
            //
            $column = new NumberViewColumn('width_ec', 'width_ec', 'Width EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for max_depth_ec field
            //
            $column = new NumberViewColumn('max_depth_ec', 'max_depth_ec', 'Max Depth EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for slope field
            //
            $column = new NumberViewColumn('slope', 'slope', 'Slope', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for dist_d_ec field
            //
            $column = new NumberViewColumn('dist_d_ec', 'dist_d_ec', 'Dist D EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddSingleRecordViewColumn($column);
            
            //
            // View column for active field
            //
            $column = new TextViewColumn('active', 'active', 'Active', $this->dataset);
            $column->SetOrderable(true);
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
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for install_date field
            //
            $editor = new DateTimeEdit('install_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Install Date', 'install_date', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for lat field
            //
            $editor = new TextEdit('lat_edit');
            $editor->SetPlaceholder('decimal degrees');
            $editColumn = new CustomEditColumn('Lat', 'lat', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for lon field
            //
            $editor = new TextEdit('lon_edit');
            $editor->SetPlaceholder('decimal degrees');
            $editColumn = new CustomEditColumn('Lon', 'lon', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for elevation field
            //
            $editor = new TextEdit('elevation_edit');
            $editor->SetPlaceholder('m a.s.l.');
            $editColumn = new CustomEditColumn('Elevation', 'elevation', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for width_d field
            //
            $editor = new TextEdit('width_d_edit');
            $editor->SetPlaceholder('Stream width at dumper location [m]');
            $editColumn = new CustomEditColumn('Width D', 'width_d', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for max_depth_d field
            //
            $editor = new TextEdit('max_depth_d_edit');
            $editor->SetPlaceholder('Maximum stream depth at dumper location [m]');
            $editColumn = new CustomEditColumn('Max Depth D', 'max_depth_d', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for width_ec field
            //
            $editor = new TextEdit('width_ec_edit');
            $editor->SetPlaceholder('Stream width at EC sensor location [m]');
            $editColumn = new CustomEditColumn('Width EC', 'width_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for max_depth_ec field
            //
            $editor = new TextEdit('max_depth_ec_edit');
            $editor->SetPlaceholder('Maximum stream depth at EC sensor location [m]');
            $editColumn = new CustomEditColumn('Max Depth EC', 'max_depth_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for slope field
            //
            $editor = new TextEdit('slope_edit');
            $editor->SetPlaceholder('Bed slope between the dumper and EC sensors [%]');
            $editColumn = new CustomEditColumn('Slope', 'slope', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for dist_d_ec field
            //
            $editor = new TextEdit('dist_d_ec_edit');
            $editor->SetPlaceholder('Downstream distance between dumper and EC sensor locations [m]');
            $editColumn = new CustomEditColumn('Dist D EC', 'dist_d_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $editColumn->setAllowListCellEdit(false);
            $editColumn->setAllowSingleViewCellEdit(false);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddEditColumn($editColumn);
            
            //
            // Edit column for active field
            //
            $editor = new ComboBox('active_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Active', 'active', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
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
            // Edit column for install_date field
            //
            $editor = new DateTimeEdit('install_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Install Date', 'install_date', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for lat field
            //
            $editor = new TextEdit('lat_edit');
            $editor->SetPlaceholder('decimal degrees');
            $editColumn = new CustomEditColumn('Lat', 'lat', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for lon field
            //
            $editor = new TextEdit('lon_edit');
            $editor->SetPlaceholder('decimal degrees');
            $editColumn = new CustomEditColumn('Lon', 'lon', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for elevation field
            //
            $editor = new TextEdit('elevation_edit');
            $editor->SetPlaceholder('m a.s.l.');
            $editColumn = new CustomEditColumn('Elevation', 'elevation', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for width_d field
            //
            $editor = new TextEdit('width_d_edit');
            $editor->SetPlaceholder('Stream width at dumper location [m]');
            $editColumn = new CustomEditColumn('Width D', 'width_d', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for max_depth_d field
            //
            $editor = new TextEdit('max_depth_d_edit');
            $editor->SetPlaceholder('Maximum stream depth at dumper location [m]');
            $editColumn = new CustomEditColumn('Max Depth D', 'max_depth_d', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for width_ec field
            //
            $editor = new TextEdit('width_ec_edit');
            $editor->SetPlaceholder('Stream width at EC sensor location [m]');
            $editColumn = new CustomEditColumn('Width EC', 'width_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for max_depth_ec field
            //
            $editor = new TextEdit('max_depth_ec_edit');
            $editor->SetPlaceholder('Maximum stream depth at EC sensor location [m]');
            $editColumn = new CustomEditColumn('Max Depth EC', 'max_depth_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for slope field
            //
            $editor = new TextEdit('slope_edit');
            $editor->SetPlaceholder('Bed slope between the dumper and EC sensors [%]');
            $editColumn = new CustomEditColumn('Slope', 'slope', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for dist_d_ec field
            //
            $editor = new TextEdit('dist_d_ec_edit');
            $editor->SetPlaceholder('Downstream distance between dumper and EC sensor locations [m]');
            $editColumn = new CustomEditColumn('Dist D EC', 'dist_d_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddMultiEditColumn($editColumn);
            
            //
            // Edit column for active field
            //
            $editor = new ComboBox('active_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Active', 'active', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
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
            $editor = new TextEdit('siteid_edit');
            $editColumn = new CustomEditColumn('SiteID', 'siteid', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for install_date field
            //
            $editor = new DateTimeEdit('install_date_edit', false, 'Y-m-d');
            $editColumn = new CustomEditColumn('Install Date', 'install_date', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for lat field
            //
            $editor = new TextEdit('lat_edit');
            $editor->SetPlaceholder('decimal degrees');
            $editColumn = new CustomEditColumn('Lat', 'lat', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for lon field
            //
            $editor = new TextEdit('lon_edit');
            $editor->SetPlaceholder('decimal degrees');
            $editColumn = new CustomEditColumn('Lon', 'lon', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for elevation field
            //
            $editor = new TextEdit('elevation_edit');
            $editor->SetPlaceholder('m a.s.l.');
            $editColumn = new CustomEditColumn('Elevation', 'elevation', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for width_d field
            //
            $editor = new TextEdit('width_d_edit');
            $editor->SetPlaceholder('Stream width at dumper location [m]');
            $editColumn = new CustomEditColumn('Width D', 'width_d', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for max_depth_d field
            //
            $editor = new TextEdit('max_depth_d_edit');
            $editor->SetPlaceholder('Maximum stream depth at dumper location [m]');
            $editColumn = new CustomEditColumn('Max Depth D', 'max_depth_d', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for width_ec field
            //
            $editor = new TextEdit('width_ec_edit');
            $editor->SetPlaceholder('Stream width at EC sensor location [m]');
            $editColumn = new CustomEditColumn('Width EC', 'width_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for max_depth_ec field
            //
            $editor = new TextEdit('max_depth_ec_edit');
            $editor->SetPlaceholder('Maximum stream depth at EC sensor location [m]');
            $editColumn = new CustomEditColumn('Max Depth EC', 'max_depth_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for slope field
            //
            $editor = new TextEdit('slope_edit');
            $editor->SetPlaceholder('Bed slope between the dumper and EC sensors [%]');
            $editColumn = new CustomEditColumn('Slope', 'slope', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for dist_d_ec field
            //
            $editor = new TextEdit('dist_d_ec_edit');
            $editor->SetPlaceholder('Downstream distance between dumper and EC sensor locations [m]');
            $editColumn = new CustomEditColumn('Dist D EC', 'dist_d_ec', $editor, $this->dataset);
            $editColumn->SetAllowSetToNull(true);
            $this->ApplyCommonColumnEditProperties($editColumn);
            $grid->AddInsertColumn($editColumn);
            
            //
            // Edit column for active field
            //
            $editor = new ComboBox('active_edit', $this->GetLocalizerCaptions()->GetMessageString('PleaseSelect'));
            $editor->addChoice('Y', 'Y');
            $editor->addChoice('N', 'N');
            $editColumn = new CustomEditColumn('Active', 'active', $editor, $this->dataset);
            $validator = new RequiredValidator(StringUtils::Format($this->GetLocalizerCaptions()->GetMessageString('RequiredValidationMessage'), $editColumn->GetCaption()));
            $editor->GetValidatorCollection()->AddValidator($validator);
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
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddPrintColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddPrintColumn($column);
            
            //
            // View column for lat field
            //
            $column = new NumberViewColumn('lat', 'lat', 'Lat', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for lon field
            //
            $column = new NumberViewColumn('lon', 'lon', 'Lon', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for elevation field
            //
            $column = new NumberViewColumn('elevation', 'elevation', 'Elevation', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for width_d field
            //
            $column = new NumberViewColumn('width_d', 'width_d', 'Width D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for max_depth_d field
            //
            $column = new NumberViewColumn('max_depth_d', 'max_depth_d', 'Max Depth D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for width_ec field
            //
            $column = new NumberViewColumn('width_ec', 'width_ec', 'Width EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for max_depth_ec field
            //
            $column = new NumberViewColumn('max_depth_ec', 'max_depth_ec', 'Max Depth EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for slope field
            //
            $column = new NumberViewColumn('slope', 'slope', 'Slope', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for dist_d_ec field
            //
            $column = new NumberViewColumn('dist_d_ec', 'dist_d_ec', 'Dist D EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddPrintColumn($column);
            
            //
            // View column for active field
            //
            $column = new TextViewColumn('active', 'active', 'Active', $this->dataset);
            $column->SetOrderable(true);
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
            // View column for siteid field
            //
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddExportColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddExportColumn($column);
            
            //
            // View column for lat field
            //
            $column = new NumberViewColumn('lat', 'lat', 'Lat', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for lon field
            //
            $column = new NumberViewColumn('lon', 'lon', 'Lon', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for elevation field
            //
            $column = new NumberViewColumn('elevation', 'elevation', 'Elevation', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for width_d field
            //
            $column = new NumberViewColumn('width_d', 'width_d', 'Width D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for max_depth_d field
            //
            $column = new NumberViewColumn('max_depth_d', 'max_depth_d', 'Max Depth D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for width_ec field
            //
            $column = new NumberViewColumn('width_ec', 'width_ec', 'Width EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for max_depth_ec field
            //
            $column = new NumberViewColumn('max_depth_ec', 'max_depth_ec', 'Max Depth EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for slope field
            //
            $column = new NumberViewColumn('slope', 'slope', 'Slope', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for dist_d_ec field
            //
            $column = new NumberViewColumn('dist_d_ec', 'dist_d_ec', 'Dist D EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddExportColumn($column);
            
            //
            // View column for active field
            //
            $column = new TextViewColumn('active', 'active', 'Active', $this->dataset);
            $column->SetOrderable(true);
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
            $column = new NumberViewColumn('siteid', 'siteid', 'SiteID', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(0);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('');
            $grid->AddCompareColumn($column);
            
            //
            // View column for install_date field
            //
            $column = new DateTimeViewColumn('install_date', 'install_date', 'Install Date', $this->dataset);
            $column->SetOrderable(true);
            $column->SetDateTimeFormat('Y-m-d');
            $grid->AddCompareColumn($column);
            
            //
            // View column for lat field
            //
            $column = new NumberViewColumn('lat', 'lat', 'Lat', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for lon field
            //
            $column = new NumberViewColumn('lon', 'lon', 'Lon', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(4);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for elevation field
            //
            $column = new NumberViewColumn('elevation', 'elevation', 'Elevation', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(1);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for width_d field
            //
            $column = new NumberViewColumn('width_d', 'width_d', 'Width D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for max_depth_d field
            //
            $column = new NumberViewColumn('max_depth_d', 'max_depth_d', 'Max Depth D', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for width_ec field
            //
            $column = new NumberViewColumn('width_ec', 'width_ec', 'Width EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for max_depth_ec field
            //
            $column = new NumberViewColumn('max_depth_ec', 'max_depth_ec', 'Max Depth EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for slope field
            //
            $column = new NumberViewColumn('slope', 'slope', 'Slope', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for dist_d_ec field
            //
            $column = new NumberViewColumn('dist_d_ec', 'dist_d_ec', 'Dist D EC', $this->dataset);
            $column->SetOrderable(true);
            $column->setNumberAfterDecimal(2);
            $column->setThousandsSeparator('');
            $column->setDecimalSeparator('.');
            $grid->AddCompareColumn($column);
            
            //
            // View column for active field
            //
            $column = new TextViewColumn('active', 'active', 'Active', $this->dataset);
            $column->SetOrderable(true);
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
            $defaultSortedColumns[] = new SortColumn('active', 'ASC');
            $defaultSortedColumns[] = new SortColumn('siteid', 'ASC');
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
            $this->SetInsertFormTitle('Add New Site');
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
 		 $this->setDetailedDescription( fread(fopen(			   "HTML/Site_Description_Metadata.html",'r'),filesize("HTML/Site_Description_Metadata.html")));
    
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
        $Page = new chrl_site_descriptionPage("chrl_site_description", "site_description.php", GetCurrentUserPermissionsForPage("chrl.site_description"), 'UTF-8');
        $Page->SetRecordPermission(GetCurrentUserRecordPermissionsForDataSource("chrl.site_description"));
        GetApplication()->SetMainPage($Page);
        GetApplication()->Run();
    }
    catch(Exception $e)
    {
        ShowErrorPage($e);
    }
	
