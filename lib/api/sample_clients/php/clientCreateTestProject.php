<?php
 /**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Filename $RCSfile: clientCreateTestProject.php,v $
 *
 * @version $Revision: 1.4 $
 * @modified $Date: 2010/10/23 09:44:59 $ by $Author: franciscom $
 * @Author: francisco.mancardi@gmail.com
 *
 * @internal revisions
 * now more parameters on interface
 * 
 */
require_once 'util.php';
require_once 'sample.inc.php';
show_api_db_sample_msg();

$method='createTestProject';
$test_num = 0;

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";
$prefix = 'AXECX';

$args=array();
$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";

$dummy = '';
$additionalInfo = $dummy;
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";

echo $unitTestDescription . ' ' . $additionalInfo;

$debug=true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";


$args=array();
// requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
$args["options"] = array('requirementsEnabled' => 0);
$dummy = 'Options[';
foreach($args["options"] as $key => $value)
{
	$dummy .= $key . ' -> ' . $value . ' ';
}
$dummy .= "] ";

$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";
$args["active"] = 0;
$args["public"] = 0;

$additionalInfo = $dummy . " active -> {$args['active']}, public -> {$args['public']}";
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";


echo $unitTestDescription . ' ' . $additionalInfo;

$debug = true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";

$args=array();
// requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
$args["options"] = array('requirementsEnabled' => 0, 'testPriorityEnabled' => 0);
$dummy = 'Options[';
foreach($args["options"] as $key => $value)
{
	$dummy .= $key . ' -> ' . $value . ' ';
}
$dummy .= "] ";

$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";
$args["active"] = 0;
$args["public"] = 0;

$additionalInfo = $dummy . " active -> {$args['active']}, public -> {$args['public']}";
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";

echo $unitTestDescription . ' ' . $additionalInfo;

$debug=true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";

$args=array();

// requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
$args["options"] = array('requirementsEnabled' => 0, 'testPriorityEnabled' => 0,
						 'automationEnabled' => 0 ,'inventoryEnabled' => 0 );
$dummy = 'Options[';
foreach($args["options"] as $key => $value)
{
	$dummy .= $key . ' -> ' . $value . ' ';
}
$dummy .= "] ";


$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";
$args["active"] = 0;
$args["public"] = 0;

$additionalInfo = $dummy . " active -> {$args['active']}, public -> {$args['public']}";
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";


echo $unitTestDescription . ' ' . $additionalInfo;

$debug=true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";

$args=array();

// requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
$args["options"] = array('requirementsEnabled' => 0, 'testPriorityEnabled' => 0,
						 'automationEnabled' => 0 ,'inventoryEnabled' => 0 );
$dummy = 'Options[';
foreach($args["options"] as $key => $value)
{
	$dummy .= $key . ' -> ' . $value . ' ';
}
$dummy .= "] ";


$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";
$args["active"] = 0;
$args["public"] = 1;

$additionalInfo = $dummy . " active -> {$args['active']}, public -> {$args['public']}";
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";


echo $unitTestDescription . ' ' . $additionalInfo;

$debug=true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";

$args=array();

// requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
$args["options"] = array('requirementsEnabled' => 0, 'testPriorityEnabled' => 0,
						 'automationEnabled' => 0 ,'inventoryEnabled' => 0 );
$dummy = 'Options[';
foreach($args["options"] as $key => $value)
{
	$dummy .= $key . ' -> ' . $value . ' ';
}
$dummy .= "] ";


$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";
$args["active"] = 1;
$args["public"] = 0;

$additionalInfo = $dummy . " active -> {$args['active']}, public -> {$args['public']}";
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";


echo $unitTestDescription . ' ' . $additionalInfo;

$debug=true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}() ::: ";

$args=array();

// requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
$args["options"] = array('requirementsEnabled' => 0, 'testPriorityEnabled' => 0,
						 'automationEnabled' => 0 ,'inventoryEnabled' => 0 );
$dummy = 'Options[';
foreach($args["options"] as $key => $value)
{
	$dummy .= $key . ' -> ' . $value . ' ';
}
$dummy .= "] ";


$args["devKey"]=DEV_KEY;
$args["testcaseprefix"] = $prefix . $test_num;
$args["testprojectname"] = "API Methods Test Project {$args['testcaseprefix']}";
$args["active"] = 1;
$args["public"] = 1;

$additionalInfo = $dummy . " active -> {$args['active']}, public -> {$args['public']}";
$args["notes"]="test project created using XML-RPC-API - <br> {$additionalInfo}";


echo $unitTestDescription . ' ' . $additionalInfo;

$debug=true;
$client = new IXR_Client($server_url);
$client->debug=$debug;
runTest($client,$method,$args);
// ------------------------------------------------------------------------------------

?>