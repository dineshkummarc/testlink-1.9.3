<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: no_editor.class.php,v $
 *
 * @version $Revision: 1.4 $
 * @modified $Date: 2008/10/17 16:16:08 $ by $Author: asielb $
 * 
 * Rev :
 *      20071125 - franciscom - added dtree_render_req_node_open
 *
 **/

class no_editor
{
	var $InstanceName ;
	var $Value;
	var $rows = 8;
	var $cols = 80;

	function __construct($instanceName)
	{
		$this->InstanceName	= $instanceName;
		$this->Value		= '';
	}
  
 	function Create($rows = null,$cols = null)
	{
		echo $this->CreateHtml($rows,$cols);
	}

	function CreateHtml($rows = null,$cols = null)
	{
		$HtmlValue = htmlspecialchars($this->Value);

	    $my_rows = $rows;
	    $my_cols = $cols;

	    if(is_null($my_rows) || $my_rows <= 0)
			$my_rows = $this->rows;
	    if(is_null($my_cols) || $my_cols <= 0)
	    	$my_cols = $this->cols;

	    $Html = "<textarea name=\"{$this->InstanceName}\" " .
		        "id=\"{$this->InstanceName}\" rows=\"{$my_rows}\" cols=\"{$my_cols}\" >".
		        "{$HtmlValue}</textarea>" ;
		return $Html ;
	}

} // class end
?>