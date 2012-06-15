{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
$Id: tcSearchResults.tpl,v 1.6 2010/09/21 10:03:18 mx-julian Exp $
Purpose: smarty template - view test case in test specification

rev:
  20100921 - Julian - BUGID 3793 - use exttable to display search results
  20080322 - franciscom - php errors clean up
*}

{include file="inc_head.tpl" openHead='yes'}
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

<h1 class="title">{$gui->pageTitle}</h1>

<div class="workBack">
{if $gui->warning_msg == ''}
  {foreach from=$gui->tableSet key=idx item=matrix}
    {assign var=tableID value=table_$idx}
    {$matrix->renderBodySection($tableID)}
  {/foreach}
  <br />
  {lang_get s='generated_by_TestLink_on'} {$smarty.now|date_format:$gsmarty_timestamp_format}
{else}
  <div class="user_feedback">
  <br />
  {$gui->warning_msg}
  </div>
{/if} 
</div>
</body>
</html>
