<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Check configuration and system 
 * Using: Installer, sysinfo.php and Login
 * 
 * @package 	TestLink
 * @author 		Martin Havlat
 * @copyright 	2007-2009, TestLink community 
 * @version    	CVS: $Id: configCheck.php,v 1.55.4.1 2011/01/26 09:01:58 mx-julian Exp $
 * @link 		http://www.teamst.org/index.php
 * @see			sysinfo.php
 *
 * @internal Revisions:
 * 	
 *  20110411 - Julian - BUGID 4398 - Prevent user-login when database scheme version does not fit 
 *                                   required scheme
 *  20110126 - Julian - BUGID 4186 - checkSchemaVersion() moved last_db_version to const.inc.php
 *                                 - better log message for DB 1.3 to DB 1.4 upgrade
 *  20100617 - franciscom - domxml is not needed anymore
 *  20090713 - franciscom - tested is_writable() on windows with PHP 5.
 *                          minor refactoring
 *  20090416 - havlatm - checking: database, GD lib and browser support
 *  20090126 - franciscom - check_php_extensions() refactoring
 *  20090109 - havlatm - import checking functions from Installer 
 * 	20081122 - franciscom - checkForExtensions() - added check of needed extensions to use pChart
 *  20081015 - franciscom - getSecurityNotes() - refactoring
 *
 **/
// ---------------------------------------------------------------------------------------------------

/**
 * get home URL
 * 
 * @author adapted from Mantis Bugtracking system
 * @return string URL 
 */
function get_home_url()
{
  if ( isset ( $_SERVER['PHP_SELF'] ) ) {
	$t_protocol = 'http';
	if ( isset( $_SERVER['HTTPS'] ) && ( strtolower( $_SERVER['HTTPS'] ) != 'off' ) ) {
		$t_protocol = 'https';
	}

	// $_SERVER['SERVER_PORT'] is not defined in case of php-cgi.exe
	if ( isset( $_SERVER['SERVER_PORT'] ) ) {
		$t_port = ':' . $_SERVER['SERVER_PORT'];
		if ( ( ':80' == $t_port && 'http' == $t_protocol )
		  || ( ':443' == $t_port && 'https' == $t_protocol )) {
			$t_port = '';
		}
	} else {
		$t_port = '';
	}

	if ( isset( $_SERVER['HTTP_HOST'] ) ) {
		$t_host = $_SERVER['HTTP_HOST'];
	} else if ( isset( $_SERVER['SERVER_NAME'] ) ) {
		$t_host = $_SERVER['SERVER_NAME'] . $t_port;
	} else if ( isset( $_SERVER['SERVER_ADDR'] ) ) {
		$t_host = $_SERVER['SERVER_ADDR'] . $t_port;
	} else {
		$t_host = 'www.example.com';
	}

	$t_path = dirname( $_SERVER['PHP_SELF'] );
	if ( '/' == $t_path || '\\' == $t_path ) {
		$t_path = '';
	}

	$t_url	= $t_protocol . '://' . $t_host . $t_path.'/';
	
	return ($t_url);
  }
}


/** check language acceptance by web client */
function checkServerLanguageSettings($defaultLanguage)
{
	global $g_locales;
	$language = $defaultLanguage;

	// check for !== false because getenv() returns false on error
	$serverLanguage = getenv($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	if(false !== $serverLanguage)
	{
		if (array_key_exists($serverLanguage,$g_locales))
			$language = $serverLanguage;
	}

	return ($language);
}


/** Check if we need to run the install program. Used on login.php and index.php */
function checkConfiguration()
{
	clearstatcache();
	$file_to_check = "config_db.inc.php";

	if(!is_file($file_to_check))
	{
		echo '<html><body onload="' . "location.href='./install/index.php'" . '"></body></html>';
		exit();	
	}
}


/** 
 * checks if installation is done
 * 
 * @return bool returns true if the installation was already executed, false else
 * @author Martin Havlat
 **/
function checkInstallStatus()
{
    $status=defined('DB_TYPE') ? true : false;
    return $status;
}


/**
 * Checks if charts are supported (GD and PNG library) 
 *
 * @return string resulted message ('OK' means pass)
 * @author Martin Havlat 
 **/
function checkLibGd()
{
	if( extension_loaded('gd') )
	{
		$arrLibConf = gd_info();
		$msg = lang_get("error_gd_png_support_disabled");
		if ($arrLibConf["PNG Support"]){
			$msg = 'OK';
		}	
	}
	else
	{
		$msg = lang_get("error_gd_missing");
	}
	return $msg;
}


/**
 * Checks if needed functions and extensions are defined 
 *
 * @param array [ref] msgs will be appended
 * @return bool returns true if all extension or functions ar present or defined
 *
 * @author Andreas Morsing 
 * @todo Martin: it's used in getSecurityNotes() ... but it's not consistent with 
 * 		checkPhpExtensions() - refactore
 **/
// * rev: 20081122 - franciscom - added gd2 check
function checkForExtensions(&$msg)
{
	// if (!function_exists('domxml_open_file'))
	// {
	// 	$msg[] = lang_get("error_domxml_missing");
	// }
	
	// without this pChart do not work
	if( !extension_loaded('gd') )
	{
		$msg[] = lang_get("error_gd_missing");
	}
	return true;
}

/**
 * checks if the install dir is present
 *
 * @return bool returns true if the install dir is present, false else
 * @author Andreas Morsing 
 **/
function checkForInstallDir()
{
	$installerDir = TL_ABS_PATH. DIRECTORY_SEPARATOR . "install"  . DIRECTORY_SEPARATOR;
	clearstatcache();
	$dirExists=	(is_dir($installerDir)) ? true : false;
	return $dirExists;	
}


/**
 * checks if the default password for the admin accout is still set
 *
 * @return boolean returns true if the default password for the admin account is set, 
 * 				false else
 * @author Andreas Morsing 
 **/
function checkForAdminDefaultPwd(&$db)
{
	$passwordHasDefaultValue = false;
	
	$user = new tlUser();
	$user->login = "admin";
	if ($user->readFromDB($db,tlUser::USER_O_SEARCH_BYLOGIN) >= tl::OK && 
		 $user->comparePassword("admin") >= tl::OK)
	{	 
		$passwordHasDefaultValue = true;
	}	
	return $passwordHasDefaultValue;
}

/*
  function: checkForLDAPExtension
  args :
  returns: 
*/
function checkForLDAPExtension()
{
	return extension_loaded("ldap");
}

/**
 * builds the security notes while checking some security issues
 * these notes should be displayed!
 *
 * @return array returns the security issues, or null if none found!
 * @author Andreas Morsing 
 *
 * @internal rev :
 *      20081015 - franciscom - LDAP checks refactored
 *      20080925 - franciscom - added option to not show results
 *      20070626 - franciscom - added LDAP checks  
 **/
function getSecurityNotes(&$db)
{
	$repository['type'] = config_get('repositoryType');
	$repository['path'] = config_get('repositoryPath');
  
	$securityNotes = null;
	if (checkForInstallDir())
	{
		$securityNotes[] = lang_get("sec_note_remove_install_dir");
  }
  
  $authCfg = config_get('authentication');
  if( 'LDAP' == $authCfg['method']  )
  {
    if( !checkForLDAPExtension() )
    {
	    $securityNotes[] = lang_get("ldap_extension_not_loaded");
	  }  
  } 
  else
  {
    if( checkForAdminDefaultPwd($db) )
    {
		    $securityNotes[] = lang_get("sec_note_admin_default_pwd");
		}
  }

  
	if (!checkForBTSConnection())
	{
		$securityNotes[] = lang_get("bts_connection_problems");
	}
		
	if($repository['type'] == TL_REPOSITORY_TYPE_FS)
	{
		$ret = checkForRepositoryDir($repository['path']);
		if(!$ret['status_ok'])
		{
			$securityNotes[] = $ret['msg'];
		}	
	}

	// 20070121 - needed when schemas change has been done
	// This call can be removed when release is stable
	$res = checkSchemaVersion($db);
	$msg = $res['msg'];
	
	if($msg != "")
	{
		$securityNotes[] = $msg;
	}
	
	// 20070911 - fixing bug 1021 
	$msg = checkForTestPlansWithoutTestProjects($db);
	if($msg != "")
	{
		$securityNotes[] = $msg;
	}
	
	$msg = checkEmailConfig();
	if(!is_null($msg))
	{
	  foreach($msg as $detail)
	  {
		   $securityNotes[] = $detail;
		}   
	}
	checkForExtensions($securityNotes);
  
	if(!is_null($securityNotes))
	{
	  $user_feedback=config_get('config_check_warning_mode');
	  
	  switch($user_feedback)
	  {
	      case 'SCREEN':
	      break;
	      
	      case 'FILE':
	      case 'SILENT':
            $warnings='';
		        $filename = config_get('log_path') . 'config_check.txt';
            if (@$handle = fopen($filename, 'w')) 
            {
              		$warnings=implode("\n",$securityNotes);
              		@fwrite($handle, $warnings);
		              @fclose($handle);	
	          }
       	    $securityNotes=null;
	          if($user_feedback=='FILE')
	          {
        	      $securityNotes[] = sprintf(lang_get('config_check_warnings'),$filename);
	          } 
	      break;
	  }
	}
	return $securityNotes;
}


/**
 * checks if the connection to the Bug Tracking System database is working
 *
 * @return boolean returns true if ok
 * 				false else
 * @author franciscom 
 **/
function checkForBTSConnection()
{
  
	global $g_bugInterface;
	$status_ok = true;
	if($g_bugInterface && !$g_bugInterface->connect())
	{	
		$status_ok = false;
	}
	return $status_ok; 
}

/** 
 * Check if server OS is microsoft Windows flavour
 * 
 * @return boolean TRUE if microsoft
 * @author havlatm
 */ 
function isMSWindowsServer()
{
	$osID = strtoupper(substr(PHP_OS, 0, 3));
	$isWindows = (strcmp('WIN',$osID) == 0) ? true: false;
	return $isWindows; 
}

/*
  function: checkForRepositoryDir
  args :
  returns: 
*/
function checkForRepositoryDir($the_dir)
{
	clearstatcache();

	$ret['msg']=lang_get('attachments_dir') . " " . $the_dir . " ";
	$ret['status_ok']=false;
  	
	if(is_dir($the_dir)) 
	{
		$ret['msg'] .= lang_get('exists') . ' ';
		$ret['status_ok'] = true;

		// There is a note on PHP manual that points that on windows
		// is_writable() has problems => need a workaround
		/** @TODO verify if it's valid for PHP5 */
		// if( isMSWindowsServer() )
		// {
        //     $test_dir= $the_dir . '/requirements/';
        //     if(!is_dir($test_dir))
        //     {
        //       // try to make the dir
        //       $stat = @mkdir($test_dir);
        //       $ret['status_ok']=$stat;
        //     }
		// }
		// else
		// {
		// 	$ret['status_ok'] = (is_writable($the_dir)) ? true : false; 
    	// }
        // 20090713 - franciscom - tested on windows XP with PHP 5.2.9 seems OK
 	    $ret['status_ok'] = (is_writable($the_dir)) ? true : false; 

		if($ret['status_ok']) 
		{
   	    	$ret['msg'] .= lang_get('directory_is_writable');
   		}
       	else
       	{
   	    	$ret['msg'] .= lang_get('but_directory_is_not_writable');
   	    }	
	} 
	else
	{
		$ret['msg'] .= lang_get('does_not_exist');
	}
	
	return($ret);
}


/**
 * Check if DB schema is valid
 * 
 * @param pointer $db Database class
 * @return string message
 * @todo Update list of versions
 */
function checkSchemaVersion(&$db)
{
	$result = array('status' => tl::ERROR, 'msg' => null);
	$last_version = TL_LAST_DB_VERSION;  // BUGID 4186: moved last db version to const.inc.php
	$db_version_table= DB_TABLE_PREFIX . 'db_version';
	
	$sql = "SELECT * FROM {$db_version_table} ORDER BY upgrade_ts DESC";
	$res = $db->exec_query($sql,1);  
	if (!$res)
	{
		return $result['msg'] = "Failed to get Schema version from DB";
	}
		
	$myrow = $db->fetch_array($res);
	
	$upgrade_msg = "You need to upgrade your Testlink Database to {$last_version} - <br>" .
				   '<a href="./install/index.php" style="color: white">click here access install and upgrade page </a><br>';
	switch (trim($myrow['version']))
	{
		case '1.7.0 Alpha':
		case '1.7.0 Beta 1':
		case '1.7.0 Beta 2':
		case '1.7.0 Beta 3':
		case '1.7.0 Beta 4':
		case '1.7.0 Beta 5':
		case '1.7.0 RC 2':
		case '1.7.0 RC 3':
		case 'DB 1.1':
		case 'DB 1.2':
			$result['msg'] = $upgrade_msg;
			break;

		case 'DB 1.3':
			// DB 1.3 to DB 1.4 requires manual steps
			if ($last_version == 'DB 1.4') {
				$result['msg'] = "Manual upgrade of your DB scheme necessary (1.9.0 -> {$last_version}) - Read README file!";
			} else {
				$result['msg'] = $upgrade_msg;
			}
			break;
			
		case $last_version:
			$result['status'] = tl::OK;
			break;
		
		default:
			$result['msg'] = "Unknown Schema version " .  trim($myrow['version']) . 
			                 ", please upgrade your Testlink Database to " . $last_version;
			break;
	}
	
	return $result;
}

/**
 * checks if the install dir is present
 *
 * @return msg returns if there are any test plans without a test project 
 * @author Asiel Brumfield 
 **/
function checkForTestPlansWithoutTestProjects(&$db)
{
	$msg = "";
	if(count(getTestPlansWithoutProject($db)))
	{	
		$msg = "You have Test Plans that are not associated with Test Projects!";
		if(isset($_SESSION['basehref']))
		{		
			$url = $_SESSION['basehref'] . "/lib/project/fix_tplans.php"; 			
			$msg .= " <a style=\"color:red\" href=\"{$url}\">Fix This</a>";		
		}
	}	
	return $msg;
}


/*
  function: checkEmailConfig 
  args :
  returns: 
*/
function checkEmailConfig()
{
	$common[] = lang_get('check_email_config');
	$msg = null;
	$idx = 1;
	$key2get = array('tl_admin_email','from_email','return_path_email','smtp_host');
	
	foreach($key2get as $cfg_key)
	{  
	   $cfg_param = config_get($cfg_key);
	   if(trim($cfg_param) == "" || strpos($cfg_param,'not_configured') > 0 )
	   {
			$msg[$idx++] = $cfg_key;
	   }  
	}
	return is_null($msg) ? null : $common+$msg; 
}

/** 
 * checking register global = OFF (doesn't cause error')
 * @param integer &$errCounter reference to error counter
 * @return string html table row
 */
function check_php_settings(&$errCounter)
{
    $max_execution_time_recommended = 120;
    $max_execution_time = ini_get('max_execution_time');
    $memory_limit_recommended = 64;
    $memory_limit = intval(str_ireplace('M','',ini_get('memory_limit')));

    $final_msg = '<tr><td>Checking max. execution time (Parameter max_execution_time)</td>';
    if($max_execution_time < $max_execution_time_recommended)
    {
        $final_msg .=  "<td><span class='tab-warning'>{$max_execution_time} seconds - " .
                       "We suggest {$max_execution_time_recommended} " .
                       "seconds in order to manage hundred of test cases (edit php.ini)</span></td>";
    }
    else
    {
        $final_msg .= '<td><span class="tab-success">OK ('.$max_execution_time.' seconds)</span></td></tr>';
    }
    $final_msg .=  "<tr><td>Checking maximal allowed memory (Parameter memory_limit)</td>";
    if($memory_limit < $memory_limit_recommended)
    {
       $final_msg .= "<td><span class='tab-warning'>$memory_limit MegaBytes - " .
                     "We suggest {$memory_limit_recommended} MB" .
                     " in order to manage hundred of test cases</span></td></tr>";
    }
    else
    {
        $final_msg .= '<td><span class="tab-success">OK ('.$memory_limit.' MegaBytes)</span></td></tr>';
    }
	$final_msg .= "<tr><td>Checking if Register Globals is disabled</td>";
	if(ini_get('register_globals')) 
	{
		$final_msg .=  "<td><span class='tab-warning'>Failed! is enabled - " .
		               "Please change the setting in your php.ini file</span></td></tr>";
	}
	else
	{ 
		$final_msg .= "<td><span class='tab-success'>OK</span></td></tr>\n";
    }
	return ($final_msg);
}


/** 
 * Check availability of PHP extensions
 * 
 * @param integer &$errCounter pointer to error counter
 * @return string html table rows
 * @author Martin Havlat
 * @todo martin: Do we require "Checking DOM XML support"? It seems that we use internal library.
 *			if (function_exists('domxml_open_file'))
 */
function checkPhpExtensions(&$errCounter)
{
 
  $cannot_use='cannot be used';
  $td_ok = "<td><span class='tab-success'>OK</span></td></tr>\n";
  $td_failed = '<td><span class="tab-warning">Failed! %s %s.</span></td></tr>';
  
  $msg_support='<tr><td>Checking %s </td>';
  $checks=array();
  $checks[]=array('extension' => 'mysql',
                  'msg' => array('feedback' => 'MySQL Database', 'ok' => $td_ok, 'ko' => 'cannot be used') );
 
  $checks[]=array('extension' => 'pgsql',
                  'msg' => array('feedback' => 'Postgres Database', 'ok' => $td_ok, 'ko' => 'cannot be used') );
 
  
  $checks[]=array('extension' => 'gd',
                  'msg' => array('feedback' => 'GD Graphic library', 'ok' => $td_ok, 
                                 'ko' => " not enabled.<br>Graph rendering requires it. This feature will be disabled." .
                                         " It's recommended to install it.") );
  
  $checks[]=array('extension' => 'ldap',
                  'msg' => array('feedback' => 'LDAP library', 'ok' => $td_ok, 
                                 'ko' => " not enabled. LDAP authentication cannot be used. " .
                                         "(default internal authentication will works)"));
  
  $checks[]=array('extension' => 'json',
                  'msg' => array('feedback' => 'JSON library', 'ok' => $td_ok, 
                                 'ko' => " not enabled. You MUST install it to use EXT-JS tree component. "));
  
  $out='';
  foreach($checks as $test)
  {
      $out .= sprintf($msg_support,$test['msg']['feedback']);
      if( extension_loaded($test['extension']) )
      {
          $msg=$test['msg']['ok'];
      }
      else
      {
          $msg=sprintf($td_failed,$test['msg']['feedback'],$test['msg']['ko']);  
      }
      $out .= $msg;
  }

	return ($out);
}  



/**
 * Check if web server support session data
 * 
 * @param integer &$errCounter reference to error counter
 * @return string html row with result 
 */
function check_session(&$errCounter)
{
	$out = "<tr><td>Checking if sessions are properly configured</td>";

	if( !isset($_SESSION) )
		session_start();

	if( $_SESSION['session_test'] != 1 ) 
	{
    	$color = 'success';
    	$msg = 'OK';
	} else {
    	$color = 'error';
    	$msg = 'Failed!';
        $errCounter++;
    }

	$out .= "<td><span class='tab-$color'>$msg</span></td></tr>\n";
	return ($out);
}  //function end


/**
 * check PHP defined timeout
 * 
 * @param integer &$errCounter reference to error counter
 * @return string html row with result 
 */
function check_timeout(&$errCounter)
{
    $out = '<tr><td>Maximum Session Idle Time before Timeout</td>';

	$timeout = ini_get("session.gc_maxlifetime");
	$gc_maxlifetime_min = floor($timeout/60);
	$gc_maxlifetime_sec = $timeout % 60;
	
    if ($gc_maxlifetime_min > 30) {
    	$color = 'success';
    	$res = 'OK';
	} else if ($gc_maxlifetime_min > 10){
    	$color = 'warning';
    	$res = 'Short. Consider to extend.';
	} else {
    	$color = 'error';
    	$res = 'Too short. It must be extended!';
        $errCounter++;
    }
    $out .= "<td><span class='tab-$color'>".$gc_maxlifetime_min .
    		" minutes and $gc_maxlifetime_sec seconds - ($res)</span></td></tr>\n";
    
	return $out;
}


/**
 * check Database type
 * 
 * @param integer &$errCounter reference to error counter
 * @param string $type valid PHP database type label
 * 
 * @return string html row with result 
 */
function checkDbType(&$errCounter, $type)
{
    $out = '<tr><td>Database type</td>';

	switch ($type)
	{
    	case 'mysql':
    	case 'mssql':
    	case 'postgres':
    		$out .= '<td><span class="tab-success">'.$type.'</span></td></tr>';
    		break;
    		
    	default:
    		$out .= '<td><span class="tab-warning">Unsupported type: '.$type.
					'. MySQL,Postgres and MsSql 2000 are supported DB types. Of course' .
					' you can use also other ones without migration support.</span></td></tr>';
	}
	
	return $out;
}


/**
 * Display Operating System
 * 
 * @return string html table row
 */
function checkServerOs()
{
	$final_msg = '<tr><td>Server Operating System (no constrains)</td>';
	$final_msg .= '<td>'.PHP_OS.'</td></tr>';
	
	return $final_msg;
}  


/**
 * check minimal required PHP version
 * 
 * @param integer &$errCounter pointer to error counter
 * @return string html row with result 
 */
 /* Revision
  		- havlatm: converted to table format, error passed via argument, 
  			disabled unused "not tested version"
        - added argument to point to info
        - added warning regarding possible problems between MySQL and PHP 
          on windows systems due to MySQL password algorithm.
 */
function checkPhpVersion(&$errCounter)
{
	// 5.2 is required because json is used in ext-js component
	$min_version = '5.2.0'; 
	$my_version = phpversion();

	// version_compare:
	// -1 if left is less, 0 if equal, +1 if left is higher
	$php_ver_comp = version_compare($my_version, $min_version);

	$final_msg = '<tr><td>PHP version</td>';

	if($php_ver_comp < 0) 
	{
		$final_msg .= "<td><span class='tab-error'>Failed!</span> - You are running on PHP " . 
	        $my_version . ", and TestLink requires PHP " . $min_version . ' or greater. ' .
	        'This is fatal problem. You must upgrade it.</td>';
		$errCounter += 1;
	} 
	else 
	{
		$final_msg .= "<td><span class='tab-success'>OK ( {$min_version} [minimum version] ";
		$final_msg .= ($php_ver_comp == 0 ? " = " : " <= ");
		$final_msg .=	$my_version . " [your version] " ;
		$final_msg .= " ) </span></td></tr>";
	}

	return ($final_msg);
}  


/**
 * verify that files are writable/readable
 * OK result is for state:
 * 		a) installation - writable
 * 		b) installed - readable
 * 
 * @param integer &$errCounter pointer to error counter
 * @return string html row with result 
 * @author Martin Havlat
 */
function check_file_permissions(&$errCounter, $inst_type, $checked_filename, $isCritical=FALSE)
{
	$checked_path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
	$checked_file = $checked_path.DIRECTORY_SEPARATOR.$checked_filename;
	$out = '<tr><td>Access to file ('.$checked_file.')</td>';

	if ($inst_type == 'new')
	{
		if(file_exists($checked_file)) 
		{
  			if (is_writable($checked_file))
  			{
  				$out .= "<td><span class='tab-success'>OK (writable)</span></td></tr>\n"; 
  			}
  			else
  			{
 				if ($isCritical)
 				{
 					$out .= "<td><span class='tab-error'>Failed! Please fix the file " .
 							$checked_file . " permissions and reload the page.</span></td></tr>"; 
					$errCounter += 1;
 				}
 				else
 				{
 					$out .= "<td><span class='tab-warning'>Not writable! Please fix the file " .
 							$checked_file . " permissions.</span></td></tr>"; 
 				}			
  			}
  		} 
		else 
		{
  			if (is_writable($checked_path))
  			{
  				$out .= "<td><span class='tab-success'>OK</span></td></tr>\n"; 
  			}
  			else
  			{
 				if ($isCritical)
 				{
	 				$out .= "<td><span class='tab-error'>Directory is not writable! Please fix " .
 							$checked_path . " permissions and reload the page.</span></td></tr>"; 
					$errCounter += 1;
 				}
 				else
 				{
 					$out .= "<td><span class='tab-warning'>Directory is not writable! Please fix " .
 							$checked_path . " permissions.</span></td></tr>"; 
 				}			
  			}
		}
	}
	else
	{
		if(file_exists($checked_file)) 
		{
  			if (!is_writable($checked_file))
  			{
  				$out .= "<td><span class='tab-success'>OK (read only)</span></td></tr>\n"; 
  			}
  			else
  			{
 				$out .= "<td><span class='tab-warning'>It's recommended to have read only permission for security reason.</span></td></tr>"; 
  			}
  		} 
		else 
		{
			if ($isCritical)
			{
				$out .= "<td><span class='tab-error'>Failed! The file is not on place.</span></td></tr>"; 
				$errCounter += 1;
 			}
			else
			{
				$out .= "<td><span class='tab-warning'>The file is not on place.</span></td></tr>"; 
			}	
		}
	}

	return($out);
}


/**
 * Check read/write permissions for directories
 * based on check_with_feedback($dirs_to_check);
 * 
 * @param integer &$errCounter pointer to error counter
 * @return string html row with result 
 * @author Martin Havlat
 */
function check_dir_permissions(&$errCounter)
{
	$dirs_to_check = array('gui'.DIRECTORY_SEPARATOR.'templates_c', 'logs', 'upload_area');
	$final_msg = '';
	$msg_ko = "<td><span class='tab-error'>Failed!</span></td></tr>";
	$msg_ok = "<td><span class='tab-success'>OK</span></td></tr>";
	$checked_path_base = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..');

	foreach ($dirs_to_check as $the_d) 
	{
  	// Correct relative path for installer.
		$the_d = $checked_path_base . DIRECTORY_SEPARATOR . $the_d;
  	$final_msg .= "<tr><td>Checking if <span class='mono'>{$the_d}</span> directory exists</td>";
  
		if(!file_exists($the_d)) 
		{
  			$errCounter += 1;
  			$final_msg .= $msg_ko; 
  	} 
		else 
		{
  			$final_msg .= $msg_ok;
    		$final_msg .= "<tr><td>Checking if <span class='mono'>{$the_d}</span> directory is writable</td>";
  			if(!is_writable($the_d)) 
    		{
				    $errCounter += 1;
  	  	    $final_msg .= $msg_ko;  
  			}
    		else
    		{
				    $final_msg .= $msg_ok;  
			  }
   	}
	}

	return($final_msg);
}


/** 
 * Print table with checking www browser support
 *  
 * @param integer &$errCounter pointer to error counter
 * @author Martin Havlat
 **/
function reportCheckingBrowser(&$errCounter)
{
	$browser = strtolower($_SERVER['HTTP_USER_AGENT']);

	echo "\n".'<h2>Browser compliance</h2><table class="common" style="width: 100%;">'."\n";

	echo '<p>'.$browser.'</p>';
	echo '<tr><td>Browser supported</td>';
	if (strpos($browser, 'firefox') === false || strpos($browser, 'msie')  === false)
		echo "<td><span class='tab-success'>OK</span></td></tr>";
	else
		echo "<td><span class='tab-error'>Unsupported: {$_SERVER['HTTP_USER_AGENT']}</span></td></tr>";

	echo '<tr><td>Javascript availability</td><td>' .
			'<script type="text/javascript">document.write(\''.
			'<span class="tab-success">Enabled</span>\');</script>'.
			'<noscript><span class="tab-error">Javascript is disabled!</span></noscript>' .
			'</td></tr>';
		 
	echo '</table>'; 
}


/** 
 * print table with system checking results
 *  
 * @param integer &$errCounter reference to error counter
 * @author Martin Havlat
 **/
function reportCheckingSystem(&$errCounter)
{
	echo '<h2>System requirements</h2><table class="common" style="width: 100%;">';
	echo checkServerOs();
	echo checkPhpVersion($errCounter);
	echo '</table>';
}


/** 
 * print table with database checking
 *  
 * @param integer &$errCounter reference to error counter
 * @author Martin Havlat
 **/
function reportCheckingDatabase(&$errCounter, $type = null)
{
	if (checkInstallStatus())
		$type = DB_TYPE;
		 
	if (!is_null($type))
	{
		echo '<h2>Database checking</h2><table class="common" style="width: 100%;">';
		echo checkDbType($errCounter, $type);
//		echo checkDbConnection($errCounter);
		echo "</table>\n";
		
	}

}


/** 
 * print table with system checking results 
 * 
 * @param integer &$errCounter reference to error counter
 * @author Martin Havlat
 **/
function reportCheckingWeb(&$errCounter)
{
	echo '<h2>Web and PHP configuration</h2><table class="common" style="width: 100%;">';
	echo check_timeout($errCounter);
	echo check_php_settings($errCounter);
	echo checkPhpExtensions($errCounter);
	echo '</table>';

}


/** 
 * print table with system checking results
 *  
 * @param integer &$errCounter pointer to error counter
 * @param string installationType: useful when this function is used on installer
 * 
 * @author Martin Havlat
 **/
function reportCheckingPermissions(&$errCounter,$installationType='none')
{
	echo '<h2>Read/write permissions</h2><table class="common" style="width: 100%;">';
	echo check_dir_permissions($errCounter);
	
	// for $installationType='upgrade' existence of config_db.inc.php is not needed
	$blockingCheck=$installationType=='upgrade' ? FALSE : TRUE;
	if($installationType=='new')
	{
		echo check_file_permissions($errCounter,$installationType,'config_db.inc.php', $blockingCheck);
	}
	// 20100502 - this file is not needed => IMHO we do not need to check existence
	// echo check_file_permissions($errCounter,$installationType,'custom_config.inc.php');
	echo '</table>';
}

// 20070911 - azl
// 20071029 - azl - modified to only get active test plans bug # 1148
function getTestPlansWithoutProject(&$db)
{
    $tables['nodes_hierarchy'] = DB_TABLE_PREFIX . 'nodes_hierarchy';
    $tables['testplans'] = DB_TABLE_PREFIX . 'testplans';
    
	$sql = "SELECT id,name FROM {$tables['nodes_hierarchy']} WHERE id " . 
	       " IN( SELECT id FROM {$tables['testplans']}  " .
		   " WHERE testproject_id=0 and active=1)";
	$testPlans = $db->get_recordset($sql);
	return $testPlans;
}
?>