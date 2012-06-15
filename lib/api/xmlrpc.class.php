<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *  
 * @filesource 	xmlrpc.class.php
 *
 * @author 		Asiel Brumfield <asielb@users.sourceforge.net>
 * @package 	TestlinkAPI
 * 
 * Testlink API makes it possible to interact with Testlink  
 * using external applications and services. This makes it possible to report test results 
 * directly from automation frameworks as well as other features.
 * 
 * See examples for additional detail
 * @example sample_clients/java/org/testlink/api/client/sample/TestlinkAPIXMLRPCClient.java java client sample
 * @example sample_clients/php/clientSample.php php client sample
 * @example sample_clients/ruby/clientSample.rb ruby client sample
 * @example sample_clients/python/clientSample.py python client sample
 * 
 *
 * @internal revisions 
 * 20110630 - franciscom - get_linked_versions() interface changes
 * 20110309 - franciscom - BUGID 4311: typo error on uploadExecutionAttachment mapping
 */

/** 
 * IXR is the class used for the XML-RPC server 
 */
require_once(dirname(__FILE__) . "/../../third_party/xml-rpc/class-IXR.php");
require_once("api.const.inc.php");
require_once(dirname(__FILE__) . "/../../config.inc.php");
require_once(dirname(__FILE__) . "/../functions/common.php");
require_once("APIErrors.php");

/**
 * The entry class for serving XML-RPC Requests
 * 
 * See examples for additional detail
 * @example sample_clients/java/org/testlink/api/client/sample/TestlinkAPIXMLRPCClient.java java client sample
 * @example sample_clients/php/clientSample.php php client sample
 * @example sample_clients/ruby/clientSample.rb ruby client sample
 * @example sample_clients/python/clientSample.py python client sample
 * 
 * @author 		Asiel Brumfield <asielb@users.sourceforge.net>
 * @package 	TestlinkAPI 
 * @since 		Class available since Release 1.8.0
 * @version 	1.0
 */
class TestlinkXMLRPCServer extends IXR_Server
{
    public static $version = "1.0";
 
    
    const   OFF=false;
    const   ON=true;
    const   BUILD_GUESS_DEFAULT_MODE=OFF;
    const   SET_ERROR=true;
    	
	/**
	 * The DB object used throughout the class
	 * 
	 * @access protected
	 */
	protected $dbObj = null;
	protected $tables = null;

	protected $tcaseMgr =  null;
	protected $tprojectMgr = null;
	protected $tplanMgr = null;
	protected $reqSpecMgr = null;
    protected $reqMgr = null;

	/** Whether the server will run in a testing mode */
	protected $testMode = false;

	/** userID associated with the devKey provided */
	protected $userID = null;
	
	/** UserObject associated with the userID */
	protected $user = null;

	/** array where all the args are stored for requests */
	protected $args = null;	

	/** array where error codes and messages are stored */
	protected $errors = array();

	/** The api key being used to make a request */
	protected $devKey = null;
	
	/** boolean to allow a method to invoke another method and avoid double auth */
	protected $authenticated = false;

	/** The version of a test case that is being used */
	/** This value is setted in following method:     */
	/** _checkTCIDAndTPIDValid()                      */
	protected $tcVersionID = null;
	protected $versionNumber = null;
	
	
	/**#@+
	 * string for parameter names are all defined statically
	 * PLEASE define in DICTIONARY ORDER
	 * @static
 	 */
    public static $actionOnDuplicatedNameParamName = "actiononduplicatedname";
	public static $activeParamName = "active";
	public static $assignedToParamName = "assignedto";
	public static $automatedParamName = "automated";
    public static $authorLoginParamName = "authorlogin";

	public static $bugIDParamName = "bugid";		
	public static $buildIDParamName = "buildid";
	public static $buildNameParamName = "buildname";
	public static $buildNotesParamName = "buildnotes";

    public static $checkDuplicatedNameParamName = "checkduplicatedname";
    public static $contentParamName = "content";
	public static $customFieldNameParamName = "customfieldname";
    public static $customFieldsParamName = "customfields";

	public static $deepParamName = "deep";
    public static $descriptionParamName = "description";
    public static $detailsParamName = "details";
	public static $devKeyParamName = "devKey";

    public static $executionIDParamName = "executionid";
    public static $executionOrderParamName = "executionorder";
	public static $executedParamName = "executed";
	public static $executeStatusParamName = "executestatus";
    public static $executionTypeParamName = "executiontype";
    public static $expectedResultsParamName = "expectedresults";

    public static $fileNameParamName = "filename";
    public static $fileTypeParamName = "filetype";
    public static $foreignKeyIdParamName = "fkid";
    public static $foreignKeyTableNameParamName = "fktable";

	public static $guessParamName = "guess";
    public static $getStepsInfoParamName = "getstepsinfo";
    public static $importanceParamName = "importance";
    public static $internalIDParamName = "internalid";
	public static $keywordIDParamName = "keywordid";
    public static $keywordNameParamName = "keywords";

    public static $nodeIDParamName = "nodeid";
	public static $noteParamName = "notes";

    public static $optionsParamName = "options";
    public static $orderParamName = "order";
	public static $overwriteParamName = "overwrite";
	public static $parentIDParamName = "parentid";		
    public static $platformNameParamName = "platformname";
    public static $platformIDParamName = "platformid";
    public static $preconditionsParamName = "preconditions";
    public static $publicParamName = "public";

    public static $requirementsParamName = "requirements";

	public static $summaryParamName = "summary";
	public static $statusParamName = "status";
	public static $stepsParamName = "steps";

	public static $testCaseIDParamName = "testcaseid";
	public static $testCaseExternalIDParamName = "testcaseexternalid";
	public static $testCaseNameParamName = "testcasename";
	public static $testCasePathNameParamName = "testcasepathname";
	public static $testCasePrefixParamName = "testcaseprefix";
	public static $testModeParamName = "testmode";
	public static $testPlanIDParamName = "testplanid";
	public static $testPlanNameParamName = "testplanname";
	public static $testProjectIDParamName = "testprojectid";
	public static $testProjectNameParamName = "testprojectname";
	public static $testSuiteIDParamName = "testsuiteid";
	public static $testSuiteNameParamName = "testsuitename";
	public static $timeStampParamName = "timestamp";
    public static $titleParamName = "title";


    public static $urgencyParamName = "urgency";
    public static $userParamName = "user";


    public static $versionNumberParamName = "version";
    


	// public static $executionRunTypeParamName		= "executionruntype";
		
	
	/**#@-*/
	
	/**
	 * An array containing strings for valid statuses 
	 * Will be initialized using user configuration via config_get()
	 */
    public $statusCode;
    public $codeStatus;
  
	
	/**
	 * Constructor sets up the IXR_Server and db connection
	 */
	public function __construct($callbacks = array())
	{		
		$this->dbObj = new database(DB_TYPE);
		$this->dbObj->db->SetFetchMode(ADODB_FETCH_ASSOC);
		$this->_connectToDB();
		
		$this->tcaseMgr=new testcase($this->dbObj);
	    $this->tprojectMgr=new testproject($this->dbObj);
	    $this->tplanMgr=new testplan($this->dbObj);
	    $this->reqSpecMgr=new requirement_spec_mgr($this->dbObj);
        $this->reqMgr=new requirement_mgr($this->dbObj);
		
		$this->tables = $this->tcaseMgr->getDBTables();
		
		$resultsCfg = config_get('results');
        foreach($resultsCfg['status_label_for_exec_ui'] as $key => $label )
        {
            $this->statusCode[$key]=$resultsCfg['status_code'][$key];  
        }
        
        if( isset($this->statusCode['not_run']) )
        {
            unset($this->statusCode['not_run']);  
        }   
        $this->codeStatus=array_flip($this->statusCode);
    	
        
	    
	    $this->methods = array( 'tl.reportTCResult' => 'this:reportTCResult',
	                            'tl.setTestCaseExecutionResult' => 'this:reportTCResult',
	                            'tl.createBuild' => 'this:createBuild',
	                            'tl.createTestCase' => 'this:createTestCase',
	                            'tl.createTestPlan' => 'this:createTestPlan',
	                            'tl.createTestProject' => 'this:createTestProject',
	                            'tl.createTestSuite' => 'this:createTestSuite',
	                            'tl.uploadExecutionAttachment' => 'this:uploadExecutionAttachment',
	                            'tl.uploadRequirementSpecificationAttachment' => 'this:uploadRequirementSpecificationAttachment',
	                            'tl.uploadRequirementAttachment' => 'this:uploadRequirementAttachment',
	                            'tl.uploadTestProjectAttachment' => 'this:uploadTestProjectAttachment',
	                            'tl.uploadTestSuiteAttachment' => 'this:uploadTestSuiteAttachment',
	                            'tl.uploadTestCaseAttachment' => 'this:uploadTestCaseAttachment',
	                            'tl.uploadAttachment' => 'this:uploadAttachment',
                                'tl.assignRequirements' => 'this:assignRequirements',     
                                'tl.addTestCaseToTestPlan' => 'this:addTestCaseToTestPlan',
	                            'tl.getProjects' => 'this:getProjects',
	                            'tl.getTestProjectByName' => 'this:getTestProjectByName',
	                            'tl.getTestPlanByName' => 'this:getTestPlanByName',
	                            'tl.getProjectTestPlans' => 'this:getProjectTestPlans',
								'tl.getTestPlanPlatforms' => 'this:getTestPlanPlatforms',
	                            'tl.getTotalsForTestPlan' => 'this:getTotalsForTestPlan',
	                            'tl.getBuildsForTestPlan' => 'this:getBuildsForTestPlan',
	                            'tl.getLatestBuildForTestPlan' => 'this:getLatestBuildForTestPlan',	
                                'tl.getLastExecutionResult' => 'this:getLastExecutionResult',
	                            'tl.getTestSuitesForTestPlan' => 'this:getTestSuitesForTestPlan',
	                            'tl.getTestSuitesForTestSuite' => 'this:getTestSuitesForTestSuite',
	                            'tl.getTestCasesForTestSuite'	=> 'this:getTestCasesForTestSuite',
	                            'tl.getTestCasesForTestPlan' => 'this:getTestCasesForTestPlan',
	                            'tl.getTestCaseIDByName' => 'this:getTestCaseIDByName',
                                'tl.getTestCaseCustomFieldDesignValue' => 'this:getTestCaseCustomFieldDesignValue',
                                'tl.getFirstLevelTestSuitesForTestProject' => 'this:getFirstLevelTestSuitesForTestProject',     
                                'tl.getTestCaseAttachments' => 'this:getTestCaseAttachments',
	                            'tl.getTestCase' => 'this:getTestCase',
                                'tl.getFullPath' => 'this:getFullPath',
                                'tl.getTestSuiteByID' => 'this:getTestSuiteByID',
                                'tl.deleteExecution' => 'this:deleteExecution',
                                'tl.doesUserExist' => 'this:doesUserExist',
                                'tl.checkDevKey' => 'this:checkDevKey',
			                    'tl.about' => 'this:about',
			                    'tl.setTestMode' => 'this:setTestMode',
                    			// ping is an alias for sayHello
                    			'tl.ping' => 'this:sayHello', 
                    			'tl.sayHello' => 'this:sayHello',
                    			'tl.repeat' => 'this:repeat'
		                      );				
		
		$this->methods += $callbacks;
		$this->IXR_Server($this->methods);		
	}	
	
	protected function _setArgs($args)
	{
		// TODO: should escape args
		$this->args = $args;
	}
	
	/**
	 * Set the BuildID from one place
	 * 
	 * @param int $buildID
	 * @access protected
	 */
	protected function _setBuildID($buildID)
	{		
		if(GENERAL_ERROR_CODE != $buildID)
		{			
			$this->args[self::$buildIDParamName] = $buildID;			
			return true;
		}
		else
		{
			$this->errors[] = new IXR_Error(INVALID_BUILDID, INVALID_BUILDID_STR);
			return false;
		}	
	}
	
	
	/**
	 * Set test case internal ID
	 * 
	 * @param int $tcaseID
	 * @access protected
	 */
	protected function _setTestCaseID($tcaseID)
	{		
			$this->args[self::$testCaseIDParamName] = $tcaseID;			
	}
	
	/**
	 * Set Build Id to latest build id (if test plan has builds)
	 * 
	 * @return boolean
	 * @access protected
	 */ 
	protected function _setBuildID2Latest()
	{
	    $tplan_id=$this->args[self::$testPlanIDParamName];
        $maxbuildid = $this->tplanMgr->get_max_build_id($tplan_id);
	    $status_ok=($maxbuildid >0);
	    if($status_ok)
	    {
	        $this->_setBuildID($maxbuildid);  
	    } 
	    return $status_ok;
	}	
		
	/**
	 * connect to the db and set up the db object 
	 *
	 * @access protected
	 *
	 * @internal revisions:
	 *  20100731 - asimon - BUGID 3644 (additional fix for BUGID 2607)
	 *  20100711 - franciscom - BUGID 2607 - UTF8 settings for MySQL
	 */		
	protected function _connectToDB()
	{
		if(true == $this->testMode)
		{
		    $this->dbObj->connect(TEST_DSN, TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_NAME);
		}
		else
		{
		    $this->dbObj->connect(DSN, DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}
		// asimon - BUGID 3644 & 2607 - $charSet was undefined here
		$charSet = config_get('charset');
		if((DB_TYPE == 'mysql') && ($charSet == 'UTF-8'))
		{
		    $this->dbObj->exec_query("SET CHARACTER SET utf8");
		    $this->dbObj->exec_query("SET collation_connection = 'utf8_general_ci'");
		}
	}

	/**
	 * authenticates a user based on the devKey provided 
	 * 
	 * This is the only method that should really be used directly to authenticate
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */
    protected function authenticate($messagePrefix='')
    {   
             	
	    // check that the key was given as part of the args
	    if(!$this->_isDevKeyPresent())
	    {
	    	$this->errors[] = new IXR_ERROR(NO_DEV_KEY, $messagePrefix . NO_DEV_KEY_STR);
	    	$this->authenticated = false;
	    	return false;
	    }
	    else
	    {
	    	$this->devKey = $this->args[self::$devKeyParamName];
	    }
	    // make sure the key we have is valid
	    if(!$this->_isDevKeyValid($this->devKey))
	    {
	    	$this->errors[] = new IXR_Error(INVALID_AUTH, $messagePrefix . INVALID_AUTH_STR);
	    	$this->authenticated = false;
	    	return false;			
	    }
	    else
	    {
	    	//Load User
	    	$this->user = tlUser::getByID($this->dbObj,$this->userID);	
	    	$this->authenticated = true;	    	
	    	return true;
	    }				
    }
    
    
	/**
	 * checks if a user has requested right on test project, test plan pair.
	 * 
	 * @param string $roleQuestion  on of the right defined in rights table
	 *
	 * @return boolean
	 * @access protected
	 */
    protected function userHasRight($roleQuestion)
    {
      	$status_ok = true;
      	$tprojectid = $this->args[self::$testProjectIDParamName];
		$tplanid = isset($this->args[self::$testPlanIDParamName]) ? $this->args[self::$testPlanIDParamName] : null;

    	if(!$this->user->hasRight($this->dbObj,$roleQuestion,$tprojectid, $tplanid))
    	{
    		$status_ok = false;
    		$this->errors[] = new IXR_Error(INSUFFICIENT_RIGHTS, INSUFFICIENT_RIGHTS_STR);
    	}
    	return $status_ok;
    }

	/**
	 * Helper method to see if the testcasename provided is valid 
	 * 
	 * This is the only method that should be called directly to check the testcasename
	 * 	
	 * @return boolean
	 * @access protected
	 */        
    protected function checkTestCaseName()
    {
        $status = true;
    	if(!$this->_isTestCaseNamePresent())
    	{
    	  	$this->errors[] = new IXR_Error(NO_TESTCASENAME, NO_TESTCASENAME_STR);
    	  	$status=false;
    	}
    	else
    	{
    	    $testCaseName = $this->args[self::$testCaseNameParamName];
    	    if(!is_string($testCaseName))
    	    {
    	    	$this->errors[] = new IXR_Error(TESTCASENAME_NOT_STRING, TESTCASENAME_NOT_STRING_STR);
    	    	$status=false;
    	    }
    	}
    	return $status;
    }
    
	/**
	 * Helper method to see if the status provided is valid 
	 * 
	 * This is the only method that should be called directly to check the status
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function checkStatus()
    {
		    if( ($status=$this->_isStatusPresent()) )
		    {
		        if( !($status=$this->_isStatusValid($this->args[self::$statusParamName])))
		        {
		        	// BUGID 3455
		        	$msg = sprintf(INVALID_STATUS_STR,$this->args[self::$statusParamName]);
		        	$this->errors[] = new IXR_Error(INVALID_STATUS, $msg);
		        }    	
        	}
        	else
        	{
        	    $this->errors[] = new IXR_Error(NO_STATUS, NO_STATUS_STR);
        	}
        	return $status;
    }       
    
	/**
	 * Helper method to see if the tcid provided is valid 
	 * 
	 * This is the only method that should be called directly to check the tcid
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */    
    protected function checkTestCaseID($messagePrefix='')
    {
        $msg = $messagePrefix;
        $status_ok=$this->_isTestCaseIDPresent();
        if( $status_ok)
        {
            $tcaseid = $this->args[self::$testCaseIDParamName];
            if(!$this->_isTestCaseIDValid($tcaseid))
            {
            	$this->errors[] = new IXR_Error(INVALID_TCASEID, $msg . INVALID_TCASEID_STR);
            	$status_ok=false;
            }
        }    	
        else
        {
        	$this->errors[] = new IXR_Error(NO_TCASEID, $msg . NO_TCASEID_STR);
        }
        return $status_ok;
    }
    
	/**
	 * Helper method to see if the tplanid provided is valid
	 * 
	 * This is the only method that should be called directly to check the tplanid
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */    
    protected function checkTestPlanID($messagePrefix='')
    {
        $status=true;
    	if(!$this->_isTestPlanIDPresent())
    	{
    	    $msg = $messagePrefix . NO_TPLANID_STR;
    		$this->errors[] = new IXR_Error(NO_TPLANID, $msg);
    		$status = false;
    	}
    	else
    	{    		
    		// See if this TPID exists in the db
		    $tplanid = $this->dbObj->prepare_int($this->args[self::$testPlanIDParamName]);
        	$query = "SELECT id FROM {$this->tables['testplans']} WHERE id={$tplanid}";
        	$result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");         	
        	if(null == $result)
        	{
        	      $msg = $messagePrefix . sprintf(INVALID_TPLANID_STR,$tplanid);
        		  $this->errors[] = new IXR_Error(INVALID_TPLANID, $msg);
        		  $status = false;        		
        	}
        	else
        	{
		     	// tplanid exists and its valid
        		// Do we need to try to guess build id ?
				if( $this->checkGuess() && 
					(!$this->_isBuildIDPresent() &&  
				     !$this->_isParamPresent(self::$buildNameParamName,$messagePrefix)))
				{
					$status = $this->_setBuildID2Latest();
				}
				  
        	}    		    		    	
    	}
        return $status;
    } 
    
	/**
	 * Helper method to see if the TestProjectID provided is valid
	 * 
	 * This is the only method that should be called directly to check the TestProjectID
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */    
    protected function checkTestProjectID($messagePrefix='')
    {
    	if(!($status=$this->_isTestProjectIDPresent()))
    	{
    		  $this->errors[] = new IXR_Error(NO_TESTPROJECTID, $messagePrefix . NO_TESTPROJECTID_STR);
    	}
    	else
    	{    		
            // See if this Test Project ID exists in the db
		    $testprojectid = $this->dbObj->prepare_int($this->args[self::$testProjectIDParamName]);
        	$query = "SELECT id FROM {$this->tables['testprojects']} WHERE id={$testprojectid}";
        	$result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");         	
        	if(null == $result)
        	{
        	    $msg = $messagePrefix . sprintf(INVALID_TESTPROJECTID_STR,$testprojectid);
        		$this->errors[] = new IXR_Error(INVALID_TESTPROJECTID, $msg);
        		$status=false;        		
        	}
    	}
    	return $status;
    }  

	/**
	 * Helper method to see if the TestSuiteID provided is valid
	 * 
	 * This is the only method that should be called directly to check the TestSuiteID
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */    
    protected function checkTestSuiteID($messagePrefix='')
    {
    	if(!($status=$this->_isTestSuiteIDPresent()))
    	{
    		$this->errors[] = new IXR_Error(NO_TESTSUITEID, $messagePrefix . NO_TESTSUITEID_STR);
    	}
    	else
    	{    		
            // See if this Test Suite ID exists in the db
            $tsuiteMgr = new testsuite($this->dbObj);
	        $node_info = $tsuiteMgr->get_by_id($this->args[self::$testSuiteIDParamName]);
	        if( !($status=!is_null($node_info)) )
  		    {
  		        $msg=$messagePrefix;
  		        $msg .= sprintf(INVALID_TESTSUITEID_STR, $this->args[self::$testSuiteIDParamName]);
 	            $this->errors[] = new IXR_Error(INVALID_TESTSUITEID, $msg);
        	}
    	}
        return $status;
    }          

	/**
	 * Helper method to see if the guess is set
	 * 
	 * This is the only method that should be called directly to check the guess param
	 * 
	 * Guessing is set to true by default
	 * @return boolean
	 * @access protected
	 */    
    protected function checkGuess()
    {    	
    	// if guess is set return its value otherwise return true to guess by default
    	return($this->_isGuessPresent() ? $this->args[self::$guessParamName] : self::BUILD_GUESS_DEFAULT_MODE);	
    }   	
    
	/**
	 * Helper method to see if the buildID provided is valid for testplan
	 * 
	 * if build id has not been provided on call, we can use build name if has been
	 * provided.
	 *
	 * This is the only method that should be called directly to check the buildID
	 * 	
	 * @return boolean
	 * @access protected
	 *
	 * @internal revision
	 * 20100613 - franciscom - BUGID 2845: buildname option in reportTCResult will never be used
	 */    
    protected function checkBuildID($msg_prefix)
    {
        $tplan_id=$this->args[self::$testPlanIDParamName];
	   	$status=true;
	   	$try_again=false;
      	
      	// First thing is to know is test plan has any build
      	$buildQty = $this->tplanMgr->getNumberOfBuilds($tplan_id);
      	if( $buildQty == 0)
      	{
			$status = false;
			$tplan_info = $this->tplanMgr->get_by_id($tplan_id);
            $msg = $msg_prefix . sprintf(TPLAN_HAS_NO_BUILDS_STR,$tplan_info['name'],$tplan_info['id']);
            $this->errors[] = new IXR_Error(TPLAN_HAS_NO_BUILDS,$msg);
      	} 
	   	
	   	if( $status )
	   	{
	   		if(!$this->_isBuildIDPresent())
	   		{
        	    $try_again=true;
				if($this->_isBuildNamePresent())
				{
       	            $try_again=false;
       	            $bname = trim($this->args[self::$buildNameParamName]);
        	        $buildInfo=$this->tplanMgr->get_build_by_name($tplan_id,$bname); 
	   				
        	        if( is_null($buildInfo) )
        	        {
            			$msg = $msg_prefix . sprintf(BUILDNAME_DOES_NOT_EXIST_STR,$bname);
            			$this->errors[] = new IXR_Error(BUILDNAME_DOES_NOT_EXIST,$msg);
       	            	$status=false;
        	        }
        	        else
        	        {	
        	            $this->args[self::$buildIDParamName]=$buildInfo['id'];
        	        }
				}
			}
	   		
	   		if($try_again)
	   		{
				// this means we aren't supposed to guess the buildid
				if(false == $this->checkGuess())   		
				{
					$this->errors[] = new IXR_Error(BUILDID_NOGUESS, BUILDID_NOGUESS_STR);
					$this->errors[] = new IXR_Error(NO_BUILDID, NO_BUILDID_STR);				
    		    	$status=false;
				}
				else
				{
					$setBuildResult = $this->_setBuildID2Latest();
					if(false == $setBuildResult)
					{
						$this->errors[] = new IXR_Error(NO_BUILD_FOR_TPLANID, NO_BUILD_FOR_TPLANID_STR);
						$status=false;
					}
				}
	   		}
	   		
	   		if( $status)
	   		{
	   		    $buildID = $this->dbObj->prepare_int($this->args[self::$buildIDParamName]);
        	  $buildInfo=$this->tplanMgr->get_build_by_id($tplan_id,$buildID); 
        	  if( is_null($buildInfo) )
        	  {
        	      $tplan_info = $this->tplanMgr->get_by_id($tplan_id);
        	      $msg = sprintf(BAD_BUILD_FOR_TPLAN_STR,$buildID,$tplan_info['name'],$tplan_id);          
				    	  $this->errors[] = new IXR_Error(BAD_BUILD_FOR_TPLAN, $msg);				
				    	  $status=false;
        	  }
        	}
       	} 
		return $status;
    }
     

    /**
	 * Helper method to see if a param is present
	 * 
	 * @param string $pname parameter name 
	 * @param string $messagePrefix used to be prepended to error message
	 * @param boolean $setError default false
	 *                true: add predefined error code to $this->error[]
	 *
	 * @return boolean
	 * @access protected
	 *
	 * 
	 */  	     
	protected function _isParamPresent($pname,$messagePrefix='',$setError=false)
	{
	    $status_ok=(isset($this->args[$pname]) ? true : false);
	    if(!$status_ok && $setError)
	    {
	        $msg = $messagePrefix . sprintf(MISSING_REQUIRED_PARAMETER_STR,$pname);
	        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
        }
        return $status_ok;
	}

    /**
	 * Helper method to see if the status provided is valid 
	 * 	
	 * @return boolean
	 * @access protected
	 */  	     
    protected function _isStatusValid($status)
    {
    	return(in_array($status, $this->statusCode));
    }           

    /**
	 * Helper method to see if a testcasename is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */          
	 protected function _isTestCaseNamePresent()
	 {
		    return (isset($this->args[self::$testCaseNameParamName]) ? true : false);
	 }

    /**
	 * Helper method to see if a testcasename is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */          
	 protected function _isTestCaseExternalIDPresent()
	 {
	      $status=isset($this->args[self::$testCaseExternalIDParamName]) ? true : false;
		    return $status;
	 }


    /**
	 * Helper method to see if a timestamp is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isTimeStampPresent()
    {
    	return (isset($this->args[self::$timeStampParamName]) ? true : false);
    }

    /**
	 * Helper method to see if a buildID is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isBuildIDPresent()
    {
    	return (isset($this->args[self::$buildIDParamName]) ? true : false);
    }
    
	/**
	 * Helper method to see if a buildname is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isBuildNamePresent()
    {                                   
        $status=isset($this->args[self::$buildNameParamName]) ? true : false;
    	return $status;
    }
    
	/**
	 * Helper method to see if build notes are given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isBuildNotePresent()
    {
    	return (isset($this->args[self::$buildNotesParamName]) ? true : false);
    }
    
	/**
	 * Helper method to see if testsuiteid is given as one of the arguments
	 * 	
	 * @return boolean
	 * @access protected
	 */    
	protected function _isTestSuiteIDPresent()
	{
		return (isset($this->args[self::$testSuiteIDParamName]) ? true : false);
	}    
    
    /**
	 * Helper method to see if a note is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isNotePresent()
    {
    	return (isset($this->args[self::$noteParamName]) ? true : false);
    }        
    
    /**
	 * Helper method to see if a tplanid is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isTestPlanIDPresent()
    {    	
    	return (isset($this->args[self::$testPlanIDParamName]) ? true : false);    	
    }

    /**
	 * Helper method to see if a TestProjectID is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isTestProjectIDPresent()
    {    	
    	return (isset($this->args[self::$testProjectIDParamName]) ? true : false);    	
    }        
    
    /**
	 * Helper method to see if automated is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isAutomatedPresent()
    {    	
    	return (isset($this->args[self::$automatedParamName]) ? true : false);    	
    }        
    
    /**
	 * Helper method to see if testMode is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */    
    protected function _isTestModePresent()
    {
    	return (isset($this->args[self::$testModeParamName]) ? true : false);      
    }
    
    /**
	 * Helper method to see if a devKey is given as one of the arguments 
	 * 	 
	 * @return boolean
	 * @access protected
	 */
    protected function _isDevKeyPresent()
    {
    	return (isset($this->args[self::$devKeyParamName]) ? true : false);
    }
    
    /**
	 * Helper method to see if a tcid is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */
    protected function _isTestCaseIDPresent()
    {
		return (isset($this->args[self::$testCaseIDParamName]) ? true : false);
    }  
    
	/**
	 * Helper method to see if the guess param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */
    protected function _isGuessPresent()
    {
		$status=isset($this->args[self::$guessParamName]) ? true : false;
		return $status;
    }
    
    /**
	 * Helper method to see if the testsuitename param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */
    protected function _isTestSuiteNamePresent()
    {
		    return (isset($this->args[self::$testSuiteNameParamName]) ? true : false);
    }    
    
	/**
	 * Helper method to see if the deep param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */
    protected function _isDeepPresent()
    {
		return (isset($this->args[self::$deepParamName]) ? true : false);
    }      
    
	/**
	 * Helper method to see if the status param is given as one of the arguments 
	 * 	
	 * @return boolean
	 * @access protected
	 */
    protected function _isStatusPresent()
    {
		return (isset($this->args[self::$statusParamName]) ? true : false);
    }      
    
	/**
	 * Helper method to see if the tcid provided is valid 
	 * 	
	 * @param struct $tcaseid	 
	 * @param string $messagePrefix used to be prepended to error message
	 * @param boolean $setError default false
	 *                true: add predefined error code to $this->error[]
	 * @return boolean
	 * @access protected
	 */
    protected function _isTestCaseIDValid($tcaseid,$messagePrefix='',$setError=false)
    {
        $status_ok=is_numeric($tcaseid);
    	if($status_ok)
        {
    	    // must be of type 'testcase' and show up in the nodes_hierarchy    	
            $tcaseid = $this->dbObj->prepare_int($tcaseid);
		    $query = " SELECT NH.id AS id " .
		             " FROM {$this->tables['nodes_hierarchy']} NH, " .
		             " {$this->tables['node_types']} NT " .
				     " WHERE NH.id={$tcaseid} AND node_type_id=NT.id " .
				     " AND NT.description='testcase'";
		    $result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");
		    $status_ok = is_null($result) ? false : true; 
        }
        else if($setError)
    	{
            $this->errors[] = new IXR_Error(TCASEID_NOT_INTEGER, 
    		                                $messagePrefix . TCASEID_NOT_INTEGER_STR);
        }
  		return $status_ok;
    }    
    
    /**
	 * Helper method to see if a devKey is valid 
	 * 	
	 * @param string $devKey	 
	 * @return boolean
	 * @access protected
	 */    
    protected function _isDevKeyValid($devKey)
    {    	       	        
        if(null == $devKey || "" == $devKey)
        {
            return false;
        }
        else
        {   
        	$this->userID = null;
        	$this->devKey = $this->dbObj->prepare_string($devKey);
        	$query = "SELECT id FROM {$this->tables['users']} WHERE script_key='{$this->devKey}'";
        	$this->userID = $this->dbObj->fetchFirstRowSingleColumn($query, "id");
        	    	
        	if(null == $this->userID)
        	{
        		return false;        		
        	}
        	else
        	{
        		return true;
        	}
        }                    	
    }    

    /**
	 * Helper method to set the tcVersion
	 * 
	 * 		 
	 * @return boolean
	 * @access protected
	 */        
    protected function _setTCVersion()
    {
		// TODO: Implement
    }
    
    /**
	 * Helper method to See if the tcid and tplanid are valid together 
	 * 
	 * @param map $platformInfo key: platform ID
	 * @param string $messagePrefix used to be prepended to error message
	 * @return boolean
	 * @access protected
	 */            
    protected function _checkTCIDAndTPIDValid($platformInfo=null,$messagePrefix='')
    {  	
    	$tplan_id = $this->args[self::$testPlanIDParamName];
    	$tcase_id = $this->args[self::$testCaseIDParamName];
        $platform_id = !is_null($platformInfo) ? key($platformInfo) : null;
        
        $filters = array('exec_status' => "ALL", 'active_status' => "ALL",
        				 'tplan_id' => $tplan_id, 'platform_id' => $platform_id);
    	$info = $this->tcaseMgr->get_linked_versions($tcase_id,$filters);
        $status_ok = !is_null($info);
		
		        
        if( $status_ok )
        {
            $this->tcVersionID = key($info);
            $dummy = current($info);
        	$plat = is_null($platform_id) ? 0 : $platform_id; 
            $this->versionNumber = $dummy[$tplan_id][$plat]['version'];
            
            // $this->errors[] = $this->tcVersionID;
            // $this->errors[] = $this->versionNumber;
        	// $status_ok = false;    
        }
        else
        {
            $tplan_info = $this->tplanMgr->get_by_id($tplan_id);
            $tcase_info = $this->tcaseMgr->get_by_id($tcase_id);
            
            if( is_null($platform_id) )
            {
            	$msg = sprintf(TCASEID_NOT_IN_TPLANID_STR,$tcase_info[0]['name'],
            	               $this->args[self::$testCaseExternalIDParamName],$tplan_info['name'],$tplan_id);          
            	$this->errors[] = new IXR_Error(TCASEID_NOT_IN_TPLANID, $msg);
            }
            else
            {
            	
            	$msg = sprintf(TCASEID_NOT_IN_TPLANID_FOR_PLATFORM_STR,$tcase_info[0]['name'],
            	               $this->args[self::$testCaseExternalIDParamName],
            	               $tplan_info['name'],$tplan_id,$platformInfo[$platform_id],$platform_id);          
            	$this->errors[] = new IXR_Error(TCASEID_NOT_IN_TPLANID_FOR_PLATFORM, $msg);
            }
        }
        return $status_ok;      
    }

	/**
	 * Run all the necessary checks to see if the createBuild request is valid
	 *  
	 * @param string $messagePrefix used to be prepended to error message
	 * @return boolean
	 * @access protected
	 */
	protected function _checkCreateBuildRequest($messagePrefix='')
	{		
	    
        $checkFunctions = array('authenticate','checkTestPlanID');
        $status_ok=$this->_runChecks($checkFunctions,$messagePrefix);
        if($status_ok)
        {
            $status_ok=$this->_isParamPresent(self::$buildNameParamName,$messagePrefix,self::SET_ERROR);            
        }       
        
	    return $status_ok;
	}	
	
	/**
	 * Run all the necessary checks to see if the createBuild request is valid
	 *  
	 * @return boolean
	 * @access protected
	 */
	protected function _checkGetBuildRequest()
	{		
        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions);       
	    return $status_ok;
	}

	
	/**
	 * Run a set of functions 
	 * @param array $checkFunctions set of function to be runned
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */
	protected function _runChecks($checkFunctions,$messagePrefix='')
	{
      foreach($checkFunctions as $pfn)
      {
          if( !($status_ok = $this->$pfn($messagePrefix)) )
          {
              break; 
          }
      } 
	    return $status_ok;
	}



	/**
	 * Gets the latest build by choosing the maximum build id for a specific test plan 
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["tplanid"]
	 * @return mixed 
	 * 				
	 * @access public
	 */		
	public function getLatestBuildForTestPlan($args)
	{
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    $status_ok=true;
	    $this->_setArgs($args);
        $resultInfo=array();

        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       

        if( $status_ok )
        {
            $testPlanID = $this->args[self::$testPlanIDParamName];
            $build_id = $this->tplanMgr->get_max_build_id($testPlanID);
         
            if( ($status_ok=$build_id > 0) )
            {
                $builds = $this->tplanMgr->get_builds($testPlanID);  
                $build_info = $builds[$build_id];
            }
            else
            {
                $tplan_info=$this->tplanMgr->get_by_id($testPlanID);
                $msg = $msg_prefix . sprintf(TPLAN_HAS_NO_BUILDS_STR,$tplan_info['name'],$tplan_info['id']);
                $this->errors[] = new IXR_Error(TPLAN_HAS_NO_BUILDS,$msg);
            }
        }
        
        return $status_ok ? $build_info : $this->errors;
	}





    /**
     * _getLatestBuildForTestPlan
	 *
	 * @param struct $args
     *
     */
    protected function _getLatestBuildForTestPlan($args)
	{
        $builds = $this->_getBuildsForTestPlan($args);
        $maxid = -1;
		$maxkey = -1;
		foreach ($builds as $key => $build) {
    		if ($build['id'] > $maxid)
    		{
    			$maxkey = $key;
    			$maxid = $build['id'];
    		}
		}
		$maxbuild = array();
		$maxbuild[] = $builds[$maxkey];

		return $maxbuild;
	}
	
	/**
     * Gets the result of LAST EXECUTION for a particular testcase 
     * on a test plan, but WITHOUT checking for a particular build
     *
     * @param struct $args
     * @param string $args["devKey"]
     * @param int $args["tplanid"]
     * @param int $args["testcaseid"]: optional, if does not is present           
     *                                 testcaseexternalid must be present
     *
     * @param int $args["testcaseexternalid"]: optional, if does not is present           
     *                                         testcaseid must be present
     *
     * @return mixed $resultInfo
     *               if execution found, array with these keys:
     *               id (execution id),build_id,tester_id,execution_ts,
     *               status,testplan_id,tcversion_id,tcversion_number,
     *               execution_type,notes.
     *
     *               if test case has not been execute,
     *               array('id' => -1)
     *
     * @access public
     */
    public function getLastExecutionResult($args)
    {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
        
        $this->_setArgs($args);
        $resultInfo = array();
        $status_ok=true;
                
        // Checks are done in order
        $checkFunctions = array('authenticate','checkTestPlanID','checkTestCaseIdentity');

        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && 
                   $this->_checkTCIDAndTPIDValid(null,$msg_prefix) &&
                   $this->userHasRight("mgt_view_tc");       

        if( $status_ok )
        {
            // get all, then return last
            $sql = " SELECT * FROM {$this->tables['executions']} " .
                   " WHERE testplan_id = {$this->args[self::$testPlanIDParamName]} " .
                   " AND tcversion_id IN (" .
                   " SELECT id FROM {$this->tables['nodes_hierarchy']} " .
                   " WHERE parent_id = {$this->args[self::$testCaseIDParamName]})" .
                   " ORDER BY id DESC";
                   
            $result = $this->dbObj->fetchFirstRow($sql);

            if(null == $result)
            {
               // has not been executed
               // execution id = -1 => test case has not been runned.
               $resultInfo[]=array('id' => -1);
            } 
            else
            {
               $resultInfo[]=$result;  
            }
        }
        
        return $status_ok ? $resultInfo : $this->errors;
    }




 	/**
	 * Adds the result to the database 
	 *
	 * @return int
	 * @access protected
	 */			
	protected function _insertResultToDB()
	{
		$build_id = $this->args[self::$buildIDParamName];
		$tester_id =  $this->userID;
		$status = $this->args[self::$statusParamName];
		$testplan_id =	$this->args[self::$testPlanIDParamName];
		$tcversion_id =	$this->tcVersionID;
		$version_number =	$this->versionNumber;

		$db_now=$this->dbObj->db_now();
		$platform_id = 0;
		
		if( isset($this->args[self::$platformIDParamName]) )
		{
			$platform_id = $this->args[self::$platformIDParamName]; 	
	    }
		
		$notes='';
        $notes_field="";
        $notes_value="";  

		if($this->_isNotePresent())
		{
			$notes = $this->dbObj->prepare_string($this->args[self::$noteParamName]);
		}
		
		if(trim($notes) != "")
		{
		    $notes_field = ",notes";
		    $notes_value = ", '{$notes}'";  
		}
		
		$execution_type = constant("TESTCASE_EXECUTION_TYPE_AUTO");

		$query = "INSERT INTO {$this->tables['executions']} " .
		         " (build_id, tester_id, execution_ts, status, testplan_id, tcversion_id, " .
		         " platform_id, tcversion_number," .
		         " execution_type {$notes_field} ) " .
				 " VALUES({$build_id},{$tester_id},{$db_now},'{$status}',{$testplan_id}," .
				 " {$tcversion_id},{$platform_id}, {$version_number},{$execution_type} {$notes_value})";

		$this->dbObj->exec_query($query);
		return $this->dbObj->insert_id($this->tables['executions']);		
	}
	
	
	/**
	 * Lets you see if the server is up and running
	 *  
	 * @param struct not used	
	 * @return string "Hello!"
	 * @access public
	 */
	public function sayHello($args)
	{
		return 'Hello!';
	}

	/**
	 * Repeats a message back 
	 *
	 * @param struct $args should contain $args['str'] parameter
	 * @return string
	 * @access public
	 */	
	public function repeat($args)
	{
		$this->_setArgs($args);
		$str = "You said: " . $this->args['str'];
		return $str;
	}

	/**
	 * Gives basic information about the API
	 *
	 * @param struct not used
	 * @return string
	 * @access public
	 */	
	public function about($args)
	{
		$this->_setArgs($args);
		$str = " Testlink API Version: " . self::$version . " initially written by Asiel Brumfield\n" .
		       " with contributions by TestLink development Team";
		return $str;				
	}
	
	/**
	 * Creates a new build for a specific test plan
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @param string $args["buildname"];
	 * @param string $args["buildnotes"];
	 * @return mixed $resultInfo
	 * 				
	 * @access public
	 */		
	public function createBuild($args)
	{
	    $operation = __FUNCTION__;
        $messagePrefix="({$operation}) - ";
		$resultInfo = array();
		$resultInfo[0]["status"] = true;
	    $resultInfo[0]["operation"] = $operation;
		$insertID = '';
		$returnMessage = GENERAL_SUCCESS_STR;

		$this->_setArgs($args);

		// check the tpid
		if($this->_checkCreateBuildRequest($messagePrefix) && 
		   $this->userHasRight("testplan_create_build"))
		{
			$testPlanID = $this->args[self::$testPlanIDParamName];
			$buildName = $this->args[self::$buildNameParamName];					
			$buildNotes = "";
			if($this->_isBuildNotePresent())
			{			
				$buildNotes = $this->dbObj->prepare_string($this->args[self::$buildNotesParamName]);
			}
			
			
			if ($this->tplanMgr->check_build_name_existence($testPlanID,$buildName))
			{
				//Build exists so just get the id of the existing build
				$insertID = $this->tplanMgr->get_build_id_by_name($testPlanID,$buildName);
				$returnMessage = sprintf(BUILDNAME_ALREADY_EXISTS_STR,$buildName,$insertID);
		        $resultInfo[0]["status"] = false;
			
			} else {
				//Build doesn't exist so create one
				// ,$active=1,$open=1);
				$insertID = $this->tplanMgr->create_build($testPlanID,$buildName,$buildNotes);
			}
			
			$resultInfo[0]["id"] = $insertID;	
			$resultInfo[0]["message"] = $returnMessage;
			return $resultInfo;			 	
		}
		else
		{
			return $this->errors;
		}	
	}
	
	/**
	 * Gets a list of all projects
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @return mixed $resultInfo			
	 * @access public
	 */		
	public function getProjects($args)
	{
		$this->_setArgs($args);		
		//TODO: NEED associated RIGHT
		if($this->authenticate())
		{
			return $this->tprojectMgr->get_all();	
		}
		else
		{
			return $this->errors;
		}
    }
	
	/**
	 * Gets a list of test plans within a project
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testprojectid"]
	 * @return mixed $resultInfo
	 * 				
	 * @access public
	 */		
	public function getProjectTestPlans($args)
	{
        $messagePrefix="(" .__FUNCTION__ . ") - ";
        
		$this->_setArgs($args);
		// check the tplanid
		//TODO: NEED associated RIGHT
        $checkFunctions = array('authenticate','checkTestProjectID');       
        $status_ok=$this->_runChecks($checkFunctions,$messagePrefix);       
	
		if($status_ok)
		{
			$testProjectID = $this->args[self::$testProjectIDParamName];
			$info=$this->tprojectMgr->get_all_testplans($testProjectID);
			if( !is_null($info) && count($info) > 0 )
			{
			    $info = array_values($info);
			}
			return $info;	
		}
		else
		{
			return $this->errors;
		} 
	}
	
	/**
	 * Gets a list of builds within a test plan
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @return 
	 *         if no errors
	 *            no build present => null
	 *            array of builds
	 *         
	 * 				
	 * @access public
	 */		
	public function getBuildsForTestPlan($args)
	{
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
        $this->_setArgs($args);

        $builds=null;
        $status_ok=true;
        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       
      
        if( $status_ok )
        {
            $testPlanID = $this->args[self::$testPlanIDParamName];
            $dummy = $this->tplanMgr->get_builds($testPlanID);
		  	    
		  	if( !is_null($dummy) )
		  	{
		  	   $builds=array_values($dummy);
		  	}
        }
        return $status_ok ? $builds : $this->errors;
	}


	/**
	 * List test suites within a test plan alphabetically
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @return mixed $resultInfo
	 */
	 public function getTestSuitesForTestPlan($args)
	 {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	 	$this->_setArgs($args);

        $checkFunctions = array('authenticate','checkTestPlanID');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       
		if($status_ok)
		{
			$testPlanID = $this->args[self::$testPlanIDParamName];			
			$result = $this->tplanMgr->get_testsuites($testPlanID);
			return 	$result;
		}
		else
		{
			return $this->errors;
		} 
	 }
	
	/**
	 * create a test project
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param string $args["testprojectname"]
	 * @param string $args["testcaseprefix"]
	 * @param string $args["notes"] OPTIONAL
	 * @param map $args["options"] OPTIONAL ALL int treated as boolean
	 *				keys  requirementsEnabled,testPriorityEnabled,automationEnabled,inventoryEnabled
	 *
	 * @param int $args["active"]  OPTIONAL
     * @param int $args["public"]  OPTIONAL
     *	 
	 * @return mixed $resultInfo
	 */
	public function createTestProject($args)
	{
	    $this->_setArgs($args);
        $msg_prefix="(" . __FUNCTION__ . ") - ";
	    $checkRequestMethod='_check' . ucfirst(__FUNCTION__) . 'Request';
	
	    if( $this->$checkRequestMethod($msg_prefix) && $this->userHasRight("mgt_modify_product"))
	    {
	        // function create($name,$color,$options,$notes,$active=1,$tcasePrefix='')
	        
	        // Now go for options (is any)
			// all enabled by DEFAULT
	        $options = new stdClass();
			$options->requirementsEnabled = 1;
			$options->testPriorityEnabled = 1;
			$options->automationEnabled = 1;
			$options->inventoryEnabled = 1;

			if( $this->_isParamPresent(self::$optionsParamName,$messagePrefix) )
			{
				// has to be an array ?
				$dummy = $this->args[self::$optionsParamName];
				if( is_array($dummy) )
				{
					foreach($dummy as $key => $value)
					{
						$options->$key = $value > 0 ? 1 : 0;
					}
				}
			}

			// other optional parameters (not of complex type)
            // key 2 check with default value is parameter is missing
            $keys2check = array(self::$activeParamName => 1,self::$publicParamName => 1,
                                self::$noteParamName => '');
  		    foreach($keys2check as $key => $value)
  		    {
  		        $optional[$key]=$this->_isParamPresent($key) ? trim($this->args[$key]) : $value;
  		    }

	        $name = htmlspecialchars($this->args[self::$testProjectNameParamName]);
            $prefix = htmlspecialchars($this->args[self::$testCasePrefixParamName]);

            $notes = htmlspecialchars($optional[self::$noteParamName]);
            $active = $optional[self::$activeParamName];
            $public = $optional[self::$publicParamName];
      
            $active = $active > 0 ? 1 : 0;
      		$public = $public > 0 ? 1 : 0;
      
	        $info=$this->tprojectMgr->create($name,'',$options,$notes,$active,$prefix,$public);
		    $resultInfo = array();
		    $resultInfo[]= array("operation" => __FUNCTION__,
			                    "additionalInfo" => null,
			                    "status" => true, "id" => $info, "message" => GENERAL_SUCCESS_STR);
	        return $resultInfo;
	    }
	    else
	    {
	        return $this->errors;
	    }    
      
	}
	
  /**
   * _checkCreateTestProjectRequest
   *
   */
  protected function _checkCreateTestProjectRequest($msg_prefix)
	{
      $status_ok=$this->authenticate();
      $name=$this->args[self::$testProjectNameParamName];
      $prefix=$this->args[self::$testCasePrefixParamName];
      
      if( $status_ok )
      {
          $check_op=$this->tprojectMgr->checkNameSintax($name);
          $status_ok=$check_op['status_ok'];     
          if(!$status_ok)
          {     
	           $this->errors[] = new IXR_Error(TESTPROJECTNAME_SINTAX_ERROR, 
	                                           $msg_prefix . $check_op['msg']);
          }
      }
      
      if( $status_ok ) 
      {
          $check_op=$this->tprojectMgr->checkNameExistence($name);
          $status_ok=$check_op['status_ok'];     
          if(!$status_ok)
          {     
	           $this->errors[] = new IXR_Error(TESTPROJECTNAME_EXISTS, 
	                                           $msg_prefix . $check_op['msg']);
          }
      }

      if( $status_ok ) 
      {
          $status_ok=!empty($prefix);
          if(!$status_ok)
          {     
	           $this->errors[] = new IXR_Error(TESTPROJECT_TESTCASEPREFIX_IS_EMPTY, 
	                                           $msg_prefix . $check_op['msg']);
          }
      }

      if( $status_ok ) 
      {
           $info=$this->tprojectMgr->get_by_prefix($prefix);
           if( !($status_ok = is_null($info)) )
           {
              $msg = $msg_prefix . sprintf(TPROJECT_PREFIX_ALREADY_EXISTS_STR,$prefix,$info['name']);
              $this->errors[] = new IXR_Error(TPROJECT_PREFIX_ALREADY_EXISTS,$msg);
           }
      }

  	  return $status_ok;
	}

	
	
	/**
	 * List test cases within a test suite
	 * 
	 * By default test cases that are contained within child suites 
	 * will be returned. 
	 * Set the deep flag to false if you only want test cases in the test suite provided 
	 * and no child test cases.
	 *  
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testsuiteid"]
	 * @param boolean $args["deep"] - optional (default is true)
	 * @param boolean $args["details"] - optional (default is simple)
	 *                                use full if you want to get 
	 *                                summary,steps & expected_results
	 *
	 * @return mixed $resultInfo
	 *
	 *
	 */
	 public function getTestCasesForTestSuite($args)
	 {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    
		$this->_setArgs($args);
		$status_ok=$this->_runChecks(array('authenticate','checkTestSuiteID'),$msg_prefix);       
		
		$details='simple';
		$key2search=self::$detailsParamName;
		if( $this->_isParamPresent($key2search) )
		{
		    $details=$this->args[$key2search];  
		}
			
		if($status_ok && $this->userHasRight("mgt_view_tc"))
		{		
			$testSuiteID = $this->args[self::$testSuiteIDParamName];
            $tsuiteMgr = new testsuite($this->dbObj);

            // BUGID 2179
			if(!$this->_isDeepPresent() || $this->args[self::$deepParamName] )
			{
			    $pfn = 'get_testcases_deep';
			}	
			else
			{
			    $pfn = 'get_children_testcases';
			}
			return $tsuiteMgr->$pfn($testSuiteID,$details);
			
			
		}
		else
		{
			return $this->errors;
		}
	 }

  /**
  * Find a test case by its name
  * 
  * <b>Searching is case sensitive.</b> The test case will only be returned if there is a definite match.
  * If possible also pass the string for the test suite name. 
  *
  * No results will be returned if there are test cases with the same name that match the criteria provided.  
  * 
  * @param struct $args
  * @param string $args["devKey"]
  * @param string $args["testcasename"]
  * @param string $args["testsuitename"] - optional
  * @param string $args["testprojectname"] - optional
  * @param string $args["testcasepathname"] - optional
  *               Full test case path name, starts with test project name
  *               pieces separator -> :: -> default value of getByPathName()
  * @return mixed $resultInfo
  */
  public function getTestCaseIDByName($args)
  {
		$msg_prefix="(" .__FUNCTION__ . ") - ";
		$status_ok=true;
      	$this->_setArgs($args);
        $result = null;
      
      	$checkFunctions = array('authenticate','checkTestCaseName');       
      	$status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_view_tc");       
      
      	if( $status_ok )
      	{			
          	$testCaseName = $this->args[self::$testCaseNameParamName];
          	$testCaseMgr = new testcase($this->dbObj);
 
          	$keys2check = array(self::$testSuiteNameParamName,self::$testCasePathNameParamName,
          	                    self::$testProjectNameParamName);
			foreach($keys2check as $key)
  		    {
  		        $optional[$key]=$this->_isParamPresent($key) ? trim($this->args[$key]) : '';
  		    }
  
            // 20091128 - franciscom
            if( $optional[self::$testCasePathNameParamName] != '' )
            {
          		$dummy = $testCaseMgr->getByPathName($optional[self::$testCasePathNameParamName]);
          		if( !is_null($dummy) )
          		{
          			$result[0] = $dummy;
          		}
            }
            else
            {
          		$result = $testCaseMgr->get_by_name($testCaseName,$optional[self::$testSuiteNameParamName],
            	                                    $optional[self::$testProjectNameParamName]);
          	}
          	if(0 == sizeof($result))
          	{
          	    $status_ok=false;
          	    $this->errors[] = new IXR_ERROR(NO_TESTCASE_BY_THIS_NAME, 
          	                                    $msg_prefix . NO_TESTCASE_BY_THIS_NAME_STR);
          	    return $this->errors;
          	}
      }
      return $status_ok ? $result : $this->errors; 
  }
	 
	 /**
      * createTestCase
  	  * @param struct $args
  	  * @param string $args["devKey"]
  	  * @param string $args["testcasename"]
  	  * @param int    $args["testsuiteid"]: test case parent test suite id
  	  * @param int    $args["testprojectid"]: test case parent test suite id
  	  *
  	  * @param string $args["authorlogin"]: to set test case author
  	  * @param string $args["summary"]
  	  * @param string $args["steps"]
  	  *
  	  * @param string $args["preconditions"] - optional
      * @param string $args["importance"] - optional - see const.inc.php for domain
      * @param string $args["execution"] - optional - see ... for domain
      * @param string $args["order'] - optional
      * @param string $args["internalid"] - optional - do not use
      * @param string $args["checkduplicatedname"] - optional
      * @param string $args["actiononduplicatedname"] - optional
      *
  	  * @return mixed $resultInfo
      * @return string $resultInfo['operation'] - verbose operation
      * @return boolean $resultInfo['status'] - verbose operation
      * @return int $resultInfo['id'] - test case internal ID (Database ID)
      * @return mixed $resultInfo['additionalInfo'] 
      * @return int $resultInfo['additionalInfo']['id'] same as $resultInfo['id']
      * @return int $resultInfo['additionalInfo']['external_id'] without prefix
      * @return int $resultInfo['additionalInfo']['status_ok'] 1/0
      * @return string $resultInfo['additionalInfo']['msg'] - for debug 
      * @return string $resultInfo['additionalInfo']['new_name'] only present if new name generation was needed
      * @return int $resultInfo['additionalInfo']['version_number']
      * @return boolean $resultInfo['additionalInfo']['has_duplicate'] - for debug 
      * @return string $resultInfo['message'] operation message
      */
	 public function createTestCase($args)
	 {
	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    
	    $keywordSet='';
	    $this->_setArgs($args);
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestSuiteID','checkTestCaseName');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_modify_tc");

        if( $status_ok )
        {
      		$keys2check = array(self::$authorLoginParamName,self::$summaryParamName, self::$stepsParamName);
      		foreach($keys2check as $key)
      		{
      		    if(!$this->_isParamPresent($key))
      		    {
      				$status_ok = false;
      		        $msg = $msg_prefix . sprintf(MISSING_REQUIRED_PARAMETER_STR,$key);
      		        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
      		    }   
      		}
        }                        

        if( $status_ok )
        {
            $author_id = tlUser::doesUserExist($this->dbObj,$this->args[self::$authorLoginParamName]);		    	
            if( !($status_ok = !is_null($author_id)) )
            {
            	$msg = $msg_prefix . sprintf(NO_USER_BY_THIS_LOGIN_STR,$this->args[self::$authorLoginParamName]);
     	    	$this->errors[] = new IXR_Error(NO_USER_BY_THIS_LOGIN, $msg);				
     	    }
        }

        if( $status_ok )
        {
        	$keywordSet=$this->getKeywordSet($this->args[self::$testProjectIDParamName]);
        }

        if( $status_ok )
        {
            // Optional parameters
            $opt=array(self::$importanceParamName => 2,
                       self::$executionTypeParamName => TESTCASE_EXECUTION_TYPE_MANUAL,
                       self::$orderParamName => testcase::DEFAULT_ORDER,
                       self::$internalIDParamName => testcase::AUTOMATIC_ID,
                       self::$checkDuplicatedNameParamName => testcase::DONT_CHECK_DUPLICATE_NAME,
                       self::$actionOnDuplicatedNameParamName => 'generate_new',
                       self::$preconditionsParamName => '');
        
		        foreach($opt as $key => $value)
		        {
		            if($this->_isParamPresent($key))
		            {
		                $opt[$key]=$this->args[$key];      
		            }   
		        }
        }
        
             
        if( $status_ok )
        {
            $options = array( 'check_duplicate_name' => $opt[self::$checkDuplicatedNameParamName],
	                          'action_on_duplicate_name' => $opt[self::$actionOnDuplicatedNameParamName]);
   
            $op_result=$this->tcaseMgr->create($this->args[self::$testSuiteIDParamName],
                                               $this->args[self::$testCaseNameParamName],
                                               $this->args[self::$summaryParamName],
                                               $opt[self::$preconditionsParamName],
                                               $this->args[self::$stepsParamName],
                                               $author_id,$keywordSet,
                                               $opt[self::$orderParamName],
                                               $opt[self::$internalIDParamName],
                                               $opt[self::$executionTypeParamName],
                                               $opt[self::$importanceParamName],
                                               $options);
            
            $resultInfo=array();
   		    $resultInfo[] = array("operation" => $operation, "status" => true, 
		                          "id" => $op_result['id'], 
		                          "additionalInfo" => $op_result,
		                          "message" => GENERAL_SUCCESS_STR);
        } 
        return ($status_ok ? $resultInfo : $this->errors);
	 }	
	 
	 /**
	  * Update an existing test case
	  */
	 public function updateTestCase($args)
	 {
	 	// TODO: Implement
	 } 	 	



	 /**
	 * Reports a result for a single test case
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testcaseid"]: optional, if not present           
     *                                 testcaseexternalid must be present
     *
     * @param int $args["testcaseexternalid"]: optional, if does not is present           
     *                                         testcaseid must be present
     *
	 *
	 *
	 * @param int $args["testplanid"] 
     * @param string $args["status"] - status is {@link $validStatusList}
	 * @param int $args["buildid"] - optional.
	 *                               if not present and $args["buildname"] exists
	 *	                             then 
	 *                                    $args["buildname"] will be checked and used if valid
	 *                               else 
	 *                                    build with HIGHEST ID will be used
	 *
	 * @param int $args["buildname"] - optional.
	 *                               if not present Build with higher internal ID will be used
	 *
     *
	 * @param string $args["notes"] - optional
	 * @param bool $args["guess"] - optional defining whether to guess optinal params or require them 
	 * 								              explicitly default is true (guess by default)
	 *
	 * @param string $args["bugid"] - optional
     *
     * @param string $args["platformid"] - optional, if not present platformname must be present
	 * @param string $args["platformname"] - optional, if not present platformid must be present
     *    
     *
     * @param string $args["customfields"] - optional
     *               contains an map with key:Custom Field Name, value: value for CF.
     *               VERY IMPORTANT: value must be formatted in the way it's written to db,
     *               this is important for types like:
     *
     *               DATE: strtotime()
     *               DATETIME: mktime()
     *               MULTISELECTION LIST / CHECKBOX / RADIO: se multipli selezione ! come separatore
     *
     *
     *               these custom fields must be configured to be writte during execution.
     *               If custom field do not meet condition value will not be written
     *
     * @param boolean $args["overwrite"] - optional, if present and true, then last execution
     *                for (testcase,testplan,build,platform) will be overwritten.            
     *
	 * @return mixed $resultInfo 
	 * 				[status]	=> true/false of success
	 * 				[id]		  => result id or error code
	 * 				[message]	=> optional message for error message string
	 * @access public
	 *
	 * @internal revisions
	 * 20101208 - franciscom - BUGID 4082 - no check on overwrite value
	 *
	 */
	public function reportTCResult($args)
	{		
		$resultInfo = array();
        $operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";

		$this->_setArgs($args);              
		$resultInfo[0]["status"] = true;
		
        $checkFunctions = array('authenticate','checkTestCaseIdentity','checkTestPlanID',
                                'checkBuildID','checkStatus');
                                
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       

   	    if($status_ok)
		{			
			// This check is needed only if test plan has platforms
        	$platformSet = $this->tplanMgr->getPlatforms($this->args[self::$testPlanIDParamName],
        	                                              array('outputFormat' => 'map'));  
			$targetPlatform = null;
			if( !is_null($platformSet) )
            {       
	    		$status_ok = $this->checkPlatformIdentity($this->args[self::$testPlanIDParamName],$platformSet,$msg_prefix);
				if($status_ok)
				{
					$targetPlatform[$this->args[self::$platformIDParamName]] = $platformSet[$this->args[self::$platformIDParamName]];
				}
	    	}
	    	
			$status_ok = $status_ok && $this->_checkTCIDAndTPIDValid($targetPlatform,$msg_prefix);
	    }
	
		if($status_ok && $this->userHasRight("testplan_execute"))
		{		
			$executionID = 0;	
			$resultInfo[0]["operation"] = $operation;
    	    $resultInfo[0]["overwrite"] = false;
		    $resultInfo[0]["status"] = true;
			$resultInfo[0]["message"] = GENERAL_SUCCESS_STR;

			// BUGID 4082 - no check on overwrite value
    	    if($this->_isParamPresent(self::$overwriteParamName) && $this->args[self::$overwriteParamName])
    	    {
    	    		$executionID = $this->_updateResult();
    	    		$resultInfo[0]["overwrite"] = true;			
    	    }
    	    if($executionID == 0)
            {
            	$executionID = $this->_insertResultToDB();			
            } 

			$resultInfo[0]["id"] = $executionID;	
			
			// Do we need to insert a bug ?
    	    if($this->_isParamPresent(self::$bugIDParamName))
    	    {
    	    	$bugID = $this->args[self::$bugIDParamName];
		    	$resultInfo[0]["bugidstatus"] = $this->_insertExecutionBug($executionID, $bugID);
    	    }
    	    
    	    
    	    if($this->_isParamPresent(self::$customFieldsParamName))
    	    {
    	    	$resultInfo[0]["customfieldstatus"] =  
    	    		$this->_insertCustomFieldExecValues($executionID);   
    	    }
			return $resultInfo;
		}
		else
		{
			return $this->errors;			
		}

	}
	
	
	/**
	 * turn on/off testMode
	 *
	 * This method is meant primarily for testing and debugging during development
	 * @param struct $args
	 * @return boolean
	 * @access protected
	 */	
	public function setTestMode($args)
	{
		$this->_setArgs($args);
		
		if(!$this->_isTestModePresent())
		{
			$this->errors[] = new IXR_ERROR(NO_TEST_MODE, NO_TEST_MODE_STR);
			return false;
		}
		else
		{
			// TODO: should probably validate that this is a bool or t/f string
			$this->testMode = $this->args[self::$testModeParamName];
			return true;			
		}
	}	
	
	
	/**
	 * Helper method to see if the testcase identity provided is valid 
	 * Identity can be specified in one of these modes:
	 *
	 * test case internal id
	 * test case external id  (PREFIX-NNNN) 
	 * 
	 * This is the only method that should be called directly to check test case identoty
	 * 	
	 * If everything OK, test case internal ID is setted.
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */    
    protected function checkTestCaseIdentity($messagePrefix='')
    {
        // Three Cases - Internal ID, External ID, No Id        
        $status=true;
        $tcaseID=0;
        $my_errors=array();
		$fromExternal=false;
		$fromInternal=false;

	    if($this->_isTestCaseIDPresent())
	    {
		      $fromInternal=true;
		      $tcaseID = $this->args[self::$testCaseIDParamName];
		      $status = true;
	    }
		elseif ($this->_isTestCaseExternalIDPresent())
		{
            $fromExternal = true;
			$tcaseExternalID = $this->args[self::$testCaseExternalIDParamName]; 
		    $tcaseCfg=config_get('testcase_cfg');
		    $glueCharacter=$tcaseCfg->glue_character;
		    $tcaseID=$this->tcaseMgr->getInternalID($tcaseExternalID,$glueCharacter);
            $status = $tcaseID > 0 ? true : false;
            
            //Invalid TestCase ID
            if( !$status )
            {
              	$my_errors[] = new IXR_Error(INVALID_TESTCASE_EXTERNAL_ID, 
                                             sprintf($messagePrefix . INVALID_TESTCASE_EXTERNAL_ID_STR,$tcaseExternalID));                  
            }
		}
	    if( $status )
	    {
	        $my_errors=null;
	        if($this->_isTestCaseIDValid($tcaseID,$messagePrefix))
	        {
	            $this->_setTestCaseID($tcaseID);  
	        }  
	        else
	        {  
	        	  if ($fromInternal)
	        	  {
	        	  	$my_errors[] = new IXR_Error(INVALID_TCASEID, $messagePrefix . INVALID_TCASEID_STR);
	        	  } 
	        	  elseif ($fromExternal)
	        	  {
	        	  	$my_errors[] = new IXR_Error(INVALID_TESTCASE_EXTERNAL_ID, 
                                                 sprintf($messagePrefix . INVALID_TESTCASE_EXTERNAL_ID_STR,$tcaseExternalID));
	        	  }
	        	  $status=false;
	        }    	
	    }
	    
	    
	    if (!$status)
	    {
            foreach($my_errors as $error_msg)
		    {
		          $this->errors[] = $error_msg; 
		    } 
	    }
	    return $status;
    }   

	 /**
	 * getTestCasesForTestPlan
	 * List test cases linked to a test plan
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanid"]
	 * @param int $args["testcaseid"] - optional
	 * @param int $args["buildid"] - optional
	 * @param int $args["keywordid"] - optional mutual exclusive with $args["keywords"]
	 * @param int $args["keywords"] - optional  mutual exclusive with $args["keywordid"]
	 *
	 * @param boolean $args["executed"] - optional
	 * @param int $args["$assignedto"] - optional
	 * @param string $args["executestatus"] - optional
	 * @param array $args["executiontype"] - optional
	 * @param array $args["getstepinfo"] - optional - default false
	 *
	 * @return mixed $resultInfo
	 */
	 public function getTestCasesForTestPlan($args)
	 {

	    $operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
         
        // Optional parameters that are not mutual exclusive
        $opt=array(self::$testCaseIDParamName => null,
                   self::$buildIDParamName => null,
                   self::$keywordIDParamName => null,
                   self::$executedParamName => null,
                   self::$assignedToParamName => null,
                   self::$executeStatusParamName => null,
                   self::$executionTypeParamName => null,
                   self::$getStepsInfoParamName => false);
         	
        $optMutualExclusive=array(self::$keywordIDParamName => null,
                                  self::$keywordNameParamName => null); 	
        $this->_setArgs($args);
		
		// Test Case ID, Build ID are checked if present
		if(!$this->_checkGetTestCasesForTestPlanRequest($msg_prefix) && $this->userHasRight("mgt_view_tc"))
		{
			return $this->errors;
		}
		
		$tplanid = $this->args[self::$testPlanIDParamName];
		$tplanInfo = $this->tplanMgr->tree_manager->get_node_hierarchy_info($tplanid);
		
		foreach($opt as $key => $value)
		{
		    if($this->_isParamPresent($key))
		    {
		        $opt[$key]=$this->args[$key];      
		    }   
		}
		
		// 20101110 - franciscom
		// honors what has been written in documentation
		$keywordSet = $opt[self::$keywordIDParamName];
		if( is_null($keywordSet) )
		{
			$keywordSet = null;
        	$keywordList = $this->getKeywordSet($tplanInfo['parent_id']);
        	if( !is_null($keywordList) )
        	{
        		$keywordSet = explode(",",$keywordList);
        	}
		}
		// BUGID 4041
		// BUGID 3604
        $options = array('executed_only' => $opt[self::$executedParamName], 
        				 'steps_info' => $opt[self::$getStepsInfoParamName],
        				 'details' => 'full','output' => 'mapOfMap' );
        	
        // BUGID 3992				 
		$filters = array('tcase_id' => $opt[self::$testCaseIDParamName],
			             'keyword_id' => $keywordSet,
			             'assigned_to' => $opt[self::$assignedToParamName],
			             'exec_status' => $opt[self::$executeStatusParamName],
			             'build_id' => $opt[self::$buildIDParamName],
			             'exec_type' => $opt[self::$executionTypeParamName]);
		
		
		$recordset=$this->tplanMgr->get_linked_tcversions($tplanid,$filters,$options);
		return $recordset;
	 }


	/**
	 * Run all the necessary checks to see if a GetTestCasesForTestPlanRequest()
	 * can be accepted.
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */
	protected function _checkGetTestCasesForTestPlanRequest($messagePrefix='')
	{
		$status=$this->authenticate();
		if($status)
		{
	        $status &=$this->checkTestPlanID($messagePrefix);
	        
	        if($status && $this->_isTestCaseIDPresent($messagePrefix))
	        {
	            $status &=$this->_checkTCIDAndTPIDValid(null,$messagePrefix);
	        }
	        if($status && $this->_isBuildIDPresent($messagePrefix))  
	        {
	            $status &=$this->checkBuildID($messagePrefix);
	        }
		}
		return $status;
	}
	
  /**
	 * Gets value of a Custom Field with scope='design' for a given Test case
	 *
	 * @param struct $args
	 * @param string $args["devKey"]: used to check if operation can be done.
	 *                                if devKey is not valid => abort.
	 *
	 * @param string $args["testcaseexternalid"]:  
	 * @param string $args["version"]: version number  
	 * @param string $args["testprojectid"]: 
	 * @param string $args["customfieldname"]: custom field name
	 * @param string $args["details"] optional, changes output information
	 *                                null or 'value' => just value
	 *                                'full' => a map with all custom field definition
	 *                                             plus value and internal test case id
	 *                                'simple' => value plus custom field name, label, and type (as code).
     *
     * @return mixed $resultInfo
	 * 				
	 * @access public
	 */		
    public function getTestCaseCustomFieldDesignValue($args)
	{
        $msg_prefix="(" .__FUNCTION__ . ") - ";
		$this->_setArgs($args);	
		
		// 20101020 - franciscom - added checkTestCaseVersionNumber	
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestCaseIdentity',
        						'checkTestCaseVersionNumber');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);       

        if( $status_ok )
        {
		    $status_ok=$this->_isParamPresent(self::$customFieldNameParamName,$msg_prefix,self::SET_ERROR);
        }
        
        
        if($status_ok)
		{
            $ret = $this->checkTestCaseAncestry();
            $status_ok = $ret['status_ok'];
            if( $status_ok )
            {
            	// Check if version number exists for Test Case
            	$ret = $this->checkTestCaseVersionNumberAncestry();
            	$status_ok = $ret['status_ok'];
            }
            
            if($status_ok )
            {
                $status_ok=$this->_checkGetTestCaseCustomFieldDesignValueRequest($msg_prefix);
            }
            else 
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
		}
        
		if($status_ok && $this->userHasRight("mgt_view_tc"))
		{
		    $details='value';
		    if( $this->_isParamPresent(self::$detailsParamName) )
		    {
		        $details=$this->args[self::$detailsParamName];  
		    }
	    
		    
            $cf_name=$this->args[self::$customFieldNameParamName];
            $tproject_id=$this->args[self::$testProjectIDParamName];
            $tcase_id=$this->args[self::$testCaseIDParamName];
            
		    $cfield_mgr = $this->tprojectMgr->cfield_mgr;
            $cfinfo = $cfield_mgr->get_by_name($cf_name);
            $cfield = current($cfinfo);
            $filters = array('cfield_id' => $cfield['id']);
            $cfieldSpec = $this->tcaseMgr->get_linked_cfields_at_design($tcase_id,$this->tcVersionID,null,$filters,$tproject_id);
            
            switch($details)
            {
                case 'full':
                    $retval = $cfieldSpec[$cfield['id']]; 
                break;
                
                case 'simple':
                    $retval = array('name' => $cf_name, 'label' => $cfieldSpec[$cfield['id']]['label'], 
                                    'type' => $cfieldSpec[$cfield['id']]['type'], 
                                    'value' => $cfieldSpec[$cfield['id']]['value']);
                break;
                
                case 'value':
                default:
                    $retval=$cfieldSpec[$cfield['id']]['value'];
                break;
                
            }
            return $retval;
		}
		else
		{
			return $this->errors;
		} 
  }
  
  	/**
	 * Run all the necessary checks to see if GetTestCaseCustomFieldDesignValueRequest()
	 * can be accepted.
	 *  
     * - Custom Field exists ?
     * - Can be used on a test case ?
     * - Custom Field scope includes 'design' ?
     * - is linked to testproject that owns test case ?
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 * @return boolean
	 * @access protected
	 */
    protected function _checkGetTestCaseCustomFieldDesignValueRequest($messagePrefix='')
	{		
	    // $status_ok=$this->authenticate($messagePrefix);
        $cf_name=$this->args[self::$customFieldNameParamName];

  	    //  $testCaseIDParamName = "testcaseid";
	    //  public static $testCaseExternalIDParamName = "testcaseexternalid";
  
        // Custom Field checks:
        // - Custom Field exists ?
        // - Can be used on a test case ?
        // - Custom Field scope includes 'design' ?
        // - is linked to testproject that owns test case ?
        //
 
        // - Custom Field exists ?
        $cfield_mgr=$this->tprojectMgr->cfield_mgr; 
        $cfinfo=$cfield_mgr->get_by_name($cf_name);
        if( !($status_ok=!is_null($cfinfo)) )
        {
	         $msg = sprintf(NO_CUSTOMFIELD_BY_THIS_NAME_STR,$cf_name);
	         $this->errors[] = new IXR_Error(NO_CUSTOMFIELD_BY_THIS_NAME, $messagePrefix . $msg);
        }
      
        // - Can be used on a test case ?
        if( $status_ok )
        {
            $cfield=current($cfinfo);
            $status_ok = (strcasecmp($cfield['node_type'],'testcase') == 0 );
            if( !$status_ok )
            {
	             $msg = sprintf(CUSTOMFIELD_NOT_APP_FOR_NODE_TYPE_STR,$cf_name,'testcase',$cfield['node_type']);
	             $this->errors[] = new IXR_Error(CUSTOMFIELD_NOT_APP_FOR_NODE_TYPE, $messagePrefix . $msg);
            }
        }
 
        // - Custom Field scope includes 'design' ?
        if( $status_ok )
        {
            $status_ok = ($cfield['show_on_design'] || $cfield['enable_on_design']);
            if( !$status_ok )
            {
	             $msg = sprintf(CUSTOMFIELD_HAS_NOT_DESIGN_SCOPE_STR,$cf_name);
	             $this->errors[] = new IXR_Error(CUSTOMFIELD_HAS_NOT_DESIGN_SCOPE, $messagePrefix . $msg);
            }
        }

        // - is linked to testproject that owns test case ?
        if( $status_ok )
        {
            $allCF = $cfield_mgr->get_linked_to_testproject($this->args[self::$testProjectIDParamName]);
            $status_ok=!is_null($allCF) && isset($allCF[$cfield['id']]) ;
            if( !$status_ok )
            {
                $tproject_info = $this->tprojectMgr->get_by_id($this->args[self::$testProjectIDParamName]);
	            $msg = sprintf(CUSTOMFIELD_NOT_ASSIGNED_TO_TESTPROJECT_STR,
	                           $cf_name,$tproject_info['name'],$this->args[self::$testProjectIDParamName]);
	            $this->errors[] = new IXR_Error(CUSTOMFIELD_NOT_ASSIGNED_TO_TESTPROJECT, $messagePrefix . $msg);
            }
             
        }
      
        return $status_ok;
  }



  	/**
	 * getKeywordSet()
	 *  
	 * @param int tproject_id
	 *            
	 * @return string that represent a list of keyword id (comma is character separator)
	 *
	 * @access protected
	 */
	protected function getKeywordSet($tproject_id)
	{ 
    	$kMethod=null;
    	$keywordSet=null;
    	if($this->_isParamPresent(self::$keywordNameParamName))
    	{
    	    $kMethod='getValidKeywordSetByName';
    	    $accessKey=self::$keywordNameParamName;
    	}
		else if ($this->_isParamPresent(self::$keywordIDParamName))
		{
		    $kMethod='getValidKeywordSetById';
		    $accessKey=self::$keywordIDParamName;
		}
		if( !is_null($kMethod) )
		{
    	    $keywordSet=$this->$kMethod($tproject_id,$this->args[$accessKey]);
		}
    	
		return $keywordSet;
	}
	


  	/**
	 * getValidKeywordSetByName()
	 *  
	 * @param int $tproject_id
 	 * @param $keywords array of keywords names
	 *
	 * @return string that represent a list of keyword id (comma is character separator)
	 *
	 * @access protected
	 */
	protected function getValidKeywordSetByName($tproject_id,$keywords)
	{ 
		return $this->getValidKeywordSet($tproject_id,$keywords,true);
	}
	
 	/**
 	 * 
 	 * @param $tproject_id the testprojectID the keywords belong
 	 * @param $keywords array of keywords or keywordIDs
 	 * @param $byName set this to true if $keywords is an array of keywords, false if it's an array of keywordIDs
 	 * @return string that represent a list of keyword id (comma is character separator)
 	 */
	protected function getValidKeywordSet($tproject_id,$keywords,$byName)
	{
		$keywordSet = '';
		$keywords = trim($keywords);
		if($keywords != "")
	  	{
	    	$a_keywords = explode(",",$keywords);
	        $items_qty = count($a_keywords);
	        for($idx = 0; $idx < $items_qty; $idx++)
	        {
				$a_keywords[$idx] = trim($a_keywords[$idx]);
	        }
	        $itemsSet = implode("','",$a_keywords);
	        $sql = " SELECT keyword,id FROM {$this->tables['keywords']} " .
	               " WHERE testproject_id = {$tproject_id} ";
	        
	        if ($byName)
	        {
	        	$sql .= " AND keyword IN ('{$itemsSet}')";
	        }
	        else
	        {
	        	$sql .= " AND id IN ({$itemsSet})";
	        }
         	
	        $keywordMap = $this->dbObj->fetchRowsIntoMap($sql,'keyword');
	        if(!is_null($keywordMap))
	        {
	        	$a_items = null;
	            for($idx = 0; $idx < $items_qty; $idx++)
	            {
	            	if(isset($keywordMap[$a_keywords[$idx]]))
	                {
	                    $a_items[] = $keywordMap[$a_keywords[$idx]]['id'];  
	                }
	            }
	            if( !is_null($a_items))
	            {
	                $keywordSet = implode(",",$a_items);
	            }    
	    	}
		}  
		return $keywordSet;
 	}
 	
  	/**
	 * getValidKeywordSetById()
	 *  
	 * @param int $tproject_id
 	 * @param $keywords array of keywords ID
	 *
	 * @return string that represent a list of keyword id (comma is character separator)
	 *
	 * @access protected
	 */
    protected function  getValidKeywordSetById($tproject_id,$keywords)
    {
		return $this->getValidKeywordSet($tproject_id,$keywords,false);
    }


  	/**
	 * checks if test case version number is a valid.
	 * Checks is is positive intenger
	 *  
	 * @return boolean
	 *
	 * @access protected
	 */
  protected function checkTestCaseVersionNumber()
  {
        $status=true;
        if(!($status=$this->_isParamPresent(self::$versionNumberParamName)))
        {
            $msg = sprintf(MISSING_REQUIRED_PARAMETER_STR,self::$versionNumberParamName);
            $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
        }
        else
        {
            $version = $this->args[self::$versionNumberParamName];
            if( !($status = is_int($version)) )
            {
            	// BUGID 3456
            	$msg = sprintf(PARAMETER_NOT_INT_STR,self::$versionNumberParamName,$version);
            	$this->errors[] = new IXR_Error(PARAMETER_NOT_INT, $msg);
            }
            else 
            {
                if( !($status = ($version > 0)) )
                {
            		$msg = sprintf(VERSION_NOT_VALID_STR,$version);
                    $this->errors[] = new IXR_Error(VERSION_NOT_VALID,$msg);
                }
            }
        }
        return $status;
  }

	 /**
	  * Add a test case version to a test plan 
	  *
	  * @param args['testprojectid']
	  * @param args['testplanid']
	  * @param args['testcaseexternalid']
	  * @param args['version']
	  * @param args['platformid'] - OPTIONAL Only if  test plan has no platforms
	  * @param args['executionorder'] - OPTIONAL
	  * @param args['urgency'] - OPTIONAL
	  *
	  */
	public function addTestCaseToTestPlan($args)
	{
		$operation=__FUNCTION__;
		$messagePrefix="({$operation}) - ";
		$this->_setArgs($args);
		
		$op_result=null;
		$additional_fields='';
		$doDeleteLinks = false;
		$doLink = false;
      	$hasPlatforms = false;
		$hasPlatformIDArgs = false;
		$platform_id = 0;
		$checkFunctions = array('authenticate','checkTestProjectID','checkTestCaseVersionNumber',
		                        'checkTestCaseIdentity','checkTestPlanID');
		
		$status_ok=$this->_runChecks($checkFunctions,$messagePrefix) && $this->userHasRight("testplan_planning");       
		
		// Test Plan belongs to test project ?
		if( $status_ok )
		{
		   $tproject_id = $this->args[self::$testProjectIDParamName];
		   $tplan_id = $this->args[self::$testPlanIDParamName];
		   $tplan_info = $this->tplanMgr->get_by_id($tplan_id);
		   
		   $sql=" SELECT id FROM {$this->tables['testplans']}" .
		        " WHERE testproject_id={$tproject_id} AND id = {$tplan_id}";         
		    
		   $rs=$this->dbObj->get_recordset($sql);
		
		   if( count($rs) != 1 )
		   {
		      $status_ok=false;
		      $tproject_info = $this->tprojectMgr->get_by_id($tproject_id);
		      $msg = sprintf(TPLAN_TPROJECT_KO_STR,$tplan_info['name'],$tplan_id,
		                                           $tproject_info['name'],$tproject_id);  
		      $this->errors[] = new IXR_Error(TPLAN_TPROJECT_KO,$msg_prefix . $msg); 
		   }
		              
		} 
       
        // Test Case belongs to test project ?
        if( $status_ok )
        {
            $ret = $this->checkTestCaseAncestry();
            if( !$ret['status_ok'] )
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
        }
        
        // Does this Version number exist for this test case ?     
        if( $status_ok )
        {
            $tcase_id=$this->args[self::$testCaseIDParamName];
            $version_number=$this->args[self::$versionNumberParamName];
            $sql = " SELECT TCV.version,TCV.id " . 
                   " FROM {$this->tables['nodes_hierarchy']} NH, {$this->tables['tcversions']} TCV " .
                   " WHERE NH.parent_id = {$tcase_id} " .
                   " AND TCV.version = {$version_number} " .
                   " AND TCV.id = NH.id ";
        
           $target_tcversion=$this->dbObj->fetchRowsIntoMap($sql,'version');
           if( !is_null($target_tcversion) && count($target_tcversion) != 1 )
           {
              $status_ok=false;
              $tcase_info=$this->tcaseMgr->get_by_id($tcase_id);
              $msg = sprintf(TCASE_VERSION_NUMBER_KO_STR,$version_number,$tcase_external_id,$tcase_info[0]['name']);  
              $this->errors[] = new IXR_Error(TCASE_VERSION_NUMBER_KO,$msg_prefix . $msg); 
           }                  
                   
        }     

		if( $status_ok )
		{
		    // Optional parameters
		    $additional_fields=null;
		    $additional_values=null;
		    $opt_fields=array(self::$urgencyParamName => 'urgency', self::$executionOrderParamName => 'node_order');
		    $opt_values=array(self::$urgencyParamName => null, self::$executionOrderParamName => 1);
			foreach($opt_fields as $key => $field_name)
			{
			    if($this->_isParamPresent($key))
			    {
			            $additional_values[]=$this->args[$key];
			            $additional_fields[]=$field_name;              
			    }   
			    else
			    {
					if( !is_null($opt_values[$key]) )
			     	{
			            $additional_values[]=$opt_values[$key];
			            $additional_fields[]=$field_name;              
			        }
			 	}
			}
		}

		if( $status_ok )
		{
			// 20100705 - work in progress - BUGID 3564
			// if test plan has platforms, platformid argument is MANDATORY
        	$opt = array('outputFormat' => 'mapAccessByID');
        	$platformSet = $this->tplanMgr->getPlatforms($tplan_id,$opt);  
      		$hasPlatforms = !is_null($platformSet);
			$hasPlatformIDArgs = $this->_isParamPresent(self::$platformIDParamName);
			
			if( $hasPlatforms )
			{
				if( $hasPlatformIDArgs )
				{
					// Check if platform id belongs to test plan
					$platform_id = $this->args[self::$platformIDParamName];
					$status_ok = isset($platformSet[$platform_id]);
					if( !$status_ok )
					{
   			    		$msg = sprintf( PLATFORM_ID_NOT_LINKED_TO_TESTPLAN_STR,
                               			$platform_id,$tplan_info['name']);
   						$this->errors[] = new IXR_Error(PLATFORM_ID_NOT_LINKED_TO_TESTPLAN, $msg);
					}
				}
				else
				{
              		$msg = sprintf(MISSING_PLATFORMID_BUT_NEEDED_STR,$tplan_info['name'],$tplan_id);  
              		$this->errors[] = new IXR_Error(MISSING_PLATFORMID_BUT_NEEDED,$msg_prefix . $msg); 
					$status_ok = false;
				}
			}
		}       
       if( $status_ok )
       {
       	  // 20100711 - franciscom
       	  // Because for TL 1.9 link is done to test plan + platform, logic used 
       	  // to understand what to unlink has to be changed.
       	  // If same version exists on other platforms
       	  //	just add this new record
       	  // If other version exists on other platforms
       	  //	error -> give message to user
       	  //
       	  // 
       	  
          // Other versions must be unlinked, because we can only link ONE VERSION at a time
          // 20090411 - franciscom
          // As implemented today I'm going to unlink ALL linked versions, then if version
          // I'm asking to link is already linked, will be unlinked and then relinked.
          // May be is not wise, IMHO this must be refactored, and give user indication that
          // requested version already is part of Test Plan.
          // 
          $sql = " SELECT TCV.version,TCV.id " . 
                 " FROM {$this->tables['nodes_hierarchy']} NH, {$this->tables['tcversions']} TCV " .
                 " WHERE NH.parent_id = {$tcase_id} " .
                 " AND TCV.id = NH.id ";
                 
          $all_tcversions = $this->dbObj->fetchRowsIntoMap($sql,'id');
          $id_set = array_keys($all_tcversions);

		  // get records regarding all test case versions linked to test plan	
          $in_clause=implode(",",$id_set);
          $sql = " SELECT tcversion_id, platform_id, PLAT.name FROM {$this->tables['testplan_tcversions']} TPTCV " .
                 " LEFT OUTER JOIN {$this->tables['platforms']} PLAT ON PLAT.id = platform_id " . 
                 " WHERE TPTCV.testplan_id={$tplan_id} AND TPTCV.tcversion_id IN({$in_clause}) ";

		  $rs = $this->dbObj->fetchMapRowsIntoMap($sql,'tcversion_id','platform_id');
		  
		  $doLink = is_null($rs);
		  if( !$doLink )
		  {
		  	if( isset($rs[$target_tcversion[$version_number]['id']]) )
		  	{
		  		$plat_keys = array_flip(array_keys($rs[$target_tcversion[$version_number]['id']]));
		  		// need to understand what where the linked platforms.
		  		$platform_id = $this->args[self::$platformIDParamName];
		  		$linkExists = isset($plat_keys[$platform_id]);
 				$doLink = !$linkExists;
		  		if( $linkExists )
		  		{
		  			$platform_name = $rs[$target_tcversion[$version_number]['id']][$platform_id]['name'];
          	  		$msg = sprintf(LINKED_FEATURE_ALREADY_EXISTS_STR,$tplan_info['name'],$tplan_id,
          	  				       $platform_name, $platform_id);  
          	  		$this->errors[] = new IXR_Error(LINKED_FEATURE_ALREADY_EXISTS,$msg_prefix . $msg); 
					$status_ok = false;
		  		}
		  	}	
		  	else 
		  	{
		  		// Other version than requested done is already linked
				$doLink = false;
				reset($rs);
				$linked_tcversion = key($rs);		  		
		  		$other_version = $all_tcversions[$linked_tcversion]['version'];
          	  	$msg = sprintf(OTHER_VERSION_IS_ALREADY_LINKED_STR,$other_version,$version_number,
          	  				   $tplan_info['name'],$tplan_id);
          	  	$this->errors[] = new IXR_Error(OTHER_VERSION_IS_ALREADY_LINKED,$msg_prefix . $msg); 
				$status_ok = false;
		  	}
		  	
          }
		  if( $doLink && $hasPlatforms )
		  {
		 	$additional_values[] = $platform_id;
		 	$additional_fields[] = 'platform_id';              
		  }


          if( $doDeleteLinks && count($id_set) > 0 )
          {
              $in_clause=implode(",",$id_set);
              $sql=" DELETE FROM {$this->tables['testplan_tcversions']} " .
                   " WHERE testplan_id={$tplan_id}  AND tcversion_id IN({$in_clause}) ";
           		$this->dbObj->exec_query($sql);
          }
          
		  if( $doLink)
		  {	
          	$fields="testplan_id,tcversion_id,author_id,creation_ts";
          	if( !is_null($additional_fields) )
          	{
          	   $dummy = implode(",",$additional_fields);
          	   $fields .= ',' . $dummy; 
          	}
          	
          	$sql_values="{$tplan_id},{$target_tcversion[$version_number]['id']}," .
          	            "{$this->userID},{$this->dbObj->db_now()}";
          	if( !is_null($additional_values) )
          	{
          	   $dummy = implode(",",$additional_values);
          	   $sql_values .= ',' . $dummy; 
          	}
          	
          	$sql=" INSERT INTO {$this->tables['testplan_tcversions']} ({$fields}) VALUES({$sql_values})"; 
          	$this->dbObj->exec_query($sql);

          	$op_result['feature_id']=$this->dbObj->insert_id($this->tables['testplan_tcversions']);

          }
          $op_result['operation']=$operation;
          $op_result['status']=true;
          $op_result['message']='';
       }
       
       return ($status_ok ? $op_result : $this->errors);
	 }	

  
	 /**
	  * get set of test suites AT TOP LEVEL of tree on a Test Project
	  *
	  * @param args['testprojectid']
	  *	
	  * @return array
	  *
	  */
   public function getFirstLevelTestSuitesForTestProject($args)
   {
        $msg_prefix="(" .__FUNCTION__ . ") - ";
	    $status_ok=true;
	    $this->_setArgs($args);

        $checkFunctions = array('authenticate','checkTestProjectID');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);

        if( $status_ok )
        {
            $result = $this->tprojectMgr->get_first_level_test_suites($this->args[self::$testProjectIDParamName]);
            if( is_null($result) )
            {
                $status_ok=false;
                $tproject_info = $this->tprojectMgr->get_by_id($this->args[self::$testProjectIDParamName]);
                $msg=$msg_prefix . sprintf(TPROJECT_IS_EMPTY_STR,$tproject_info['name']);
                $this->errors[] = new IXR_ERROR(TPROJECT_IS_EMPTY,$msg); 
            } 
        }
        return $status_ok ? $result : $this->errors;       
   }
   

   /**
    *  Assign Requirements to a test case 
    *  we can assign multiple requirements.
    *  Requirements can belong to different Requirement Spec
    *         
	*  @param struct $args
	*  @param string $args["devKey"]
	*  @param int $args["testcaseexternalid"]
	*  @param int $args["testprojectid"] 
    *  @param string $args["requirements"] 
    *                array(array('req_spec' => 1,'requirements' => array(2,4)),
    *                array('req_spec' => 3,'requirements' => array(22,42))
    *
    */
   public function assignRequirements($args)
   {
        $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";
	    $status_ok=true;
	    $this->_setArgs($args);
        $resultInfo=array();
        $checkFunctions = array('authenticate','checkTestProjectID','checkTestCaseIdentity');       
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix);

        if( $status_ok )
        {
            $ret = $this->checkTestCaseAncestry();
            $status_ok=$ret['status_ok'];
            if( !$status_ok )
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
        }
       
        if( $status_ok )
        {
            $ret = $this->checkReqSpecQuality();
            $status_ok=$ret['status_ok'];
            if( !$status_ok )
            {
                $this->errors[] = new IXR_Error($ret['error_code'], $msg_prefix . $ret['error_msg']); 
            }           
        }
       
        if($status_ok)
        {
            // assignment
            // Note: when test case identity is checked this args key is setted
            //       this does not means that this mut be present on method call.
            //
            $tcase_id=$this->args[self::$testCaseIDParamName];
            foreach($this->args[self::$requirementsParamName] as $item)
            {
                foreach($item['requirements'] as $req_id)
                {
                     $this->reqMgr->assign_to_tcase($req_id,$tcase_id);
                }          
            }
   		      $resultInfo[] = array("operation" => $operation,
   		 	                        "status" => true, "id" => -1, 
   		                            "additionalInfo" => '',
		 	                        "message" => GENERAL_SUCCESS_STR);
        }
        
        return ($status_ok ? $resultInfo : $this->errors);
  }


  /**
   * checks if a test case belongs to test project
   *
   * @param string $messagePrefix used to be prepended to error message
   * 
   * @return map with following keys
   *             boolean map['status_ok']
   *             string map['error_msg']
   *             int map['error_code']
   */
  protected function checkTestCaseAncestry($messagePrefix='')
  {
      $ret=array('status_ok' => true, 'error_msg' => '' , 'error_code' => 0);
      $tproject_id=$this->args[self::$testProjectIDParamName];
      $tcase_id=$this->args[self::$testCaseIDParamName];
      $tcase_external_id=$this->args[self::$testCaseExternalIDParamName];
      $tcase_tproject_id=$this->tcaseMgr->get_testproject($tcase_id);
      
      if($tcase_tproject_id != $tproject_id)
      {
          $status_ok=false;
          $tcase_info=$this->tcaseMgr->get_by_id($tcase_id);
          $tproject_info = $this->tprojectMgr->get_by_id($tproject_id);
          $msg = $messagePrefix . sprintf(TCASE_TPROJECT_KO_STR,$tcase_external_id,$tcase_info[0]['name'],
                                          $tproject_info['name'],$tproject_id);  
          $ret=array('status_ok' => false, 'error_msg' => $msg , 'error_code' => TCASE_TPROJECT_KO);                                               
      } 
      return $ret;
  } // function end


  /*
   *  checks Quality of requirements spec
   *  checks done on 
   *  Requirements Specification is present on system
   *  Requirements Specification belongs to test project
   * 
   * @return map with following keys
   *             boolean map['status_ok']
   *             string map['error_msg']
   *             int map['error_code']
   */
  protected function checkReqSpecQuality()
  {
      $ret=array('status_ok' => true, 'error_msg' => '' , 'error_code' => 0);
      $tproject_id=$this->args[self::$testProjectIDParamName];
      $nodes_types = $this->tprojectMgr->tree_manager->get_available_node_types();
          
      foreach($this->args[self::$requirementsParamName] as $item)
      {
          // does it exist ?
          $req_spec_id=$item['req_spec'];
          $reqspec_info=$this->reqSpecMgr->get_by_id($req_spec_id);      
          if(is_null($reqspec_info))
          {
              $status_ok=false;
              $msg = sprintf(REQSPEC_KO_STR,$req_spec_id);
              $error_code=REQSPEC_KO;
              break;  
          }       
          
          // does it belongs to test project ?
          $a_path=$this->tprojectMgr->tree_manager->get_path($req_spec_id);
          $req_spec_tproject_id=$a_path[0]['parent_id'];
          if($req_spec_tproject_id != $tproject_id)
          {
              $status_ok=false;
              $tproject_info = $this->tprojectMgr->get_by_id($tproject_id);
              $msg = sprintf(REQSPEC_TPROJECT_KO_STR,$reqspec_info['title'],$req_spec_id,
                                                     $tproject_info['name'],$tproject_id);  
              $error_code=REQSPEC_TPROJECT_KO;
              break;  
          }
          
          // does this specification have requirements ?
          $my_requirements = $this->tprojectMgr->tree_manager->get_subtree_list($req_spec_id,$nodes_types['requirement']);
          $status_ok = (trim($my_requirements) != "");
          if(!$status_ok)
          {
              $msg = sprintf(REQSPEC_IS_EMPTY_STR,$reqspec_info['title'],$req_spec_id);
              $error_code = REQSPEC_IS_EMPTY;
              break;
          }
          
          // if everything is OK, analise requirements
          if( $status_ok )
          {
              $dummy=array_flip(explode(",",$my_requirements));
              foreach($item['requirements'] as $req_id)
              {
                  if( !isset($dummy[$req_id]) )
                  {
                      $status_ok=false;
                      $req_info = $this->reqMgr->get_by_id($req_id,requirement_mgr::LATEST_VERSION);
                      
                      if( is_null($req_info) )
                      {
                          $msg = sprintf(REQ_KO_STR,$req_id);
                          $error_code=REQ_KO;
                      }
                      else 
                      {  
                      	  $req_info = $req_inf[0];
                          $msg = sprintf(REQ_REQSPEC_KO_STR,$req_info['req_doc_id'],$req_info['title'],$req_id,
                                         $reqspec_info['title'],$req_spec_id);
                          $error_code=REQ_REQSPEC_KO;
                      }
                      break;
                  }      
              }
          }
          
          if( !$status_ok )
          {
              break;
          }
      }

      if(!$status_ok)
      {
          $ret=array('status_ok' => false, 'error_msg' => $msg , 'error_code' => $error_code);                                               
      } 
      return $ret;
  }

	/**
	 * Insert record into execution_bugs table
	 * @param  int    $executionID	 
	 * @param  string $bugID
	 * @return boolean
	 * @access protected
     * contribution by hnishiyama
	**/
	protected function _insertExecutionBug($executionID, $bugID)
	{
		// Check for existence of executionID
		$sql="SELECT id FROM {$this->tables['executions']} WHERE id={$executionID}";
		$rs=$this->dbObj->fetchRowsIntoMap($sql,'id');
        $status_ok = !(is_null($rs) || $bugID == '');		
		if($status_ok)
		{
            $safeBugID=$this->dbObj->prepare_string($bugID);
       	    $sql="SELECT execution_id FROM {$this->tables['execution_bugs']} " .  
		         "WHERE execution_id={$executionID} AND bug_id='{$safeBugID}'";
        
            if( is_null($this->dbObj->fetchRowsIntoMap($sql, 'execution_id')) )
            {
            	$sql = "INSERT INTO {$this->tables['execution_bugs']} " .
                       "(execution_id,bug_id) VALUES({$executionID},'{$safeBugID}')";
                $result = $this->dbObj->exec_query($sql); 
                $status_ok=$result ? true : false ;
            }
		}
		return $status_ok;
	}


/**
 *  get bugs linked to an execution ID
 * @param  int $execution_id	 
 *
 * @return map indexed by bug_id
 */
protected function _getBugsForExecutionId($execution_id)
{
    $rs=null;
    if( !is_null($execution_id) && $execution_id <> '' )
    {
        $sql = "SELECT execution_id,bug_id, B.name AS build_name " .
               "FROM {$this->tables['execution_bugs']} ," .
               " {$this->tables['executions']} E, {$this->tables['builds']} B ".
               "WHERE execution_id={$execution_id} " .
               "AND   execution_id=E.id " .
               "AND   E.build_id=B.id " .
               "ORDER BY B.name,bug_id";
        $rs=$this->dbObj->fetchRowsIntoMap($sql,'bug_id');
    }
    return $rs;   
}



/**
 * Gets attachments for specified test case.
 * The attachment file content is Base64 encoded. To save the file to disk in client,
 * Base64 decode the content and write file in binary mode. 
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["testcaseid"]: optional, if does not is present           
 *                                 testcaseexternalid must be present
 *
 * @param int $args["testcaseexternalid"]: optional, if does not is present           
 *                                         testcaseid must be present
 * 
 * @return mixed $resultInfo
 */
public function getTestCaseAttachments($args)
{
	$this->_setArgs($args);
	$attachments=null;
	$checkFunctions = array('authenticate','checkTestCaseIdentity');       
    $status_ok=$this->_runChecks($checkFunctions) && $this->userHasRight("mgt_view_tc");
	
	if($status_ok)
	{		
	    $tcase_id = $this->args[self::$testCaseIDParamName];
		$attachmentRepository = tlAttachmentRepository::create($this->dbObj);
		$attachmentInfos = $attachmentRepository->getAttachmentInfosFor($tcase_id,"nodes_hierarchy");
		
		if ($attachmentInfos)
		{
			foreach ($attachmentInfos as $attachmentInfo)
			{
				$aID = $attachmentInfo["id"];
				$content = $attachmentRepository->getAttachmentContent($aID, $attachmentInfo);
				
				if ($content != null)
				{
					$attachments[$aID]["id"] = $aID;
					$attachments[$aID]["name"] = $attachmentInfo["file_name"];
					$attachments[$aID]["file_type"] = $attachmentInfo["file_type"];
					$attachments[$aID]["title"] = $attachmentInfo["title"];
					$attachments[$aID]["date_added"] = $attachmentInfo["date_added"];
					$attachments[$aID]["content"] = base64_encode($content);
				}
			}
		}
	}
  return $status_ok ? $attachments : $this->errors;
}


    /**
	 * create a test suite
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testprojectid"]
	 * @param string $args["testsuitename"]
	 * @param string $args["details"]
	 * @param int $args["parentid"] optional, if do not provided means test suite must be top level.
	 * @param int $args["order"] optional. Order inside parent container
	 * @param int $args["checkduplicatedname"] optional, default true.
	 *                                          will check if there are siblings with same name.
     *
     * @param int $args["actiononduplicatedname"] optional
     *                                            applicable only if $args["checkduplicatedname"]=true
	 *                                            what to do if already a sibling exists with same name.
	 *	 
	 * @return mixed $resultInfo
	 */
    public function createTestSuite($args)
	{
	    $result=array();
	    $this->_setArgs($args);
	    $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";
        $checkFunctions = array('authenticate','checkTestSuiteName','checkTestProjectID');
        $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_modify_tc");
      
        if( $status_ok )
        {
            // Optional parameters
            $opt=array(self::$orderParamName => testsuite::DEFAULT_ORDER,
                       self::$checkDuplicatedNameParamName => testsuite::CHECK_DUPLICATE_NAME,
                       self::$actionOnDuplicatedNameParamName => 'block');
            
		    foreach($opt as $key => $value)
		    {
		        if($this->_isParamPresent($key))
		        {
		            $opt[$key]=$this->args[$key];      
		        }   
		    }
        }

        if($status_ok)
        {
            $parent_id = $args[self::$testProjectIDParamName];  
            $tprojectInfo=$this->tprojectMgr->get_by_id($args[self::$testProjectIDParamName]);
            $tsuiteMgr = new testsuite($this->dbObj);
  		    if( $this->_isParamPresent(self::$parentIDParamName) )
  		    {
  		        $parent_id = $args[self::$parentIDParamName];

                // if parentid exists it must:
                // be a test suite id 
  		        $node_info = $tsuiteMgr->get_by_id($args[self::$parentIDParamName]);
  		        if( !($status_ok=!is_null($node_info)) )
  		        {
                   $msg=sprintf(INVALID_PARENT_TESTSUITEID_STR,
                                $args[self::$parentIDParamName],$args[self::$testSuiteNameParamName]);
                   $this->errors[] = new IXR_Error(INVALID_PARENT_TESTSUITEID,$msg_prefix . $msg);
                }
              
                if($status_ok)
                {
                   // Must belong to target test project
                   $root_node_id=$tsuiteMgr->getTestProjectFromTestSuite($args[self::$parentIDParamName],null);
                  
                   if( !($status_ok = ($root_node_id == $args[self::$testProjectIDParamName])) )
                   {
                     $msg=sprintf(TESTSUITE_DONOTBELONGTO_TESTPROJECT_STR,$args[self::$parentIDParamName],
                                  $tprojectInfo['name'],$args[self::$testProjectIDParamName]);
                     $this->errors[] = new IXR_Error(TESTSUITE_DONOTBELONGTO_TESTPROJECT,$msg_prefix . $msg);
                   }
                }
  		    } 
      }
      
      if($status_ok)
      {
          $op=$tsuiteMgr->create($parent_id,$args[self::$testSuiteNameParamName],
                                 $args[self::$detailsParamName],$opt[self::$orderParamName],
                                 $opt[self::$checkDuplicatedNameParamName],
                                 $opt[self::$actionOnDuplicatedNameParamName]);
          
          if( ($status_ok = $op['status_ok']) )
          {
              $op['status'] = $op['status_ok'] ? true : false;
              $op['operation'] = $operation;
              $op['additionalInfo'] = '';
              $op['message'] = $op['msg'];
              unset($op['msg']);
              unset($op['status_ok']);
              $result[]=$op;  
          }
          else
          {
              $op['msg']=sprintf($op['msg'],$args[self::$testSuiteNameParamName]);
              $this->errors=$op;   
          }
      }
      
			return $status_ok ? $result : $this->errors;
	}


	/**
	 * test suite name provided is valid 
	 * 
	 * @param string $messagePrefix used to be prepended to error message
     *
	 * @return boolean
	 * @access protected
	 */        
    protected function checkTestSuiteName($messagePrefix='')
    {
        $status_ok=isset($this->args[self::$testSuiteNameParamName]) ? true : false;
        if($status_ok)
        {
    	      $name = $this->args[self::$testSuiteNameParamName];
    	      if(!is_string($name))
    	      {
                $msg=$messagePrefix . TESTSUITENAME_NOT_STRING_STR;
    	      	$this->errors[] = new IXR_Error(TESTSUITENAME_NOT_STRING, $msg);
    	      	$status_ok=false;
    	      }
        }
        else
        {
       	  	$this->errors[] = new IXR_Error(NO_TESTSUITENAME, $messagePrefix . NO_TESTSUITENAME_STR);
        }
        return $status_ok;
    }




    /**
     * Gets info about target test project
     *
     * @param struct $args
     * @param string $args["devKey"]
     * @param string $args["testprojectname"]     
     * @return mixed $resultInfo			
     * @access public
     */		
    public function getTestProjectByName($args)
    {
        $msg_prefix="(" .__FUNCTION__ . ") - ";
   	    $status_ok=true;
    	$this->_setArgs($args);		
    	if($this->authenticate())
    	{
    	    $status_ok=false; 
            if( $this->_isParamPresent(self::$testProjectNameParamName,$msg_prefix,self::SET_ERROR) )
            {
                $name=trim($this->args[self::$testProjectNameParamName]);
                $check_op=$this->tprojectMgr->checkNameExistence($name);
                $not_found=$check_op['status_ok'];     
                $status_ok=!$not_found;
                if($not_found)      
                {
                    $status_ok=false;
                    $msg = $msg_prefix . sprintf(TESTPROJECTNAME_DOESNOT_EXIST_STR,$name);
                    $this->errors[] = new IXR_Error(TESTPROJECTNAME_DOESNOT_EXIST, $msg);
                }
            }
    	}
        if($status_ok)
        {
            $info=$this->tprojectMgr->get_by_name($name);            
        }
        return $status_ok ? $info : $this->errors;
    }


    /**
     * Gets info about target test project
     *
     * @param struct $args
     * @param string $args["devKey"]
     * @param string $args["testprojectname"]     
     * @param string $args["testplanname"]     
     * @return mixed $resultInfo			
     * @access public
     */		
    public function getTestPlanByName($args)
    {
        $msg_prefix="(" .__FUNCTION__ . ") - ";
   	    $status_ok=true;
    	$this->_setArgs($args);		
    	if($this->authenticate())
    	{
            $keys2check = array(self::$testPlanNameParamName,
                                self::$testProjectNameParamName);
            foreach($keys2check as $key)
            {
                $names[$key]=$this->_isParamPresent($key,$msg_prefix,self::SET_ERROR) ? trim($this->args[$key]) : '';
                if($names[$key]=='')
                {
                    $status_ok=false;    
                    breack;
                }
            }
        }
    	
    	if($status_ok)
    	{
            // need to check name existences
            $name=$names[self::$testProjectNameParamName];
            $check_op=$this->tprojectMgr->checkNameExistence($name);
            $not_found=$check_op['status_ok'];     
            $status_ok=!$not_found;
            if($not_found)      
            {
                $status_ok=false;
                $msg = $msg_prefix . sprintf(TESTPROJECTNAME_DOESNOT_EXIST_STR,$name);
                $this->errors[] = new IXR_Error(TESTPROJECTNAME_DOESNOT_EXIST, $msg);
            }
    	    else
    	    {
    	        $tprojectInfo=current($this->tprojectMgr->get_by_name($name));
    	    }
    	}
    	
    	if($status_ok)
    	{
    	    $name=trim($names[self::$testPlanNameParamName]);
            $info = $this->tplanMgr->get_by_name($name,$tprojectInfo['id']);
            if( !($status_ok=!is_null($info)) )
            {
                $msg = $msg_prefix . sprintf(TESTPLANNAME_DOESNOT_EXIST_STR,$name,$tprojectInfo['name']);
                $this->errors[] = new IXR_Error(TESTPLANNAME_DOESNOT_EXIST, $msg);
            
            }
        }

        return $status_ok ? $info : $this->errors;
    }


/**
* get test case specification using external ir internal id
* 
* @param struct $args
* @param string $args["devKey"]
* @param int $args["testcaseid"]: optional, if does not is present           
*                                 testcaseexternalid must be present
*
* @param int $args["testcaseexternalid"]: optional, if does not is present           
*                                         testcaseid must be present
* @param int $args["version"]: optional, if does not is present max version number will be
*                                        retuned
*
* @return mixed $resultInfo
*/
public function getTestCase($args)
{
    $msg_prefix="(" .__FUNCTION__ . ") - ";
    $status_ok=true;
    $this->_setArgs($args);
    
    $checkFunctions = array('authenticate','checkTestCaseIdentity');       
    $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_view_tc");       
    $version_id=testcase::LATEST_VERSION;
    $version_number=-1;

    if( $status_ok )
    {			
        // check optional arguments
        if( $this->_isParamPresent(self::$versionNumberParamName) )
        {
            if( ($status_ok=$this->checkTestCaseVersionNumber()) )
            {
                $version_id=null;
                $version_number=$this->args[self::$versionNumberParamName];
            }
        }
    }
    
    if( $status_ok )
    {			
        $testCaseMgr = new testcase($this->dbObj);
        $id=$this->args[self::$testCaseIDParamName];
        
        $result = $testCaseMgr->get_by_id($id,$version_id,'ALL','ALL',$version_number);            
        if(0 == sizeof($result))
        {
            $status_ok=false;
            $this->errors[] = new IXR_ERROR(NO_TESTCASE_FOUND, 
                                            $msg_prefix . NO_TESTCASE_FOUND_STR);
            return $this->errors;
        }
    }

    return $status_ok ? $result : $this->errors; 
}



	/**
	 * create a test plan
	 * 
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testplanname"]
	 * @param int $args["testprojectname"]
	 * @param string $args["notes"], optional
	 * @param string $args["active"], optional default value 1
	 * @param string $args["public"], optional default value 1
     *	 
	 * @return mixed $resultInfo
	 * @internal revision
	 *	20100704 - franciscom - BUGID 3565
	 */
	public function createTestPlan($args)
	{
	    $this->_setArgs($args);
	    $status_ok = false;    
        $msg_prefix="(" . __FUNCTION__ . ") - ";

    	if($this->authenticate() && $this->userHasRight("mgt_modify_product"))
    	{
            $keys2check = array(self::$testPlanNameParamName,
                                self::$testProjectNameParamName);
        
        	$status_ok = true;
            foreach($keys2check as $key)
            {
                $names[$key]=$this->_isParamPresent($key,$msg_prefix,self::SET_ERROR) ? trim($this->args[$key]) : '';
                if($names[$key]=='')
                {
                    $status_ok=false;    
                    break;
                }
            }
        }

        if( $status_ok )
        {
            $name=trim($this->args[self::$testProjectNameParamName]);
            $check_op=$this->tprojectMgr->checkNameExistence($name);
            $status_ok=!$check_op['status_ok'];     
            if($status_ok) 
            {
                $tprojectInfo=current($this->tprojectMgr->get_by_name($name));
            }
            else     
            {
                $status_ok=false;
                $msg = $msg_prefix . sprintf(TESTPROJECTNAME_DOESNOT_EXIST_STR,$name);
                $this->errors[] = new IXR_Error(TESTPROJECTNAME_DOESNOT_EXIST, $msg);
            }
        }

        if( $status_ok )
        {
    	    $name=trim($names[self::$testPlanNameParamName]);
            $info = $this->tplanMgr->get_by_name($name,$tprojectInfo['id']);
            $status_ok=is_null($info);
            
            if( !($status_ok=is_null($info)))
            {
                $msg = $msg_prefix . sprintf(TESTPLANNAME_ALREADY_EXISTS_STR,$name,$tprojectInfo['name']);
                $this->errors[] = new IXR_Error(TESTPLANNAME_ALREADY_EXISTS, $msg);
            }
        }

        if( $status_ok )
        {
            $keys2check = array(self::$activeParamName => 1,self::$publicParamName => 1,
                                self::$noteParamName => '');
  		    foreach($keys2check as $key => $value)
  		    {
  		        $optional[$key]=$this->_isParamPresent($key) ? trim($this->args[$key]) : $value;
  		    }
            $retval = $this->tplanMgr->create(htmlspecialchars($name),
                                              htmlspecialchars($optional[self::$noteParamName]),
                                              $tprojectInfo['id'],$optional[self::$activeParamName],
                                              $optional[self::$publicParamName]);

		    $resultInfo = array();
		    $resultInfo[]= array("operation" => __FUNCTION__,"additionalInfo" => null,
			                     "status" => true, "id" => $retval, "message" => GENERAL_SUCCESS_STR);
        }

        return $status_ok ? $resultInfo : $this->errors;
	} // public function createTestPlan


	/**
	 * Gets full path from the given node till the top using nodes_hierarchy_table
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param mixed $args["nodeID"] can be just a single node or an array of INTERNAL (DB) ID
	 * @return mixed $resultInfo			
	 * @access public
	 *
	 * @internal revision
	 * BUGID 3993
	 * $args["nodeID"] can be just a single node or an array
	 * when path can not be found same date structure will be returned, that on situations
	 * where all is ok, but content for KEY(nodeID) will be NULL instead of rising ERROR  
	 *
	 */		
	public function getFullPath($args)
	{
	  	$this->_setArgs($args);
	  	$operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";
	    $checkFunctions = array('authenticate');
	    $status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && 
	               $this->_isParamPresent(self::$nodeIDParamName,$msg_prefix,self::SET_ERROR) ;
	  
	    if( $status_ok )
	    {
	        $nodeIDSet = $this->args[self::$nodeIDParamName];
	        
	        // if is array => OK
	        if( !($workOnSet = is_array($nodeIDSet)) && (!is_int($nodeIDSet) || $nodeIDSet <= 0) )
	    	{
	            $msg = $msg_prefix . sprintf(NODEID_INVALID_DATA_TYPE);
	            $this->errors[] = new IXR_Error(NODEID_INVALID_DATA_TYPE, $msg);
	            $status_ok=false;
	        } 
	        
	        if( $status_ok && $workOnSet)
	        {
	        	// do check on each item on set
	        	foreach($nodeIDSet as $itemID)
	        	{
	        		if(!is_int($itemID) || $itemID <= 0) 
	    	{
	            		$msg = $msg_prefix . sprintf(NODEID_IS_NOT_INTEGER_STR,$itemID);
	            $this->errors[] = new IXR_Error(NODEID_IS_NOT_INTEGER, $msg);
	            $status_ok=false;
	        } 
	    }
	        }
	        
	    }
	    
	    if( $status_ok )
	    {
	    	// IMPORTANT NOTICE:
	    	// (may be a design problem but ..)
	    	// If $nodeIDSet is an array and for one of items path can not be found
	    	// get_full_path_verbose() returns null, no matter if for other items
	    	// information is available
	    	// 
	        $full_path = $this->tprojectMgr->tree_manager->get_full_path_verbose($nodeIDSet);
		}
	    return $status_ok ? $full_path : $this->errors;
	}

    /**
 	 * 
     *
     */
	protected function _insertCustomFieldExecValues($executionID)
	{
		// // Check for existence of executionID   
		$status_ok=true;
		$sql="SELECT id FROM {$this->tables['executions']} WHERE id={$executionID}";
		$rs=$this->dbObj->fetchRowsIntoMap($sql,'id');
		// 
        $cfieldSet=$this->args[self::$customFieldsParamName];
        $tprojectID=$this->tcaseMgr->get_testproject($this->args[self::$testCaseIDParamName]);
        $tplanID=$this->args[self::$testPlanIDParamName];
        $cfieldMgr=$this->tprojectMgr->cfield_mgr;        
        $cfieldsMap = $cfieldMgr->get_linked_cfields_at_execution($tprojectID, 1,'testcase',
                                                                  null,null,null,'name');
        $status_ok = !(is_null($rs) || is_null($cfieldSet) || count($cfieldSet) == 0);		
        $cfield4write = null;
        if( $status_ok && !is_null($cfieldsMap) )
        {
        	foreach($cfieldSet as $name => $value)
        	{
             	if( isset($cfieldsMap[$name]) )
             	{
         	    	$cfield4write[$cfieldsMap[$name]['id']] = array("type_id"  => $cfieldsMap[$name]['type'],
                                                              "cf_value" => $value);
		       	}
             }	
             if( !is_null($cfield4write) )
             {
             	$cfieldMgr->execution_values_to_db($cfield4write,$this->tcVersionID,$executionID,$tplanID,
                                                    null,'write-through');
             }
        }        
		return $status_ok;
	}



	 /**
	 * delete an execution
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["executionid"]
	 *
	 * @return mixed $resultInfo 
	 * 				[status]	=> true/false of success
	 * 				[id]		  => result id or error code
	 * 				[message]	=> optional message for error message string
	 * @access public
	 */	
	 public function deleteExecution($args)
	 {		
		$resultInfo = array();
        $operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";
		$execCfg = config_get('exec_cfg');

		$this->_setArgs($args);              
		$resultInfo[0]["status"] = false;
		
        $checkFunctions = array('authenticate','checkExecutionID');       
        $status_ok = $this->_runChecks($checkFunctions,$msg_prefix);       
	
	    // Important userHasRight sets error object
	    //
        $status_ok = ($status_ok && $this->userHasRight("testplan_execute"));	
		if($status_ok)
		{			
			if( $execCfg->can_delete_execution )  
			{
				$this->tcaseMgr->deleteExecution($args[self::$executionIDParamName]);			
    	    	$resultInfo[0]["status"] = true;
				$resultInfo[0]["id"] = $args[self::$executionIDParamName];	
				$resultInfo[0]["message"] = GENERAL_SUCCESS_STR;
				$resultInfo[0]["operation"] = $operation;
			}
			else
			{
				$status_ok = false;
    		    $this->errors[] = new IXR_Error(CFG_DELETE_EXEC_DISABLED, 
    		                                    CFG_DELETE_EXEC_DISABLED_STR);
			}
		}

		return $status_ok ? $resultInfo : $this->errors;
	}

	/**
	 * Helper method to see if an execution id exists on DB
	 * no checks regarding other data like test case , test plam, build, etc are done
	 * 
	 * 
	 * 	
	 * @return boolean
	 * @access protected
	 */        
    protected function checkExecutionID($messagePrefix='',$setError=false)
    {
        // need to be implemented - franciscom
		$pname = self::$executionIDParamName;
		$status_ok = $this->_isParamPresent($pname,$messagePrefix,$setError);
		if(!$status_ok)
		{		
	        $msg = $messagePrefix . sprintf(MISSING_REQUIRED_PARAMETER_STR, $pname);
	        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
		}
		else
		{
			$status_ok = is_int($this->args[$pname]) && $this->args[$pname] > 0;
			if( !$status_ok )
			{
            	$msg = $messagePrefix . sprintf(PARAMETER_NOT_INT_STR,$pname,$this->args[$pname]);
            	$this->errors[] = new IXR_Error(PARAMETER_NOT_INT, $msg);
			}
			else
			{
				
			}
		}
		return $status_ok;
    }



	/**
	 * Helper method to see if the platform identity provided is valid 
	 * This is the only method that should be called directly to check platform identity
	 * 	
	 * If everything OK, platform id is setted.
	 *
	 * @param int $tplanID Test Plan ID
	 * @param map $platformInfo key: platform ID
	 * @param string $messagePrefix used to be prepended to error message
	 *
	 *
	 * @return boolean
	 * @access protected
	 */    
    protected function checkPlatformIdentity($tplanID,$platformInfo=null,$messagePrefix='')
    {
        $status=true;
        $platformID=0;
        $myErrors=array();

        $name_exists = $this->_isParamPresent(self::$platformNameParamName,$messagePrefix);
        $id_exists = $this->_isParamPresent(self::$platformIDParamName,$messagePrefix);
        $status = $name_exists | $id_exists;
        // for debug - file_put_contents('c:\checkPlatformIdentity.txt', $status ? 1:0);                            

        if(!$status)
        {
        	$pname = self::$platformNameParamName . ' OR ' . self::$platformIDParamName; 
	        $msg = $messagePrefix . sprintf(MISSING_REQUIRED_PARAMETER_STR, $pname);
	        $this->errors[] = new IXR_Error(MISSING_REQUIRED_PARAMETER, $msg);				      
		}        
        
        if($status)
        {
      		// get test plan name is useful for error messages
       		$tplanInfo = $this->tplanMgr->get_by_id($tplanID);
       		if(is_null($platformInfo))
       		{
       			$platformInfo = $this->tplanMgr->getPlatforms($tplanID,array('outputFormat' => 'map'));  
       		}

            if(is_null($platformInfo))
            {
        		$status = false;
   				$msg = sprintf($messagePrefix . TESTPLAN_HAS_NO_PLATFORMS_STR,$tplanInfo['name']);
   				$this->errors[] = new IXR_Error(TESTPLAN_HAS_NO_PLATFORMS, $msg);
            }
            
        }
         
        if( $status )
        {
        	$platform_name = null;
        	$platform_id = null;
        	if($name_exists)
        	{ 
        		// file_put_contents('c:\checkPlatformIdentity.txt', $this->args[self::$platformNameParamName]);                            
        		// file_put_contents('c:\checkPlatformIdentity.txt', serialize($platformInfo));                            
        		// $this->errors[]=$platformInfo;
        		$platform_name = $this->args[self::$platformNameParamName];
        		$status = in_array($this->args[self::$platformNameParamName],$platformInfo);
            }
            else
            {
            	$platform_id = $this->args[self::$platformIDParamName];
            	$status = isset($platformInfo[$this->args[self::$platformIDParamName]]);
            }
            
        	if( !$status )
        	{
        		// Platform does not exist in target testplan
        		// Can I Try to understand if platform exists on test project ?
				// $this->tprojectMgr->        		
   			    $msg = sprintf($messagePrefix . PLATFORM_NOT_LINKED_TO_TESTPLAN_STR,
                               $platform_name,$platform_id,$tplanInfo['name']);
   				$this->errors[] = new IXR_Error(PLATFORM_NOT_LINKED_TO_TESTPLAN, $msg);
        	}	
        }
        
        if($status)
        {
        	if($name_exists)
        	{ 
 	       		$dummy = array_flip($platformInfo);
        		$this->args[self::$platformIDParamName] = $dummy[$this->args[self::$platformNameParamName]];
        	}
        }
	    return $status;
    }   



   /**
     * update result of LASTE execution
     *
     * @param
     * @param struct $args
     * @param string $args["devKey"]
     * @param int $args["testplanid"]
     * @param int $args["platformid"]
     * @param int $args["buildid"]
     * @param int $args["testcaseid"] internal ID
     * @param string $args["status"]
     * @param string $args["notes"]
     *
     * @return mixed $resultInfo
     * 
     * @access protected
     */

	protected function _updateResult()
	{
		$platform_id = 0;
		$exec_id = 0;
		$build_id = $this->args[self::$buildIDParamName];
		$tester_id =  $this->userID;
		$status = $this->args[self::$statusParamName];
		$testplan_id =	$this->args[self::$testPlanIDParamName];
		$tcversion_id =	$this->tcVersionID;
    	$tcase_id = $this->args[self::$testCaseIDParamName];
    	$db_now=$this->dbObj->db_now();
    
    	if( isset($this->args[self::$platformIDParamName]) )
		{
			$platform_id = $this->args[self::$platformIDParamName]; 	
		}

		// Here steps and expected results are not needed => do not request => less data on network
		$options = array('getSteps' => 0);
		$last_exec = $this->tcaseMgr->get_last_execution($tcase_id,testcase::ALL_VERSIONS,
		                                                 $testplan_id,$build_id,$platform_id,$options);
    	
    	if( !is_null($last_exec) )
    	{
    		$last_exec = current($last_exec);
			$execution_type = constant("TESTCASE_EXECUTION_TYPE_AUTO");
            $exec_id = $last_exec['execution_id'];
			$notes = '';
    		$notes_update = '';
			
			if($this->_isNotePresent())
			{
				$notes = $this->dbObj->prepare_string($this->args[self::$noteParamName]);
			}
			
			if(trim($notes) != "")
			{
			    $notes_update = ",notes='{$notes}'";  
			}
    		
			$sql = " UPDATE {$this->tables['executions']} " .
			       " SET tester_id={$tester_id}, execution_ts={$db_now}," . 
			       " status='{$status}', execution_type= {$execution_type} " . 
			       " {$notes_update}  WHERE id = {$exec_id}";
			
            $this->dbObj->exec_query($sql);
    	}
		return $exec_id;
	}	

   /**
     * Return a TestSuite by ID
     *
     * @param
     * @param struct $args
     * @param string $args["devKey"]
     * @param int $args["testsuiteid"]
     * @return mixed $resultInfo
     * 
     * @access public
     */
    public function getTestSuiteByID($args)
    { 
        $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";

        $this->_setArgs($args);
        $status_ok=$this->_runChecks(array('authenticate','checkTestSuiteID'),$msg_prefix);

        $details='simple';
        $key2search=self::$detailsParamName;
        if( $this->_isParamPresent($key2search) )
        { 
            $details=$this->args[$key2search];
        }

        if($status_ok && $this->userHasRight("mgt_view_tc"))
        { 
            $testSuiteID = $this->args[self::$testSuiteIDParamName];
            $tsuiteMgr = new testsuite($this->dbObj);
            return $tsuiteMgr->get_by_id($testSuiteID);

        }
        else
        { 
            return $this->errors;
        }
    }

	/**
	 * get list of TestSuites which are DIRECT children of a given TestSuite
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["testsuiteid"]
	 * @return mixed $resultInfo
	 *
	 * @access public
	 */
	public function getTestSuitesForTestSuite($args)
	{
	    $operation=__FUNCTION__;
	    $msg_prefix="({$operation}) - ";
	    $items = null;
	
	    $this->_setArgs($args);
	    $status_ok = $this->_runChecks(array('authenticate','checkTestSuiteID'),$msg_prefix) && 
	                 $this->userHasRight("mgt_view_tc");
	    if( $status_ok )
	    {
	        $testSuiteID = $this->args[self::$testSuiteIDParamName];
	        $tsuiteMgr = new testsuite($this->dbObj);
	        $items = $tsuiteMgr->get_children($testSuiteID);
	    }
	    return $status_ok ? $items : $this->errors;
	}


	/**
     * Returns the list of platforms associated to a given test plan
     *
     * @param
     * @param struct $args
     * @param string $args["devKey"]
     * @param int $args["testplanid"]
     * @return mixed $resultInfo
     * 
     * @access public
     */
	public function getTestPlanPlatforms($args)
    {
	    $operation=__FUNCTION__;
        $msg_prefix="({$operation}) - ";
    	$this->_setArgs($args);	
		$status_ok = false;
		$items = null;
		
		// Checks if a test plan id was provided
		$status_ok = $this->_isParamPresent(self::$testPlanIDParamName,$msg_prefix,self::SET_ERROR);
		
		if($status_ok)
		{
			// Checks if the provided test plan id is valid
			$status_ok=$this->_runChecks(array('authenticate','checkTestPlanID'),$msg_prefix);
		}
        if($status_ok)
        {
			$tplanID = $this->args[self::$testPlanIDParamName];
        	// get test plan name is useful for error messages
			$tplanInfo = $this->tplanMgr->get_by_id($tplanID);
        	$items = $this->tplanMgr->getPlatforms($tplanID);  
            if(! ($status_ok = !is_null($items)) )
            {
   				$msg = sprintf($messagePrefix . TESTPLAN_HAS_NO_PLATFORMS_STR,$tplanInfo['name']);
   				$this->errors[] = new IXR_Error(TESTPLAN_HAS_NO_PLATFORMS, $msg);
            }
        }
	    return $status_ok ? $items : $this->errors;
    }   

	/**
	 * Gets the summarized results grouped by platform.
	 * @see testplan:getStatusTotalsByPlatform()
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["tplanid"] test plan id
	 *
	 * @return map where every element has:
	 *
	 *	'type' => 'platform'
	 *	'total_tc => ZZ
	 *	'details' => array ( 'passed' => array( 'qty' => X)
	 *	                     'failed' => array( 'qty' => Y)
	 *	                     'blocked' => array( 'qty' => U)
	 *                       ....)
	 *
	 * @access public
	 */
	public function getTotalsForTestPlan($args)
	{
		$operation=__FUNCTION__;
		$msg_prefix="({$operation}) - ";
		$total = null;
		
		$this->_setArgs($args);
		$status_ok=true;

		// Checks are done in order
		$checkFunctions = array('authenticate','checkTestPlanID');
		$status_ok=$this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_view_tc");

		if( $status_ok )
		{
			$total = $this->tplanMgr->getStatusTotalsByPlatform($this->args[self::$testPlanIDParamName]);
		}

		return $status_ok ? $total : $this->errors;
	}



	/**
	 * @param struct $args
	 * @param string $args["devKey"]
	 * @param int $args["user"] user name
	 *
	 * @return true if everything OK, otherwise error structure
	 *
	 * @access public
	 */
	public function doesUserExist($args)
	{
		$operation=__FUNCTION__;
 	    $msg_prefix="({$operation}) - ";
	    $this->_setArgs($args);
            
		$user_id = tlUser::doesUserExist($this->dbObj,$this->args[self::$userParamName]);		    	
		if( !($status_ok = !is_null($user_id)) )
		{
			$msg = $msg_prefix . sprintf(NO_USER_BY_THIS_LOGIN_STR,$this->args[self::$userParamName]);
			$this->errors[] = new IXR_Error(NO_USER_BY_THIS_LOGIN, $msg);	
		}
		return $status_ok ? $status_ok : $this->errors;
	}


	/**
	 * check if Developer Key exists.
	 *
	 * @param struct $args
	 * @param string $args["devKey"]
	 *
	 * @return true if everything OK, otherwise error structure
	 *
	 * @access public
	 */
	public function checkDevKey($args)
	{
	    $operation=__FUNCTION__;
		$msg_prefix="({$operation}) - ";
		$this->_setArgs($args);
	    $checkFunctions = array('authenticate');
	    $status_ok = $this->_runChecks($checkFunctions,$msg_prefix);
	    return $status_ok ? $status_ok : $this->errors;        
	}


/**
 * Uploads an attachment for a Requirement Specification.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["reqspecid"] The Requirement Specification ID
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the error map.
 */
public function uploadRequirementSpecificationAttachment($args)
{
	$msg_prefix = "(" .__FUNCTION__ . ") - ";
	$args[self::$foreignKeyTableNameParamName] = 'req_specs';
	$args[self::$foreignKeyIdParamName] = $args['reqspecid'];
    $this->_setArgs($args);
	return $this->uploadAttachment($args,$msg_prefix,false);
}

/**
 * Uploads an attachment for a Requirement.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["requirementid"] The Requirement ID
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the erros map.
 */
public function uploadRequirementAttachment($args)
{
	$msg_prefix = "(" .__FUNCTION__ . ") - ";
	$args[self::$foreignKeyTableNameParamName] = 'requirements';
	$args[self::$foreignKeyIdParamName] = $args['requirementid'];
    $this->_setArgs($args);
	return $this->uploadAttachment($args,$msg_prefix,false);
}

/**
 * Uploads an attachment for a Test Project.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["testprojectid"] The Test Project ID
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the erros map.
 */
public function uploadTestProjectAttachment($args)
{
	$msg_prefix = "(" .__FUNCTION__ . ") - ";
	$ret = null;
	
	$args[self::$foreignKeyTableNameParamName] = 'nodes_hierarchy';
	$args[self::$foreignKeyIdParamName] = $args[self::$testProjectIDParamName];
    $this->_setArgs($args);
    
	$checkFunctions = array('authenticate', 'checkTestProjectID');
    $statusOk = $this->_runChecks($checkFunctions) && $this->userHasRight("mgt_view_tc");
    $ret = $statusOk ? $this->uploadAttachment($args,$msg_prefix,false) : $this->errors;
	return $ret;
}

/**
 * Uploads an attachment for a Test Suite.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["testsuiteid"] The Test Suite ID
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the erros map.
 */
public function uploadTestSuiteAttachment($args)
{
	$msg_prefix = "(" .__FUNCTION__ . ") - ";
	$args[self::$foreignKeyTableNameParamName] = 'nodes_hierarchy';
	$args[self::$foreignKeyIdParamName] = $args[self::$testSuiteIDParamName];
    $this->_setArgs($args);
	
	$checkFunctions = array('authenticate', 'checkTestSuiteID');
    $statusOk = $this->_runChecks($checkFunctions) && $this->userHasRight("mgt_view_tc");
    $ret = $statusOk ? $this->uploadAttachment($args,$msg_prefix,false) : $this->errors;
	return $ret;
}

/**
 * Uploads an attachment for a Test Case.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["testcaseid"] Test Case INTERNAL ID
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the erros map.
 */
public function uploadTestCaseAttachment($args)
{
	$ret = null;
	$msg_prefix = "(" .__FUNCTION__ . ") - ";
	
	$args[self::$foreignKeyTableNameParamName] = 'nodes_hierarchy';
	$args[self::$foreignKeyIdParamName] = $args[self::$testCaseIDParamName];
    $this->_setArgs($args);
	$checkFunctions = array('authenticate', 'checkTestCaseID');

    $statusOk = $this->_runChecks($checkFunctions,$msg_prefix) && $this->userHasRight("mgt_view_tc");
    $ret = $statusOk ? $this->uploadAttachment($args,$msg_prefix,false) : $this->errors;
	return $ret;
}

/**
 * Uploads an attachment for an execution.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["executionid"] execution ID
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the erros map.
 */
public function uploadExecutionAttachment($args)
{
	$msg_prefix = "(" .__FUNCTION__ . ") - ";
	$args[self::$foreignKeyTableNameParamName] = 'executions';
	$args[self::$foreignKeyIdParamName] = $args['executionid'];
    $this->_setArgs($args);
	return $this->uploadAttachment($args,$msg_prefix,false);
}

/**
 * Uploads an attachment for specified table. You must specify the table that 
 * the attachment is connected (nodes_hierarchy, builds, etc) and the foreign 
 * key id in this table.
 * 
 * The attachment content must be Base64 encoded by the client before sending it.
 * 
 * @param struct $args
 * @param string $args["devKey"] Developer key
 * @param int $args["fkid"] The Attachment Foreign Key ID
 * @param string $args["fktable"] The Attachment Foreign Key Table
 * @param string $args["title"] (Optional) The title of the Attachment 
 * @param string $args["description"] (Optional) The description of the Attachment
 * @param string $args["filename"] The file name of the Attachment (e.g.:notes.txt)
 * @param string $args["filetype"] The file type of the Attachment (e.g.: text/plain)
 * @param string $args["content"] The content (Base64 encoded) of the Attachment
 * 
 * @since 1.9beta6
 * @return mixed $resultInfo an array containing the fk_id, fk_table, title, 
 * description, file_name, file_size and file_type. If any errors occur it 
 * returns the erros map.
 */
public function uploadAttachment($args, $messagePrefix='', $setArgs=true)
{
	$resultInfo = array();
	if( $setArgs )
	{
		$this->_setArgs($args);
	}
	$msg_prefix = ($messagePrefix == '') ? ("(" .__FUNCTION__ . ") - ") : $messagePrefix;
	
	$checkFunctions = array();
	
	// TODO: please, somebody review if this is valid. I added this property 
	// to avoid the upload method of double authenticating the user. 
	// Otherwise, when uploadTestCaseAttachment was called, for instante, it 
	// would authenticate, check if the nodes_hierarchy is type TestCase 
	// and then call uploadAttachment that would, authenticate again.
	// What do you think?
	if( !$this->authenticated ) 
	{
		$checkFunctions[] = 'authenticate'; 
	}
	// check if :
	// TL has attachments enabled
	// provided FK is valid
	// attachment info is ok
	$checkFunctions[] = 'isAttachmentEnabled'; 
	$checkFunctions[] = 'checkForeignKey';
	$checkFunctions[] = 'checkUploadAttachmentRequest';

    $statusOk = $this->_runChecks($checkFunctions,$msg_prefix); // && $this->userHasRight("mgt_view_tc");

	if($statusOk)
	{		
		$fkId = $this->args[self::$foreignKeyIdParamName];
	    $fkTable = $this->args[self::$foreignKeyTableNameParamName];
	    $title = $this->args[self::$titleParamName];

		// return array($fkId,$fkTable,$title);	    
	    // creates a temp file and returns an array with size and tmp_name
	    $fInfo = $this->createAttachmentTempFile();
	    if ( !$fInfo )
	    {
			// Error creating attachment temp file. Ask user to check temp dir 
			// settings in php.ini and security and rights of this dir.
	    	$msg = $msg_prefix . ATTACH_TEMP_FILE_CREATION_ERROR_STR;
	    	$this->errors[] = new IXR_ERROR(ATTACH_TEMP_FILE_CREATION_ERROR,$msg); 
	    	$statusOk = false;
	    } 
	    else 
	    {
	    	// The values have already been validated in the method 
	    	// checkUploadAttachmentRequest()
	    	$fInfo['name'] = $args[self::$fileNameParamName];
	    	$fInfo['type'] = $args[self::$fileTypeParamName];
	    	
			$attachmentRepository = tlAttachmentRepository::create($this->dbObj);
			$uploadedFile = $attachmentRepository->insertAttachment($fkId,$fkTable,$title,$fInfo);
			if( !$uploadedFile )
			{
	    		$msg = $msg_prefix . ATTACH_DB_WRITE_ERROR_STR;
	    		$this->errors[] = new IXR_ERROR(ATTACH_DB_WRITE_ERROR,$msg); 
	    		$statusOk = false; 
			} 
			else 
			{
				// We are returning some data that the user originally sent. 
				// Perhaps we could return only new data, like the file size?
				$resultInfo['fk_id'] = $args[self::$foreignKeyIdParamName];
				$resultInfo['fk_table'] = $args[self::$foreignKeyTableNameParamName];
				$resultInfo['title'] = $args[self::$titleParamName];
				$resultInfo['description'] = $args[self::$descriptionParamName];
				$resultInfo['file_name'] = $args[self::$fileNameParamName];

				// It would be nice have all info available in db
				// $resultInfo['file_path'] = $args[""]; 
				// we could also return the tmp_name, but would it be useful?
 				$resultInfo['file_size'] = $fInfo['size'];
 				$resultInfo['file_type'] = $args[self::$fileTypeParamName];
			}
	    }
	}
  	
	return $statusOk ? $resultInfo : $this->errors;
}

/**
 * <p>Checks if the attachments feature is enabled in TestLink 
 * configuration.</p>
 * 
 * @since 1.9beta6
 * @return boolean true if attachments feature is enabled in TestLink 
 * configuration, false otherwise.
 */
protected function isAttachmentEnabled($msg_prefix='')
{
	$status_ok = true;
	if (!config_get("attachments")->enabled) 
	{
	    $msg = $msg_prefix . ATTACH_FEATURE_DISABLED_STR;
	    $this->errors[] = new IXR_ERROR(ATTACH_FEATURE_DISABLED,$msg); 
		$status_ok = false;
	}
	return $status_ok;
}

/**
 * <p>Checks if the given foreign key is valid. What this method basically does 
 * is query the database looking for the foreign key id in the foreign key 
 * table.</p>
 * 
 * @since 1.9beta6
 * @return boolean true if the given foreign key exists, false otherwise.
 */
protected function checkForeignKey($msg_prefix='')
{
	$statusOk = true;
	
	$fkId = $this->args[self::$foreignKeyIdParamName];
    $fkTable = $this->args[self::$foreignKeyTableNameParamName];
    
	if ( isset($fkId) && isset($fkTable) )
	{
		$query = "SELECT id FROM {$this->tables[$fkTable]} WHERE id={$fkId}";
		$result = $this->dbObj->fetchFirstRowSingleColumn($query, "id");
	}
       	
    if(null == $result)
    {
    	$msg = $msg_prefix . sprintf(ATTACH_INVALID_FK_STR, $fkId, $fkTable);
	    $this->errors[] = new IXR_ERROR(ATTACH_INVALID_FK,$msg);
        $statusOk = false;     		
	}
	
	return $statusOk;
}

/**
 * <p>Checks if the attachment parameters are valid. It checks if the 
 * <b>file_name</b> parameter is set, if the <b>content</b> is set and if 
 * the <b>file type</b> is set. If the <b>file type</b> is not set, then it uses 
 * <b>application/octet-stream</b>. 
 * This default content type refers to <i>binary</i> files.</p> 
 * 
 * @since 1.9beta6
 * @return boolean true if the file name and the content are set
 */
protected function checkUploadAttachmentRequest($msg_prefix = '')
{
	// Did the client set file name?
	$status = isset($this->args[self::$fileNameParamName]);
	if ( $status )
	{
		// Did the client set file content? 
		$status = isset($this->args[self::$contentParamName]);
		if ( $status )
		{
			// Did the client set the file type? If not so use binary as default file type
			if ( isset($this->args[self::$fileTypeParamName]) )
			{
				// By default, if no file type is provided, put it as binary
				$this->args[self::$fileTypeParamName] = "application/octet-stream";
			}
		}
	}

	if(!$status) 
	{
		$msg = $msg_prefix . sprintf(ATTACH_INVALID_ATTACHMENT_STR, $this->args[self::$fileNameParamName], 
									 sizeof($this->args[self::$contentParamName]));
	    $this->errors[] = new IXR_ERROR(ATTACH_INVALID_ATTACHMENT,$msg);
	}
	
	return $status;
}

/**
 * <p>Creates a temporary file and writes the attachment content into this file.</p>
 * 
 * <p>Before writing to the file it <b>Base64 decodes</b> the file content.</p>
 * 
 * @since 1.9beta6
 * @return file handler
 */
protected function createAttachmentTempFile()
{
	$resultInfo = array();
	$filename = tempnam(sys_get_temp_dir(), 'tl-');
	
	$resultInfo["tmp_name"] = $filename;
	$handle = fopen( $filename, "w" );
	fwrite($handle, base64_decode($this->args[self::$contentParamName]));
	fclose( $handle );
	
	$filesize = filesize($filename);
	$resultInfo["size"] = $filesize;
	
    return $resultInfo;
}



	/**
	 * checks if a test case version number is defined for a test case
	 *
	 * @param string $messagePrefix used to be prepended to error message
	 * 
	 * @return map with following keys
	 *             boolean map['status_ok']
	 *             string map['error_msg']
	 *             int map['error_code']
	 */
	protected function checkTestCaseVersionNumberAncestry($messagePrefix='')
	{
	    $ret=array('status_ok' => true, 'error_msg' => '' , 'error_code' => 0);
	
	    $tcase_id = $this->args[self::$testCaseIDParamName];
	    $version_number = $this->args[self::$versionNumberParamName];
	    
	    $sql = " SELECT TCV.version,TCV.id " . 
	           " FROM {$this->tables['nodes_hierarchy']} NH, {$this->tables['tcversions']} TCV " .
	           " WHERE NH.parent_id = {$tcase_id} " .
	           " AND TCV.version = {$version_number} " .
	           " AND TCV.id = NH.id ";
	
	    $target_tcversion = $this->dbObj->fetchRowsIntoMap($sql,'version');
	    // $xx = "tcase_id:$tcase_id - version_number:$version_number";
	    // file_put_contents('c:\checkTestCaseVersionNumberAncestry.php.xmlrpc', $xx);                            
	    
	    if( !is_null($target_tcversion) && count($target_tcversion) == 1 )
	    {
	    	$dummy = current($target_tcversion);
			$this->tcVersionID = $dummy['id'];
	    }
	    else
	    {
			$status_ok=false;
            $tcase_info = $this->tcaseMgr->tree_manager->get_node_hierarchy_info($tcase_id);
			$msg = sprintf(TCASE_VERSION_NUMBER_KO_STR,$version_number,$this->args[self::$testCaseExternalIDParamName],
						   $tcase_info['name']);  
			$ret = array('status_ok' => false, 'error_msg' => $msg , 'error_code' => TCASE_VERSION_NUMBER_KO);                                               
	    }  
	                    
	    // $xx = "this->tcVersionID:$this->tcVersionID";
	    // file_put_contents('c:\checkTestCaseVersionNumberAncestry.php.xmlrpc', $xx,FILE_APPEND); 
	    return $ret;
	} // function end

} // class end
?>