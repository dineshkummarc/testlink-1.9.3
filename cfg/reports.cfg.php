<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * @filesource	reports.cfg.php
 * @author Martin Havlat
 *
 * SCOPE: Definition of report/metrics menu 
 * 
 * @internal revisions:
 *	20110312 - franciscom - added logic to include custom_reports.cfg.php
 *  20100903 - Julian - BUGID 37006 - disabled uncovered_testcases report 
 *  20100731 - asimon - added results by tester per build and assignment overview
 *  20090421 - amitkhullar- BUGID 2410 - Custom Field report for Test Plan
 *  20090414 - franciscom - BUGID 2363 - free test cases i.e. not assigned to a test plan.
 *  20081227 - franciscom - added tcases_without_tester
 *  20081213 - franciscom - replace of old $g_ variables
 *  20081109 - franciscom - added uncovered_testcases
 * 	20080813 - havlatm - removed metrics_tp_builds
 *
 * *********************************************************************************** */

/** type of documents */
define('DOC_TEST_SPEC', 'testspec');
define('DOC_TEST_PLAN', 'testplan');
define('DOC_TEST_REPORT', 'testreport');
define('DOC_REQ_SPEC', 'reqspec');
define('SINGLE_TESTCASE', 'testcase');
define('SINGLE_REQ', 'requirement');
define('SINGLE_REQSPEC', 'single_reqspec');

define('FORMAT_HTML', 0);
define('FORMAT_ODT', 1);
define('FORMAT_ODS', 2);
define('FORMAT_XLS', 3);
define('FORMAT_MSWORD', 4);
define('FORMAT_PDF', 5);
define('FORMAT_MAIL_HTML', 6);

/** supported document formats (value = localization ID) */
$tlCfg->reports_formats = array(
	FORMAT_HTML => 'format_html',
	FORMAT_ODT => 'format_odt', 
	FORMAT_ODS => 'format_ods', 
	FORMAT_XLS => 'format_xls', 
	FORMAT_MSWORD => 'format_msword',
//	FORMAT_PDF => 'format_pdf', not implemented yet
	FORMAT_MAIL_HTML => 'format_mail_html'
);

/** Mime Content Type */
$tlCfg->reports_applications = array(
	FORMAT_HTML => 'text/html',
	FORMAT_ODT => 'application/vnd.oasis.opendocument.text', 
	FORMAT_ODS => 'application/vnd.oasis.opendocument.spreadsheet', 
	FORMAT_XLS => 'application/vnd.ms-excel', 
	FORMAT_MSWORD => 'application/vnd.ms-word',
	FORMAT_PDF => 'application/pdf'
);

/** Report file extenssion */
$tlCfg->reports_file_extension = array(
	FORMAT_HTML => 'html', 
	FORMAT_ODT => 'odt', 
	FORMAT_ODS => 'ods', 
	FORMAT_XLS => 'xls', 
	FORMAT_MSWORD => 'doc',
	FORMAT_PDF => 'pdf',
);


/** 
 * @VAR $tlCfg->reports_list['report_identifier'] 
 * definition of default set of reports
 * title - title string identifier
 * url - http path (without testPlanId and format)
 * enabled - availability
 * 	1. all (everytime),
 * 	2. bts (if bug tracker is connected only), 
 * 	3. req (if project has available requirements only)
 */
$tlCfg->reports_list['test_plan'] = array( 
	'title' => 'link_report_test_plan',
	'url' => 'lib/results/printDocOptions.php?type='.DOC_TEST_PLAN,
	'enabled' => 'all',
	'format' => 'format_html,format_odt,format_msword'
);
$tlCfg->reports_list['test_report'] = array( 
	'title' => 'link_report_test_report',
	'url' => 'lib/results/printDocOptions.php?type='.DOC_TEST_REPORT,
	'enabled' => 'all',
	'format' => 'format_html,format_odt,format_msword'
);
$tlCfg->reports_list['metrics_tp_general'] = array( 
	'title' => 'link_report_general_tp_metrics',
	'url' => 'lib/results/resultsGeneral.php',
	'enabled' => 'all',
	'format' => 'format_html,format_ods,format_xls,format_mail_html'
);
$tlCfg->reports_list['results_by_tester_per_build'] = array( 
	'title' => 'link_report_by_tester_per_build',
	'url' => 'lib/results/resultsByTesterPerBuild.php',
	'enabled' => 'all',
	'format' => 'format_html'
);
$tlCfg->reports_list['assigned_tc_overview'] = array( 
	'title' => 'link_assigned_tc_overview',
	'url' => 'lib/testcases/tcAssignedToUser.php?show_all_users=1&show_inactive_and_closed=1',
	'enabled' => 'all',
	'format' => 'format_html'
);
$tlCfg->reports_list['results_custom_query'] = array( 
	'title' => 'link_report_metrics_more_builds',
	'url' => 'lib/results/resultsMoreBuildsGUI.php',
	'enabled' => 'all',
	'format' => 'format_html,format_ods,format_xls,format_mail_html'
);
$tlCfg->reports_list['results_matrix'] = array( 
	'title' => 'link_report_test',
	'url' => 'lib/results/resultsTC.php',
	'enabled' => 'all',
	'format' => 'format_html,format_ods,format_xls,format_mail_html'
);
$tlCfg->reports_list['list_tc_failed'] = array( 
	'title' => 'link_report_failed',
	'url' => 'lib/results/resultsByStatus.php?type=' . $tlCfg->results['status_code']['failed'],
	'enabled' => 'all',
	'format' => 'format_html,format_ods,format_xls,format_mail_html'
);
$tlCfg->reports_list['list_tc_blocked'] = array( 
	'title' => 'link_report_blocked_tcs',
	'url' => 'lib/results/resultsByStatus.php?type=' . $tlCfg->results['status_code']['blocked'],
	'enabled' => 'all',
	'format' => 'format_html,format_ods,format_xls,format_mail_html'
);
$tlCfg->reports_list['list_tc_norun'] = array( 
	'title' => 'link_report_not_run',
	'url' => 'lib/results/resultsByStatus.php?type=' . $tlCfg->results['status_code']['not_run'],
	'enabled' => 'all',
	'format' => 'format_html,format_ods,format_xls,format_mail_html'
);
$tlCfg->reports_list['tcases_without_tester'] = array( 
	'title' => 'link_report_tcases_without_tester',
	'url' => 'lib/results/testCasesWithoutTester.php',
	'enabled' => 'all',
	'format' => 'format_html'
);
$tlCfg->reports_list['charts_basic'] = array( 
	'title' => 'link_charts',
	'url' => 'lib/results/charts.php',
	'enabled' => 'all',
	'format' => 'format_html'
);
$tlCfg->reports_list['results_requirements'] = array( 
	'title' => 'link_report_reqs_coverage',
	'url' => 'lib/results/resultsReqs.php',
	'enabled' => 'req',
	'format' => 'format_html'
);
//$tlCfg->reports_list['uncovered_testcases'] = array( 
//	'title' => 'link_report_uncovered_testcases',
//	'url' => 'lib/results/uncoveredTestCases.php',
//	'enabled' => 'req',
//	'format' => 'format_html'
//);
$tlCfg->reports_list['list_problems'] = array( 
	'title' => 'link_report_total_bugs',
	'url' => 'lib/results/resultsBugs.php',
	'enabled' => 'bts',
	'format' => 'format_html'
);
$tlCfg->reports_list['tcases_with_cf'] = array( 
	'title' => 'link_report_tcases_with_cf',
	'url' => 'lib/results/testCasesWithCF.php',
	'enabled' => 'all',
	'format' => 'format_html'
);
$tlCfg->reports_list['tplan_with_cf'] = array( 
	'title' => 'link_report_tplans_with_cf',
	'url' => 'lib/results/testPlanWithCF.php',
	'enabled' => 'all',
	'format' => 'format_html'
);

$tlCfg->reports_list['free_tcases'] = array( 
'title' => 'link_report_free_testcases_on_testproject',
'url' => 'lib/results/freeTestCases.php',
'enabled' => 'all',
'format' => 'format_html'
);


clearstatcache();
$f2inc = TL_ABS_PATH . 'cfg/custom_reports.cfg.php';
if ( file_exists($f2inc) )
{
  require_once($f2inc);
}
// -------------------------------------------------------------------
?>