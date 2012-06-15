{* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: resultsImport.tpl,v 1.10 2010/09/07 07:18:39 mx-julian Exp $
Purpose: smarty template - manage import of test cases and test suites

rev:
    20100908 - Julian - BUGID 3752 - $gui->tplan to $gui->tplanID
    20100821 - franciscom - BUGID 3470 - reopened due to filters refactoring
    20100518 - franciscom - BUGID 3470 - contribution twelve
*}
{include file="inc_head.tpl"}
{lang_get var='labels' 
          s='view_file_format_doc,file_type,btn_cancel,btn_upload_file,
             title_imp_tc_data,local_file,max_size_cvs_file1,max_size_cvs_file2'}

<body>
{config_load file="input_dimensions.conf" section="tcImport"} {* Constant definitions *}

{* <h1 class="title">{$container_description}{$smarty.const.TITLE_SEP}{$container_name|escape}</h1> *}

<div class="workBack">
<h1 class="title">{$gui->import_title}</h1>

{if $gui->resultMap eq null}
<form method="post" enctype="multipart/form-data" action="{$SCRIPT_NAME}">
  <table>
  <tr>
  	<td>{$labels.file_type}</td>
    <td><select name="importType">
		      {html_options options=$gui->importTypes}
	      </select>
      	<a href={$basehref}{$smarty.const.PARTIAL_URL_TL_FILE_FORMATS_DOCUMENT}>{$labels.view_file_format_doc}</a>
	  </td>
  </tr>
  	
	<tr>
	 <td>{$labels.local_file}</td> 
	 <td>
	  {* standard way to set a maximum size for upload value is size IN BYTES *}
	 	<input type="hidden" name="MAX_FILE_SIZE" value="{$gui->importLimit}" /> {* restrict file size *}
		<input type="file" name="uploadedFile" 
	                        size="{#FILENAME_SIZE#}" maxlength="{#FILENAME_MAXLEN#}"/></td>
  </tr>                              
	</table>
	<p>{$labels.max_size_cvs_file1} {$gui->importLimit/1024} {$labels.max_size_cvs_file2}</p>
	
	<div class="groupBtn">
		<input type="hidden" name="buildID" value="{$gui->buildID}" />
    <input type="hidden" name="platformID" value="{$gui->platformID}" /> {* BUGID 3470 *}
    <input type="hidden" name="tplanID" value="{$gui->tplanID}" /> {* BUGID 3470 & BUGID 3752 *}

		<input type="submit" name="UploadFile" value="{$labels.btn_upload_file}" />
		<input type="button" name="cancel" value="{$labels.btn_cancel}" 
			onclick="javascript: location.href=fRoot+'lib/results/resultsImport.php';" />
	</div>
</form>
{else}
	{foreach item=result from=$gui->resultMap}
		{$labels.title_imp_tc_data} : {$result[0]|escape}<br />
	{/foreach}
	{include file="inc_refreshTree.tpl"}
{/if}

{if $gui->doImport}
	{include file="inc_refreshTree.tpl"}
{/if}

{if $gui->file_check.status_ok eq 0}
    <script>
    alert("{$gui->file_check.msg}");
    </script>
{/if}  


</div>

</body>
</html>