<?php

//  define('SHOW_VARIABLES', 1);
//  define('DEBUG_LEVEL', 1);

//  error_reporting(E_ALL ^ E_NOTICE);
//  ini_set('display_errors', 'On');

set_include_path('.' . PATH_SEPARATOR . get_include_path());


include_once dirname(__FILE__) . '/' . 'components/utils/system_utils.php';
include_once dirname(__FILE__) . '/' . 'components/mail/mailer.php';
include_once dirname(__FILE__) . '/' . 'components/mail/phpmailer_based_mailer.php';
require_once dirname(__FILE__) . '/' . 'database_engine/pgsql_engine.php';

//  SystemUtils::DisableMagicQuotesRuntime();

SystemUtils::SetTimeZoneIfNeed('America/Los_Angeles');

function GetGlobalConnectionOptions()
{  $config= include 'config.php';
	
    return $config ;
 }



function HasAdminPage()
{
    return false;
}

function HasHomePage()
{
    return true;
}

function GetHomeURL()
{
    return 'index.php';
}

function GetHomePageBanner()
{
    return '<h1><center> Autosalt Project</center></h1>
<h4><center> The Hakai Institute and Vancouver Island University</center></h4>';
}

function GetPageGroups()
{
    $result = array();
    $result[] = array('caption' => 'General Information', 'description' => '');
    $result[] = array('caption' => 'Calibration Factors', 'description' => '');
    $result[] = array('caption' => 'Stream Discharge', 'description' => '');
    $result[] = array('caption' => 'Rating Curve', 'description' => '');
    return $result;
}

function GetPageInfos()
{
    $result = array();
    $result[] = array('caption' => 'Autosalt Summary', 'short_caption' => 'Autosalt Summary', 'filename' => 'autosalt_summary.php', 'name' => 'chrl.autosalt_summary', 'group_name' => 'Stream Discharge', 'add_separator' => false, 'description' => '<i> Summary of all autosalt events </i>');
    $result[] = array('caption' => 'All Discharge Calcs', 'short_caption' => 'All Discharge Calcs', 'filename' => 'all_discharge_calcs.php', 'name' => 'chrl.all_discharge_calcs', 'group_name' => 'Stream Discharge', 'add_separator' => false, 'description' => '<i> Record of all calculated discharges </i>');
    $result[] = array('caption' => 'Salt Waves', 'short_caption' => 'Salt Waves', 'filename' => 'salt_waves.php', 'name' => 'chrl.salt_waves', 'group_name' => 'Stream Discharge', 'add_separator' => false, 'description' => '<i> Summary of EC waves for each autosalt event </i>');
    $result[] = array('caption' => 'Autosalt Forms', 'short_caption' => 'Autosalt Forms', 'filename' => 'autosalt_forms.php', 'name' => 'chrl.autosalt_forms', 'group_name' => 'Stream Discharge', 'add_separator' => false, 'description' => '<i> Links to excel sheets used for discharge calculations </i>');
    $result[] = array('caption' => 'Manual Discharge', 'short_caption' => 'Manual Discharge', 'filename' => 'manual_discharge.php', 'name' => 'chrl.manual_discharge', 'group_name' => 'Stream Discharge', 'add_separator' => false, 'description' => '<i> Record of all manually collected discharges</i>');
    $result[] = array('caption' => 'Calibration Events', 'short_caption' => 'Calibration Events', 'filename' => 'calibration_events.php', 'name' => 'chrl.calibration_events', 'group_name' => 'Calibration Factors', 'add_separator' => false, 'description' => '<i> Record of all calibration events </i>');
    $result[] = array('caption' => 'Calibration Results', 'short_caption' => 'Calibration Results', 'filename' => 'calibration_results.php', 'name' => 'chrl.calibration_results', 'group_name' => 'Calibration Factors', 'add_separator' => false, 'description' => '<i> All calibration factor results </i>');
    $result[] = array('caption' => 'RC Autosalt', 'short_caption' => 'RC Autosalt', 'filename' => 'rcautosalt.php', 'name' => 'chrl.rcautosalt', 'group_name' => 'Rating Curve', 'add_separator' => false, 'description' => '<i> Record of all autosalt events used in rating curves </i>');
    $result[] = array('caption' => 'RC Manual', 'short_caption' => 'RC Manual', 'filename' => 'rcmanual.php', 'name' => 'chrl.rcmanual', 'group_name' => 'Rating Curve', 'add_separator' => false, 'description' => '<i> Record of all manually collected discharges used in rating curves </i>');
    $result[] = array('caption' => 'Site Description', 'short_caption' => 'Site Description', 'filename' => 'site_description.php', 'name' => 'chrl.site_description', 'group_name' => 'General Information', 'add_separator' => false, 'description' => '<i>General information about autosalt sites</i>');
    $result[] = array('caption' => 'Barrel Periods', 'short_caption' => 'Barrel Periods', 'filename' => 'barrel_periods.php', 'name' => 'chrl.barrel_periods', 'group_name' => 'General Information', 'add_separator' => false, 'description' => '<i>Record of barrel fills at autosalt sites</i>');
    $result[] = array('caption' => 'Sensors', 'short_caption' => 'Sensors', 'filename' => 'sensors.php', 'name' => 'chrl.sensors', 'group_name' => 'General Information', 'add_separator' => false, 'description' => '<i> Record of sensors deployed at autosalt sites </i>');
    $result[] = array('caption' => 'RC Summary', 'short_caption' => 'RC Summary', 'filename' => 'rc_summary.php', 'name' => 'chrl.rc_summary', 'group_name' => 'General Information', 'add_separator' => false, 'description' => '<i> General information about rating curve versions </i>');
    return $result;
}

function GetPagesHeader()
{
    return
        '';
}

function GetPagesFooter()
{
    return
        '';
}

function ApplyCommonPageSettings(Page $page, Grid $grid)
{
    $page->SetShowUserAuthBar(true);
    $page->setShowNavigation(true);
    $page->OnGetCustomExportOptions->AddListener('Global_OnGetCustomExportOptions');
    $page->getDataset()->OnGetFieldValue->AddListener('Global_OnGetFieldValue');
    $page->getDataset()->OnGetFieldValue->AddListener('OnGetFieldValue', $page);
    $grid->BeforeUpdateRecord->AddListener('Global_BeforeUpdateHandler');
    $grid->BeforeDeleteRecord->AddListener('Global_BeforeDeleteHandler');
    $grid->BeforeInsertRecord->AddListener('Global_BeforeInsertHandler');
    $grid->AfterUpdateRecord->AddListener('Global_AfterUpdateHandler');
    $grid->AfterDeleteRecord->AddListener('Global_AfterDeleteHandler');
    $grid->AfterInsertRecord->AddListener('Global_AfterInsertHandler');
}

function GetAnsiEncoding() { return 'windows-1252'; }

function Global_AddEnvironmentVariablesHandler(&$variables)
{

}

function Global_CustomHTMLHeaderHandler($page, &$customHtmlHeaderText)
{

}

function Global_GetCustomTemplateHandler($type, $part, $mode, &$result, &$params, CommonPage $page = null)
{

}

function Global_OnGetCustomExportOptions($page, $exportType, $rowData, &$options)
{

}

function Global_OnGetFieldValue($fieldName, &$value, $tableName)
{

}

function Global_GetCustomPageList(CommonPage $page, PageList $pageList)
{

}

function Global_BeforeInsertHandler($page, &$rowData, $tableName, &$cancel, &$message, &$messageDisplayTime)
{

}

function Global_BeforeUpdateHandler($page, $oldRowData, &$rowData, $tableName, &$cancel, &$message, &$messageDisplayTime)
{

}

function Global_BeforeDeleteHandler($page, &$rowData, $tableName, &$cancel, &$message, &$messageDisplayTime)
{

}

function Global_AfterInsertHandler($page, $rowData, $tableName, &$success, &$message, &$messageDisplayTime)
{

}

function Global_AfterUpdateHandler($page, $oldRowData, $rowData, $tableName, &$success, &$message, &$messageDisplayTime)
{

}

function Global_AfterDeleteHandler($page, $rowData, $tableName, &$success, &$message, &$messageDisplayTime)
{

}

function GetDefaultDateFormat()
{
    return 'Y-m-d';
}

function GetFirstDayOfWeek()
{
    return 0;
}

function GetPageListType()
{
    return PageList::TYPE_SIDEBAR;
}

function GetNullLabel()
{
    return null;
}

function UseMinifiedJS()
{
    return true;
}

function GetOfflineMode()
{
    return false;
}

function GetInactivityTimeout()
{
    return 0;
}

function GetMailer()
{

}

function sendMailMessage($recipients, $messageSubject, $messageBody, $attachments = '', $cc = '', $bcc = '')
{

}

function createConnection()
{
    $connectionOptions = GetGlobalConnectionOptions();
    $connectionOptions['client_encoding'] = 'utf8';

    $connectionFactory = PgConnectionFactory::getInstance();
    return $connectionFactory->CreateConnection($connectionOptions);
}

/**
 * @param string $pageName
 * @return IPermissionSet
 */
function GetCurrentUserPermissionsForPage($pageName) 
{
    return GetApplication()->GetCurrentUserPermissionSet($pageName);
}
