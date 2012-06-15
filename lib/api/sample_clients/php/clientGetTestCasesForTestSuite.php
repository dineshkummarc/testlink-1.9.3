<?php
 /**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Filename $RCSfile: clientGetTestCasesForTestSuite.php,v $
 *
 * @version $Revision: 1.3 $
 * @modified $Date: 2009/05/01 20:36:56 $ by $Author: franciscom $
 * @Author: francisco.mancardi@gmail.com
 *
 * rev: 
 */
 
require_once 'util.php';
require_once 'sample.inc.php';
show_api_db_sample_msg();

$method='getTestCasesForTestSuite';
$test_num=1;
$unitTestDescription="Test {$test_num} - {$method}";

$args=array();
$args["devKey"]=DEV_KEY;
$args["testprojectid"]=1;
$args["testsuiteid"]=186;
$args["deep"]=true;
$args["details"]='full';

$additionalInfo=' Parameter deep = ' . $args["deep"];

$debug=true;
echo $unitTestDescription;
echo $additionalInfo;

$client = new IXR_Client($server_url);
$client->debug=$debug;

runTest($client,$method,$args);

// ---------------------------------------------------------------------------------
$method='getTestCasesForTestSuite';
$test_num++;
$unitTestDescription="Test {$test_num} - {$method}";

$args=array();
$args["devKey"]=DEV_KEY;
$args["testprojectid"]=1;
$args["testsuiteid"]=186;
$args["deep"]=false;
$args["details"]='simple';

$additionalInfo=' Parameter deep = ' . $args["deep"];

$debug=true;
echo $unitTestDescription;
echo $additionalInfo;

runTest($client,$method,$args);
?>