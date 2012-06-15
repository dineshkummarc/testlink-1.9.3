<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: getExecNotes.php,v $
 *
 * @version $Revision: 1.12 $
 * @modified $Date: 2010/06/24 17:25:57 $ by $Author: asimon83 $
 *
 *
 * rev:	
 * 20100312 - BUGID 3269 - asimon
 * 20100129 - BUGID 3113 - franciscom
 *			solved ONLY for  $webeditorType == 'none'
 * 20090530: franciscom - try to improve usability in order to allow edit online
 */
require_once('../../config.inc.php');
require_once('common.php');
require_once("web_editor.php");
require_once('exec.inc.php');

$webeditorCfg = getWebEditorCfg('execution');
require_once(require_web_editor($webeditorCfg['type']));


testlinkInitPage($db);
$templateCfg = templateConfiguration();

$tcase_mgr = new testcase($db);
$args = init_args();

$webeditorCfg = getWebEditorCfg('execution');
$map = get_execution($db,$args->exec_id);

// BUGID 3269
//if( $webeditorCfg['type'] != 'none' )
//{
//    $notesContent=createExecNotesWebEditor($args->exec_id,$_SESSION['basehref'],$webeditorCfg,$map[0]['notes']);
//}
//else
//{
    $notesContent=$map[0]['notes'];
//}

$readonly = $args->readonly > 0 ? 'readonly="readonly"' : ''; 
$smarty = new TLSmarty();
$smarty->assign('notes',$notesContent);
$smarty->assign('webeditorType',$webeditorCfg['type']);
$smarty->assign('readonly',$readonly);
$smarty->assign('editor_instance','exec_notes_' . $args->exec_id);
$smarty->display($templateCfg->template_dir . $templateCfg->default_template);



function createExecNotesWebEditor($id,$basehref,$editorCfg,$content=null)
{
    // Important Notice:
    //
    // When using tinymce or none as web editor, we need to set rows and cols
    // to appropriate values, to avoid an ugly ui.
    // null => use default values defined on editor class file
    //
    // Rows and Cols values are useless for FCKeditor.
    //
    $of=web_editor("exec_notes_$id",$basehref,$editorCfg) ;
    $of->Value = $content;
    $editor=$of->CreateHTML(10,60);         
    unset($of);
    return $editor;
}



function init_args()
{
    $iParams = array("exec_id" => array(tlInputParameter::INT_N),
                     "readonly" => array(tlInputParameter::INT_N));
	$args = new stdClass();
	R_PARAMS($iParams,$args);
    return $args; 
}
?>
