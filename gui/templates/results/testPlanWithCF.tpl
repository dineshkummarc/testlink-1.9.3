{* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 
$Id: testPlanWithCF.tpl,v 1.5 2010/09/21 13:44:32 mx-julian Exp $

Purpose: For a test plan, list test cases with Custom Fields at Execution

rev:
  20100921 - Julian - BUGID 3797 - use exttable
*}

{lang_get var="labels" 
          s='no_uncovered_testcases,testproject_has_no_reqspec,
             testproject_has_no_requirements,no_linked_tplan_cf,generated_by_TestLink_on,
             test_case,build,th_owner,date,status,info_testPlanWithCF'}
{include file="inc_head.tpl" openHead="yes"}
{foreach from=$gui->tableSet key=idx item=matrix name="initializer"}
  {assign var=tableID value=$matrix->tableID}
  {if $smarty.foreach.initializer.first}
    {$matrix->renderCommonGlobals()}
    {if $matrix instanceof tlExtTable}
        {include file="inc_ext_js.tpl" bResetEXTCss=1}
        {include file="inc_ext_table.tpl"}
    {/if}
  {/if}
  {$matrix->renderHeadSection()}
{/foreach}
</head>
<body>
<h1 class="title">{$gui->pageTitle|escape}</h1>
<div class="workBack" style="overflow-y: auto;">

{if $gui->warning_msg == ''}
    {include file="inc_result_tproject_tplan.tpl" 
         arg_tproject_name=$gui->tproject_name arg_tplan_name=$gui->tplan_name}	
    {foreach from=$gui->tableSet key=idx item=matrix}
       {assign var=tableID value=table_$idx}
       {$matrix->renderBodySection($tableID)}
    {/foreach}
    <br />
    <p class="italic">{$labels.info_testPlanWithCF}</p>
    <br />
    {$labels.generated_by_TestLink_on} {$smarty.now|date_format:$gsmarty_timestamp_format}
{else}
    <br />
    <div class="user_feedback">
    {$gui->warning_msg}
    </div>
{/if} 
</div>
</body>
</html>
