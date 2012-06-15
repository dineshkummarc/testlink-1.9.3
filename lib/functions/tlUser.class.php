<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later.
 * 
 * @filesource	tlUser.class.php
 * @package 	TestLink
 * @copyright 	2007-2011, TestLink community 
 * @link 		http://www.teamst.org/index.php
 *
 * @internal revisions:
 *	
 *  20110325 - franciscom - BUGID 4062 - hasRight() caused by bad access to $_SESSION
 *	20101111 - franciscom - BUGID 4006 - test plan is_public
 *	20100917 - Julian - getAccessibleTestPlans() - BUGID 3724 - new option "active"
 *	20100704 - franciscom - getAccessibleTestPlans() - BUGID 3526
 *	20100522 - franciscom - getAccessibleTestPlans() - added arguments options
 *	20100427 - franciscom - BUGID 3396 - writePasswordToDB() method
 *	20100326 - franciscom - setActive() method
 *	20100217 - franciscom - getNamesForProjectRight() - fixed error displayed on event viewer + refactoring
 *  20090726 - franciscom - new method getAccessibleTestPlans()
 * 	20090419 - franciscom - refactoring replace product with test project (where possible).
 *  20090101 - franciscom - changes to deleteFromDB() due to Foreing Key constraints
 *  20081213 - franciscom - removed global coupling to access config parameters
 */
 
/**
 * Class for handling users in TestLink
 * 
 * @package TestLink
 * @author 	Andreas Morsing
 * @uses 	config.inc.php
 * @since 	1.7
 */ 
class tlUser extends tlDBObject
{
	/**
	 * @var the name of the table the object is stored into
	 * @access private
	 */
	private $object_table = "users";
    
	/**
	 * @var string the first name of the user
	 */
	public $firstName;

	/**
	 * @var string the last name of the user
	 */
	public $lastName;

	/**
	 * @var string the email address of the user
	 */
	public $emailAddress;

	/**
	 * @var string the locale of the user (eG de_DE, en_GB)
	 */
	public $locale;

	/**
	 * @var boolean true if the user is active, false else
	 */
	public $isActive;

	/**
	 * @var integer the default testprojectID of the user
	 */
	public $defaultTestprojectID;

	/**
	 * @var tlRole the global role of the user
	 */
	public $globalRole;

	/**
	 * @var integer the id of global role of the user
	 */
	public $globalRoleID;

	/**
	 * @var array of tlRole, holds the roles of the user for the different testprojects 
	 */
	public $tprojectRoles; 

	/**
	 * @var array of tlRole, holds the roles of the user for the different testplans 
	 */
	public $tplanRoles;

	/**
	 * @var string the login of the user
	 */
	public $login;

	/**
	 * @var string the API Key for the user
	 */
	public $userApiKey;

	/**
	 * @var string the password of the user
	 * @access protected
	 */
	protected $password;
	
	/** configuration options */
	//@TODO should be moved inside a tlConfig class
	protected $usernameFormat;
	protected $loginMethod;
	protected $maxLoginLength;
	
	/** error codes */
	const E_LOGINLENGTH = -1;
	const E_EMAILLENGTH = -2;
	const E_NOTALLOWED = -4;
	const E_DBERROR = -8;
	const E_FIRSTNAMELENGTH = -16;
	const E_LASTNAMELENGTH = -32;
	const E_PWDEMPTY = -64;
	const E_PWDDONTMATCH = -128;
	const E_LOGINALREADYEXISTS = -256;
	const E_EMAILFORMAT = -512;
	const S_PWDMGTEXTERNAL = 2;
	
	//search options
	const USER_O_SEARCH_BYLOGIN = 2;
	
	
	//detail leveles
	const TLOBJ_O_GET_DETAIL_ROLES = 1;

    const SKIP_CHECK_AT_TESTPROJECT_LEVEL = -1;
    const SKIP_CHECK_AT_TESTPLAN_LEVEL = -1;

	
	/**
	 * Constructor, creates the user object
	 * 
	 * @param resource $db database handler
	 */
	function __construct($dbID = null)
	{
		parent::__construct($dbID);

		$this->object_table = $this->tables['users']; 
		
		$authCfg = config_get('authentication');
		$this->usernameFormat = config_get('username_format');
		$this->loginRegExp = config_get('validation_cfg')->user_login_valid_regex;
		$this->maxLoginLength = 30; 
		$this->loginMethod = $authCfg['method'];
		
		$this->globalRoleID = config_get('default_roleid');
		$this->locale = config_get('default_language');
		$this->isActive = 1;
		$this->tprojectRoles = null;
		$this->tplanRoles = null;
	}
	
	/** 
	 * Cleans the object by resetting the members to default values
	 * 
	 * @param mixed $options tlUser/tlObject options
	 */
	protected function _clean($options = self::TLOBJ_O_SEARCH_BY_ID)
	{
		$this->firstName = null;
		$this->lastName = null;
		$this->emailAddress = null;
		$this->locale = null;
		$this->password = null;
		$this->isActive = null;
		$this->defaultTestprojectID = null;
		$this->globalRoleID = null;
		$this->tprojectRoles = null;
		$this->tplanRoles = null;
		$this->userApiKey = null;

		if (!($options & self::TLOBJ_O_SEARCH_BY_ID))
		{
			$this->dbID = null;
		}

		if (!($options & self::USER_O_SEARCH_BYLOGIN))
		{
			$this->login = null;
		}
	}
	
	/** 
	 * Checks if password management is external (like LDAP)...
	 * 
	 * @return boolean return true if password management is external, else false
	 * @TODO schlundus, should be moved inside a super tl configuration class
	 */
	static public function isPasswordMgtExternal()
	{
		$authCfg = config_get('authentication');
		return ($authCfg['method'] != '' &&  $authCfg['method'] != 'MD5') ? true : false;
	}
	
	/**
	 *  Obtain a secure password. 
	 *  You can choose the number of alphanumeric characters to add and 
	 *  the number of non-alphanumeric characters. 
	 *  You can add another characters to the non-alphanumeric list if you need.
	 *           
	 * 	@param integer $numAlpha number alphanumeric characters in generated password
	 *  @param integer $numNonAlpha number special characters in generated password
	 * 
	 * 	@return string the generated password
	*/
	static public function generatePassword($numAlpha = 6,$numNonAlpha = 2)
	{
	  $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	  $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';
	  
	  return str_shuffle(substr(str_shuffle($listAlpha),0,$numAlpha) .
	                      substr(str_shuffle($listNonAlpha),0,$numNonAlpha));
	}
	
	/** 
	 * not used at the moment, only placeholder
	 * 
	 * @return void
	 * @TODO implement  
	 **/
	function create()
	{
	}
	
	//----- BEGIN interface iDBSerialization -----
	/** 
	 * Reads an user object identified by its database id from the given database
	 * 
	 * @param resource &$db reference to database handler
	 * @param mixed $options (optional) tlUser/tlObject options
	 * 
	 * @return integer tl::OK if the object could be read from the db, else tl::ERROR
	 */
	public function readFromDB(&$db,$options = self::TLOBJ_O_SEARCH_BY_ID)
	{
		$this->_clean($options);
		$sql = "SELECT id,login,password,first,last,email,role_id,locale, " .
		         " login AS fullname, active,default_testproject_id, script_key " .
		         " FROM {$this->object_table}";
		$clauses = null;

		if ($options & self::TLOBJ_O_SEARCH_BY_ID)
		{
			$clauses[] = "id = {$this->dbID}";		
		}
		if ($options & self::USER_O_SEARCH_BYLOGIN)
		{
			$clauses[] = "login = '".$db->prepare_string($this->login)."'";		
		}
		if ($clauses)
		{
			$sql .= " WHERE " . implode(" AND ",$clauses);
		}
		$info = $db->fetchFirstRow($sql);	
		if ($info)
		{
			$this->dbID = $info['id'];
			$this->firstName = $info['first'];
			$this->lastName = $info['last'];
			$this->login = $info['login'];
			$this->emailAddress = $info['email'];
			$this->globalRoleID = $info['role_id'];
			$this->userApiKey = $info['script_key'];
			
			if ($this->globalRoleID)
			{
				$this->globalRole = new tlRole($this->globalRoleID);
				$this->globalRole->readFromDB($db);
			}
			if ($this->detailLevel & self::TLOBJ_O_GET_DETAIL_ROLES)
			{
				$this->readTestProjectRoles($db);
				$this->readTestPlanRoles($db);
			}
			
			$this->locale = $info['locale'];
			$this->password = $info['password'];
			$this->isActive = $info['active'];
			$this->defaultTestprojectID = $info['default_testproject_id'];
		}
		return $info ? tl::OK : tl::ERROR;
	}
	
	/**
	 * Fetches all the testproject roles of of the user, and store them into the object. 
	 * Result could be limited to a certain testproject
	 * 
	 * @param resource &$db reference to database handler
	 * @param integer $testProjectID Identifier of the testproject to read the roles for, 
	 * 		if null all roles are read
	 * 
	 * @return integer returns tl::OK 
	 */
	public function readTestProjectRoles(&$db,$testProjectID = null)
	{
		$sql = "SELECT testproject_id,role_id " .
		         " FROM {$this->tables['user_testproject_roles']} user_testproject_roles " .
		         " WHERE user_id = {$this->dbID}";
		if ($testProjectID)
		{
			$sql .= " AND testproject_id = {$testProjectID}";
		}
		$allRoles = $db->fetchColumnsIntoMap($sql,'testproject_id','role_id');
		$this->tprojectRoles = null;
		if (sizeof($allRoles))
		{
			$roleCache = null;
			foreach($allRoles as $tprojectID => $roleID)
			{
				if (!isset($roleCache[$roleID]))
				{
					$tprojectRole = tlRole::createObjectFromDB($db,$roleID,"tlRole",true);
					$roleCache[$roleID] = $tprojectRole;
				}
				else
				{
					$tprojectRole = clone($roleCache[$roleID]);
				}
				if ($tprojectRole)
				{
					$this->tprojectRoles[$tprojectID] = $tprojectRole;
				}	
			}
		}
		return tl::OK;
	}
	
	/**
	 * Fetches all the testplan roles of of the user, and store them into the object. 
	 * Result could be limited to a certain testplan
	 * 
	 * @param resource &$db reference to database handler
	 * @param integer $testPlanID Identifier of the testplan to read the roles for, if null all roles are read
	 * 
	 * @return integer returns tl::OK 
	 */
	public function readTestPlanRoles(&$db,$testPlanID = null)
	{
		$sql = "SELECT testplan_id,role_id " . 
		         " FROM {$this->tables['user_testplan_roles']} user_testplan_roles " .
		         " WHERE user_id = {$this->dbID}";
		if ($testPlanID)
		{
			$sql .= " AND testplan_id = {$testPlanID}";
        }
        
		$allRoles = $db->fetchColumnsIntoMap($sql,'testplan_id','role_id');
		$this->tplanRoles = null;
		if (sizeof($allRoles))
		{
			$roleCache = null;
			foreach($allRoles as $tplanID => $roleID)
			{
				if (!isset($roleCache[$roleID]))
				{
					$tplanRole = tlRole::createObjectFromDB($db,$roleID,"tlRole",true);
					$roleCache[$roleID] = $tplanRole;
				}
				else
				{
					$tplanRole = clone($roleCache[$roleID]);
				}
				if ($tplanRole)
				{
					$this->tplanRoles[$tplanID] = $tplanRole;
				}	
			}
		}
		return tl::OK;
	}
	
	/** 
	 * Writes the object into the database
	 * 
	 * @param resource &$db reference to database handler
	 * @return integer tl::OK if the object could be written to the db, else error code
	 */
	public function writeToDB(&$db)
	{
		$result = $this->checkDetails($db);
		if ($result >= tl::OK)
		{		
			if($this->dbID)
			{
				$sql = "UPDATE {$this->tables['users']} " .
			       	   "SET first = '" . $db->prepare_string($this->firstName) . "'" .
			       	   ", last = '" .  $db->prepare_string($this->lastName)    . "'" .
			       	   ", email = '" . $db->prepare_string($this->emailAddress)   . "'" .
				   	   ", locale = ". "'" . $db->prepare_string($this->locale) . "'" . 
				   	   ", password = ". "'" . $db->prepare_string($this->password) . "'" .
				   	   ", role_id = ". $db->prepare_string($this->globalRoleID) . 
				   	   ", active = ". $db->prepare_string($this->isActive);
				$sql .= " WHERE id = " . $this->dbID;
				$result = $db->exec_query($sql);
			}
			else
			{
				$sql = "INSERT INTO {$this->tables['users']} (login,password,first,last,email,role_id,locale,active) 
					   VALUES ('" . 
					   $db->prepare_string($this->login) . "','" . $db->prepare_string($this->password) . "','" . 
					   $db->prepare_string($this->firstName) . "','" . $db->prepare_string($this->lastName) . "','" . 
					   $db->prepare_string($this->emailAddress) . "'," . $this->globalRoleID. ",'". 
					   $db->prepare_string($this->locale). "'," . $this->isActive . ")";
				$result = $db->exec_query($sql);
				if($result)
				{
					$this->dbID = $db->insert_id($this->tables['users']);
				}	
			}
			$result = $result ? tl::OK : self::E_DBERROR;
		}
		return $result;
	}	

	/** 
	 * WARNING: DO NOT USE THE FUNCTION - CAUSES DB INCONSISTENCE! 
	 *  
	 * @deprecated 1.8.3 
	 * @see #2407 
	 **/	
	public function deleteFromDB(&$db)
	{
		$sqlSet = array();
		$sqlSet[] = "DELETE FROM {$this->table['user_assignments']} WHERE user_id = {$this->dbID}";
		$sqlSet[] = "DELETE FROM {$this->table['users']}  WHERE id = {$this->dbID}";

	    foreach($sqlSet as $sql)
	    {
			$result = $db->exec_query($sql) ? tl::OK : tl::ERROR;
		    if($result == tl::ERROR) 
		    {
		        break;  
		    }
		}
	
		if ($result == tl::OK)
		{
			$result = $this->deleteTestProjectRoles($db);
		}
		return $result;
	}

	/**
	 * Deletes all testproject related role assignments for a given user
	 *
	 * @param resource &$db reference to database handler
	 * @param integer $userID the user ID
	 * 
	 * @return integer tl::OK on success, tl:ERROR else
	 **/
	protected function deleteTestProjectRoles(&$db)
	{
		$sql = "DELETE FROM {$this->tables['user_testproject_roles']} WHERE user_id = {$this->dbID}";
	
		return $db->exec_query($sql) ? tl::OK : tl::ERROR;
	}

	/** 
	 * Returns a user friendly representation of the user name
	 * 
	 * @return string the display nmae
	 */
	public function getDisplayName()
	{
		$keys = array('%first%','%last%','%login%','%email%');
		$values = array($this->firstName, $this->lastName,$this->login,$this->emailAddress);
		$displayName = trim(str_replace($keys,$values,$this->usernameFormat));

		return $displayName;
	}
	
	/**
	 * Encrypts a given password with MD5
	 * 
	 * @param $pwd the password to encrypt
	 * @return string the encrypted password
	 */
	protected function encryptPassword($pwd)
	{
		if (self::isPasswordMgtExternal())
			return self::S_PWDMGTEXTERNAL;

		return md5($pwd);
	}
	
	/**
	 * Set encrypted password
	 * 
	 * @param string $pwd the new password
	 * @return integer return tl::OK is the password is stored, else errorcode
	 */
	public function setPassword($pwd)
	{
		if (self::isPasswordMgtExternal())
		{
			return self::S_PWDMGTEXTERNAL;
		}
		$pwd = trim($pwd);	
		if ($pwd == "")
		{
			return self::E_PWDEMPTY;
		}
		$this->password = $this->encryptPassword($pwd);
		return tl::OK;
	}
	
	/**
	 * Getter for the password of the user
	 * 
	 * @return string the password of the user
	 */
	public function getPassword()
	{
		return $this->password;
	}
	
	/**
	 * compares a given password with the current password of the user
	 * 
	 * @param string $pwd the password to compate with the password actually set 
	 * @return integer returns tl::OK if the password's match, else errorcode
	 */
	public function comparePassword($pwd)
	{
		if (self::isPasswordMgtExternal())
			return self::S_PWDMGTEXTERNAL;

		if ($this->getPassword($pwd) == $this->encryptPassword($pwd))
			return tl::OK;
		return self::E_PWDDONTMATCH;		
	}

	
	public function checkDetails(&$db)
	{
		$this->firstName = trim($this->firstName);
		$this->lastName = trim($this->lastName);
		$this->emailAddress = trim($this->emailAddress);
		$this->locale = trim($this->locale);
		$this->isActive = intval($this->isActive);
		$this->login = trim($this->login);
	
		$result = self::checkEmailAddress($this->emailAddress);
		if ($result >= tl::OK)
		{
			$result = $this->checkLogin($this->login);
		}
		if ($result >= tl::OK && !$this->dbID)
		{
			$result = self::doesUserExist($db,$this->login) ? self::E_LOGINALREADYEXISTS : tl::OK;
		}
		if ($result >= tl::OK)
		{
			$result = self::checkFirstName($this->firstName);
		}
		if ($result >= tl::OK)
		{
			$result = self::checkLastName($this->lastName);
		}
		return $result;
	}

	
	public function checkLogin($login)
	{
		$result = tl::OK;
		$login = trim($login);
		
		if ($login == "" || (tlStringLen($login) > $this->maxLoginLength))
			$result = self::E_LOGINLENGTH;
		else if (!preg_match($this->loginRegExp,$login)) //Only allow a basic set of characters
			$result = self::E_NOTALLOWED;

		return $result;
	}
	
	/**
	 * Returns the id of the effective role in the context of ($tproject_id,$tplan_id)
	 * 
	 * @param resource &$db reference to database handler
	 * @param integer $tproject_id the testproject id
	 * @param integer $tplan_id the plan id
	 * 
	 * @return integer tlRole the effective role
	 */
	function getEffectiveRole(&$db,$tproject_id,$tplan_id)
	{
		$tprojects_role = $this->tprojectRoles;
		$tplans_role = $this->tplanRoles;
		$effective_role = $this->globalRole;
		if(!is_null($tplans_role) && isset($tplans_role[$tplan_id]))
		{
			$effective_role = $tplans_role[$tplan_id];  
		}
		else if(!is_null($tprojects_role) && isset($tprojects_role[$tproject_id]))
		{
			$effective_role = $tprojects_role[$tproject_id];  
		}
		return $effective_role;
	}

	/**
	 * Gets all userids of users with a certain testplan role @TODO WRITE RIGHT COMMENTS FROM START
	 *
	 * @param resource &$db reference to database handler
	 * @return array returns array of userids
	 **/
	protected function getUserNamesWithTestPlanRole(&$db)
	{
		$sql = "SELECT DISTINCT id FROM {$this->tables['users']} users," . 
		         " {$this->tables['user_testplan_roles']} user_testplan_roles " .
		         " WHERE  users.id = user_testplan_roles.user_id";
		$sql .= " AND user_testplan_roles.role_id = {$this->dbID}";
		$idSet = $db->fetchColumnsIntoArray($sql,"id");
		
		return $idSet; 
	}


	/**
     * Get a list of names with a defined project right (for example for combo-box)
     * used by ajax script getUsersWithRight.php
     * 
     * @param integer $db DB Identifier
     * @param string $rightNick key corresponding with description in rights table
     * @param integer $testprojectID Identifier of project
     *
     * @return array list of user IDs and names
     * 
     * @todo fix the case that user has default role with a right but project role without
     * 		i.e. he should be listed
     */
	public function getNamesForProjectRight(&$db,$rightNick,$testprojectID = null)
	{
		$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
		if (is_null($testprojectID))
		{
			tLog( $debugMsg . ' requires Test Project ID defined','ERROR');
			return null;
		}
		
		$output = array();
		
		//get users for default roles
		$sql = "/* $debugMsg */ SELECT DISTINCT u.id,u.login,u.first,u.last FROM {$this->tables['users']} u" .
			   " JOIN {$this->tables['role_rights']} a ON a.role_id=u.role_id" .
			   " JOIN {$this->tables['rights']} b ON a.right_id = b.id " .
			   " WHERE b.description='{$rightNick}'";
		$defaultRoles = $db->fetchRowsIntoMap($sql,'id');

		// get users for project roles
		$sql = "/* $debugMsg */ SELECT DISTINCT u.id,u.login,u.first,u.last FROM {$this->tables['users']} u" .
			   " JOIN {$this->tables['user_testproject_roles']} p ON p.user_id=u.id" .
			   " AND p.testproject_id=" . $testprojectID .
			   " JOIN {$this->tables['role_rights']} a ON a.role_id=p.role_id" .
			   " JOIN {$this->tables['rights']} b ON a.right_id = b.id " .
			   " WHERE b.description='{$rightNick}'";
		$projectRoles = $db->fetchRowsIntoMap($sql,'id');
		
		// merge arrays		
		// the next function is available from php53 but we support php52
		// $output = array_replace($output1, $output2);
	    if( !is_null($projectRoles) )
	    {
	    	foreach($projectRoles as $k => $v) 
	    	{
       		    if( !isset($defaultRoles[$k]) ) 
       		    {
	    	   	    $defaultRoles[$k] = $v;
      		    }
	    	}
	    }

	    // format for ext-js combo-box (remove associated array)
	    // foreach($defaultRoles as $k => $v) 
	    // {
       	//     $output[] = $v;
	    // }
		$output = array_values($defaultRoles);   
		   
		return $output;
	}

	
	/**
     * Get a list of all names 
     * used for replacement user ID by user login
     * 
     * @param integer $db DB Identifier
     * @return array list of user IDs and names
     */
	public function getNames(&$db)
	{
		$sql = "SELECT id,login,first,last FROM {$this->tables['users']}";
		$output = $db->fetchRowsIntoMap($sql,'id');

		return $output;
	}


	/**
     * check right on effective role for user, using test project and test plan,
     * means that check right on effective role.
     *
     * @return string|null 'yes' or null
	 *
	 * @internal revisions
	 * 20110325 - franciscom - 	BUGID 4062 - caused by bad access to $_SESSION
	 *							dammed global coupling	
     */
	function hasRight(&$db,$roleQuestion,$tprojectID = null,$tplanID = null)
	{
		global $g_propRights_global;
		global $g_propRights_product;
		
		$userGlobalRights = (array)$this->globalRole->rights;
		$globalRights = array();
		foreach($userGlobalRights as $right)
		{
			$globalRights[] = $right->name;
		}
		
		if (!is_null($tplanID))
		{
			$testPlanID = $tplanID;
		}
		else
		{
			//@TODO schlundus, should not be there
			// 20090726 - franciscom -> yes must be moved ASAP
			$testPlanID = isset($_SESSION['testplanID']) ? $_SESSION['testplanID'] : 0;
		}
		
		
		$userTestPlanRoles = $this->tplanRoles;
		if (!is_null($tprojectID))
		{
			$testprojectID = $tprojectID;
		}
		else
		{
			//@TODO schlundus, should not be there
			$testprojectID = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
		}
		
		$allRights = $globalRights;
			
		$userTestProjectRoles = $this->tprojectRoles;
		/* if $testprojectID == -1 we dont check rights at test project level! */
		if (isset($userTestProjectRoles[$testprojectID]))
		{
			$userTestProjectRights = (array)$userTestProjectRoles[$testprojectID]->rights;
			$testProjectRights = array();
			foreach($userTestProjectRights as $right)
			{
				$testProjectRights[] = $right->name;
			}
		    

			//subtract global rights		
			$testProjectRights = array_diff($testProjectRights,array_keys($g_propRights_global));
			propagateRights($globalRights,$g_propRights_global,$testProjectRights);
			$allRights = $testProjectRights;
		}
		
		/* if $tplanID == -1 we dont check rights at tp level! */
		if (isset($userTestPlanRoles[$testPlanID]))
		{
			$userTestPlanRights = (array) $userTestPlanRoles[$testPlanID]->rights;
			$testPlanRights = array();
			foreach($userTestPlanRights as $right)
			{
				$testPlanRights[] = $right->name;
			}
			//subtract test projects rights		
			$testPlanRights = array_diff($testPlanRights,array_keys($g_propRights_product));
			
			propagateRights($allRights,$g_propRights_product,$testPlanRights);
			$allRights = $testPlanRights;
		}
		
		return checkForRights($allRights,$roleQuestion);
	}

	/**
     * get array with accessible test plans for user on a test project, 
     * analising user roles.
     *
     * @param resource $db database handler  
     * @param int testprojectID 
     * @param int testplanID: default null. 
     *            Used as filter when you want to check if this test plan
     *            is accessible.
     *
     * @param map options keys :
     * 							'output' => null -> get numeric array
     *									 => map => map indexed by testplan id
     *									 => combo => map indexed by testplan id and only returns name
     *							'active' => ACTIVE (get active test plans)
     *									 => INACTIVE (get inactive test plans)
     *									 => TP_ALL_STATUS (get all test plans)
     *
     * @return array if 0 accessible test plans => null
     *
     * @internal Revisions
     * 20101111 - franciscom - BUGID 4006 test plan is_public
     */
	function getAccessibleTestPlans(&$db,$testprojectID,$testplanID=null, $options=null)
	{
		$debugTag = 'Class:' .  __CLASS__ . '- Method:' . __FUNCTION__ . '-';
		
		$my['options'] = array( 'output' => null, 'active' => ACTIVE);
	    $my['options'] = array_merge($my['options'], (array)$options);
		
		$fields2get = ' NH.id, NH.name, TPLAN.is_public, COALESCE(USER_TPLAN_ROLES.testplan_id,0) AS has_role';
		if( $my['options']['output'] != 'combo' )
		{
			$fields2get .= ' ,TPLAN.active, 0 AS selected ';
		}
		$sql = " /* $debugTag */  SELECT {$fields2get} " .
		       " FROM {$this->tables['nodes_hierarchy']} NH" .
		       " JOIN {$this->tables['testplans']} TPLAN ON NH.id=TPLAN.id  " .
		       " LEFT OUTER JOIN {$this->tables['user_testplan_roles']} USER_TPLAN_ROLES" .
		       " ON TPLAN.id = USER_TPLAN_ROLES.testplan_id " .
		       " AND USER_TPLAN_ROLES.user_id = $this->dbID WHERE " .
		       " testproject_id = {$testprojectID} AND ";
		
		if (!is_null($my['options']['active'])) {
			$sql .= " active = {$my['options']['active']} AND ";
		}
	
	  	if (!is_null($testplanID))
	  	{
			$sql .= " NH.id = {$testplanID} AND ";
	  	}
		
		$globalNoRights = ($this->globalRoleID == TL_ROLES_NO_RIGHTS);
		$projectNoRights = 0;
		$analyseGlobalRole = 1;
		
		// 20100704 - franciscom
		// BUGID 3526
		// 
		// If user has a role for $testprojectID, then we DO NOT HAVE to check for globalRole
		// if( ($analyseGlobalRole = isset($this->tprojectRoles[$testprojectID]->dbID)) )
		// {
		// 	$projectNoRights = ($this->tprojectRoles[$testprojectID]->dbID == TL_ROLES_NO_RIGHTS); 
		// }
		// Looking to the code on 1.8.5, seems this has been introduced on some refactoring
		if( isset($this->tprojectRoles[$testprojectID]->dbID) )
		{
			$analyseGlobalRole = 0;
			$projectNoRights = ($this->tprojectRoles[$testprojectID]->dbID == TL_ROLES_NO_RIGHTS); 
		}
		
		// User can have NO RIGHT on test project under analisys ($testprojectID), in this situation he/she 
		// has to have a role at Test Plan level in order to access one or more test plans 
		// that belong to $testprojectID.
		//
		// Other situation: he/she has been created with role without rights ($globalNoRights)
		//
	  	if( $projectNoRights || ($analyseGlobalRole && $globalNoRights))
	  	{
	  	  $sql .= "(role_id IS NOT NULL AND role_id != ".TL_ROLES_NO_RIGHTS.")";
	  	}	
	  	else
	  	{
	  		// in this situation, do we are hineriting role from testprojectID ?	
	  	  	$sql .= "(role_id IS NULL OR role_id != ".TL_ROLES_NO_RIGHTS.")";
	  	}
			
		$sql .= " ORDER BY name";
		$numericIndex = false;
		switch($my['options']['output'])
		{
			case 'map':
				$testPlanSet = $db->fetchRowsIntoMap($sql,'id');
			break;
			
			case 'combo':
				$testPlanSet = $db->fetchColumnsIntoMap($sql,'id','name');
			break;
			
			default:
				$testPlanSet = $db->get_recordset($sql);
				$numericIndex = true;
			break;
		}

		// BUGID 4006 - test plan is_public
		// Admin exception		
		if( $this->globalRoleID != TL_ROLES_ADMIN && count($testPlanSet) > 0 )
		{
			$doReindex = false;
			foreach($testPlanSet as $idx => $item)
			{
				if( $item['is_public'] == 0 && $item['has_role'] == 0 )
				{
					unset($testPlanSet[$idx]);
					$doReindex = true;
				} 				
			}
			if( $doReindex && $numericIndex)
			{
				$testPlanSet = array_values($testPlanSet);
			}
		} 
		
		return $testPlanSet;
	}


	/**
	 * Checks the correctness of an email address
	 * 
	 * @param string $email
	 * @return integer returns tl::OK on success, errorcode else
	 */
	static public function checkEmailAddress($email)
	{
		$result = is_blank($email) ? self::E_EMAILLENGTH : tl::OK;
		if ($result == tl::OK)
		{
	    	$matches = array();
	    	$email_regex = config_get('validation_cfg')->user_email_valid_regex_php;
			if (!preg_match($email_regex,$email,$matches))
			{
				$result = self::E_EMAILFORMAT;
			}	
		}
		return $result;
	}
	
	static public function checkFirstName($first)
	{
		return is_blank($first) ? self::E_FIRSTNAMELENGTH : tl::OK;
	}
	
	static public function checkLastName($last)
	{
		return is_blank($last) ? self::E_LASTNAMELENGTH : tl::OK;
	}
	
	static public function doesUserExist(&$db,$login)
	{
		$user = new tlUser();
		$user->login = $login;
		if ($user->readFromDB($db,self::USER_O_SEARCH_BYLOGIN) >= tl::OK)
		{
			return $user->dbID;
		}
		return null;
	}
	
	static public function getByID(&$db,$id,$detailLevel = self::TLOBJ_O_GET_DETAIL_FULL)
	{
		return tlDBObject::createObjectFromDB($db,$id,__CLASS__,self::TLOBJ_O_SEARCH_BY_ID,$detailLevel);
	}
	

	static public function getByIDs(&$db,$ids,$detailLevel = self::TLOBJ_O_GET_DETAIL_FULL)
	{
		$users = null;
		for($i = 0;$i < sizeof($ids);$i++)
		{
			$id = $ids[$i];
			$user = tlDBObject::createObjectFromDB($db,$id,__CLASS__,self::TLOBJ_O_SEARCH_BY_ID,$detailLevel);
			if ($user)
				$users[$id] = $user;
		}
		return $users ? $users : null;
	}

	static public function getAll(&$db,$whereClause = null,$column = null,$orderBy = null,
	                              $detailLevel = self::TLOBJ_O_GET_DETAIL_FULL)
	{
		$tables = tlObject::getDBTables('users');
		$sql = " SELECT id FROM {$tables['users']} ";
		if (!is_null($whereClause))
		{
			$sql .= ' '.$whereClause;
	    }
		$sql .= is_null($orderBy) ? " ORDER BY login " : $orderBy;
		
		return tlDBObject::createObjectsFromDBbySQL($db,$sql,'id',__CLASS__,true,$detailLevel);
	}

	/** 
	 */
	public function setActive(&$db,$value)
	{
		$booleanVal = intval($value) > 0 ? 1 : 0;
		$sql = " UPDATE {$this->tables['users']} SET active = {$booleanVal} " .
			   " WHERE id = " . $this->dbID;
		$result = $db->exec_query($sql);
	}


	/** 
	 * Writes user password into the database
	 * 
	 * @param resource &$db reference to database handler
	 * @return integer tl::OK if no problem written to the db, else error code
	 */
	public function writePasswordToDB(&$db)
	{
		if($this->dbID)
		{
			$sql = "UPDATE {$this->tables['users']} " .
		       	   " SET password = ". "'" . $db->prepare_string($this->password) . "'";
			$sql .= " WHERE id = " . $this->dbID;
			$result = $db->exec_query($sql);
		}
		$result = $result ? tl::OK : self::E_DBERROR;
		return $result;
	}	

}
?>