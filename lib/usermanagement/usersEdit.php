<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * Allows editing a user
 *
 * @package 	TestLink
 * @copyright 	2005-2010, TestLink community
 * @version    	CVS: $Id: usersEdit.php,v 1.41.2.2 2011/01/10 15:38:59 asimon83 Exp $
 * @link 		http://www.teamst.org/index.php
 *
 * @internal Revisions:
 *	20101010 - franciscom - BUGID 3872: Admin should be able to set a new password for users
 *	20100502 - franciscom - BUGID 3417
 *
 */
require_once('../../config.inc.php');
require_once('testproject.class.php');
require_once('users.inc.php');
require_once('email_api.php');
require_once('Zend/Validate/Hostname.php');

testlinkInitPage($db,false,false,"checkRights");

$templateCfg = templateConfiguration();
$args = init_args();

$passwordSendMethod = config_get('password_reset_send_method');

$op = new stdClass();
$op->user_feedback = '';
$highlight = initialize_tabsmenu();

$actionOperation = array('create' => 'doCreate', 'edit' => 'doUpdate',
                       'doCreate' => 'doCreate', 'doUpdate' => 'doUpdate',
                       'resetPassword' => 'doUpdate');

switch($args->doAction)
{
	case "edit":
		$highlight->edit_user = 1;
		$user = new tlUser($args->user_id);
		$user->readFromDB($db);
		break;
	
	case "doCreate":
		$highlight->create_user = 1;
		$op = doCreate($db,$args);
		$user = $op->user;
		$templateCfg->template = $op->template;
		break;
	
	case "doUpdate":
		$highlight->edit_user = 1;
		$sessionUserID = $_SESSION['currentUser']->dbID;
		$op = doUpdate($db,$args,$sessionUserID);
		$user = $op->user;
		break;

	case "resetPassword":
		$highlight->edit_user = 1;
		$user = new tlUser($args->user_id);
		$user->readFromDB($db);
		$op = createNewPassword($db,$args,$user,$passwordSendMethod);
		break;
	
	case "create":
	default:
		$highlight->create_user = 1;
		$user = new tlUser();
		break;
}

$op->operation = $actionOperation[$args->doAction];
$roles = tlRole::getAll($db,null,null,null,tlRole::TLOBJ_O_GET_DETAIL_MINIMUM);
unset($roles[TL_ROLES_UNDEFINED]);

$smarty = new TLSmarty();
$smarty->assign('highlight',$highlight);
$smarty->assign('operation',$op->operation);
$smarty->assign('user_feedback',$op->user_feedback);
$smarty->assign('external_password_mgmt', tlUser::isPasswordMgtExternal());
$smarty->assign('mgt_view_events',$_SESSION['currentUser']->hasRight($db,"mgt_view_events"));
$smarty->assign('grants',getGrantsForUserMgmt($db,$_SESSION['currentUser']));
$smarty->assign('optRights',$roles);
$smarty->assign('userData', $user);
renderGui($smarty,$args,$templateCfg);


/**
 * 
 *
 */
function init_args()
{
	$iParams = array(
			"delete" => array(tlInputParameter::INT_N),
			"user" => array(tlInputParameter::INT_N),
			"user_id" => array(tlInputParameter::INT_N),
			"rights_id" => array(tlInputParameter::INT_N),
	
			"doAction" => array(tlInputParameter::STRING_N,0,30),
			"firstName" => array(tlInputParameter::STRING_N,0,30),
			"lastName" => array(tlInputParameter::STRING_N,0,100),
			"emailAddress" => array(tlInputParameter::STRING_N,0,100),
			"locale" => array(tlInputParameter::STRING_N,0,10),
			"login" => array(tlInputParameter::STRING_N,0,30),
			"password" => array(tlInputParameter::STRING_N,0,32),
	
			"user_is_active" => array(tlInputParameter::CB_BOOL),
	);

	$args = new stdClass();
  	R_PARAMS($iParams,$args);
 	
  	// BUGID 4066 - take care of proper escaping when magic_quotes_gpc is enabled
	$_REQUEST=strings_stripSlashes($_REQUEST);

	return $args;
}

/*
  function: doCreate

  args:

  returns: object with following members
           user: tlUser object
           status:
           template: will be used by viewer logic.
                     null -> viewer logic will choose template
                     other value -> viever logic will use this template.



*/
function doCreate(&$dbHandler,&$argsObj)
{
	$op = new stdClass();
	$op->user = new tlUser();
	$op->status = $op->user->setPassword($argsObj->password);
	$op->template = 'usersEdit.tpl';
	$op->operation = '';

    $statusOk = false;
	if ($op->status >= tl::OK)
	{
	  	initializeUserProperties($op->user,$argsObj);
		$op->status = $op->user->writeToDB($dbHandler);
		if($op->status >= tl::OK)
		 {
		      $statusOk = true;
		      $op->template = null;
		      logAuditEvent(TLS("audit_user_created",$op->user->login),"CREATE",$op->user->dbID,"users");
		      $op->user_feedback = sprintf(lang_get('user_created'),$op->user->login);
		}
	}

	if (!$statusOk)
	{
	    $op->operation = 'create';
	    $op->user_feedback = getUserErrorMessage($op->status);
	}

    return $op;
}


function doUpdate(&$dbHandler,&$argsObj,$sessionUserID)
{
    $op = new stdClass();
    $op->user_feedback = '';
    $op->user = new tlUser($argsObj->user_id);
	$op->status = $op->user->readFromDB($dbHandler);
	if ($op->status >= tl::OK)
	{
		initializeUserProperties($op->user,$argsObj);
		$op->status = $op->user->writeToDB($dbHandler);
		if ($op->status >= tl::OK)
		{
			logAuditEvent(TLS("audit_user_saved",$op->user->login),"SAVE",$op->user->dbID,"users");

			if ($sessionUserID == $argsObj->user_id)
			{
				$_SESSION['currentUser'] = $op->user;
				setUserSession($dbHandler,$op->user->login, $argsObj->user_id,
				               $op->user->globalRoleID, $op->user->emailAddress, $op->user->locale);
	
				if (!$argsObj->user_is_active)
				{
					header("Location: ../../logout.php");
					exit();
				}
			}
		}
		$op->user_feedback = getUserErrorMessage($op->status);
	}
    return $op;
}

/**
 * 
 *
 * @internal revisions
 *	20100502 - franciscom - BUGID 3417
 */
function createNewPassword(&$dbHandler,&$argsObj,&$userObj,$newPasswordSendMethod)
{
	$op = new stdClass();
	$op->user_feedback = '';
	$op->new_password = '';
	
	// Try to validate mail configuration
	//
	// From Zend Documentation
	// You may find you also want to match IP addresses, Local hostnames, or a combination of all allowed types. 
	// This can be done by passing a parameter to Zend_Validate_Hostname when you instantiate it. 
	// The paramter should be an integer which determines what types of hostnames are allowed. 
	// You are encouraged to use the Zend_Validate_Hostname constants to do this.
    // The Zend_Validate_Hostname constants are: ALLOW_DNS to allow only DNS hostnames, ALLOW_IP to allow IP addresses, 
    // ALLOW_LOCAL to allow local network names, and ALLOW_ALL to allow all three types. 
	// 
	$validator = new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_ALL);
	$smtp_host = config_get( 'smtp_host' );
	
	$password_on_screen = ($newPasswordSendMethod == 'display_on_screen');
	if( $validator->isValid($smtp_host) || $password_on_screen )
	{
		$dummy = resetPassword($dbHandler,$argsObj->user_id,$newPasswordSendMethod);

		$op->user_feedback = $dummy['msg'];
		$op->status = $dummy['status'];
		$op->new_password = $dummy['password'];
		if ($op->status >= tl::OK)
		{
			logAuditEvent(TLS("audit_pwd_reset_requested",$userObj->login),"PWD_RESET",$argsObj->user_id,"users");
			$op->user_feedback = lang_get('password_reseted');
			if( $password_on_screen )
			{
				$op->user_feedback = lang_get('password_set') . $dummy['password'];			
			}
		}
		else
		{
			$op->user_feedback = sprintf(lang_get('password_cannot_be_reseted_reason'),$op->user_feedback);
		}
	}
	else
	{
		$op->status = tl::ERROR;
		$op->user_feedback = lang_get('password_cannot_be_reseted_invalid_smtp_hostname');
	}
	return $op;
}

/*
  function: initializeUserProperties
            initialize members for a user object.

  args: userObj: data read from DB
        argsObj: data entry from User Interface

  returns: -

*/
function initializeUserProperties(&$userObj,&$argsObj)
{
	if (!is_null($argsObj->login))
	{
    	$userObj->login = $argsObj->login;
	}
	$userObj->emailAddress = $argsObj->emailAddress;
	$userObj->firstName = $argsObj->firstName;
	$userObj->lastName = $argsObj->lastName;
	$userObj->globalRoleID = $argsObj->rights_id;
	$userObj->locale = $argsObj->locale;
	$userObj->isActive = $argsObj->user_is_active;
}

function decodeRoleId(&$dbHandler,$roleID)
{
    $roleInfo = tlRole::getByID($dbHandler,$roleID);
    return $roleInfo->name;
}

function renderGui(&$smartyObj,&$argsObj,$templateCfg)
{
    $doRender = false;
    switch($argsObj->doAction)
    {
        case "edit":
        case "create":
        case "resetPassword":
       		$doRender = true;
    		$tpl = $templateCfg->default_template;
    		break;

		case "doCreate":
		case "doUpdate":
        if(!is_null($templateCfg->template))
        {
            $doRender = true;
            $tpl = $templateCfg->template;
        }
        else
        {
			header("Location: usersView.php");
			exit();
        }
    	break;

    }

    if($doRender)
    {
        $smartyObj->display($templateCfg->template_dir . $tpl);
    }    
}

function checkRights(&$db,&$user)
{
	return $user->hasRight($db,'mgt_users');
}
?>