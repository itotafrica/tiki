{* $Header: /cvsroot/tikiwiki/tiki/templates/styles/musus/modules/mod-top_visited_blogs.tpl,v 1.1 2004-01-07 04:31:24 musus Exp $ *}

{if $feature_blogs eq 'y'}
    {if $nonums eq 'y'}
    {eval var="{tr}Most `$module_rows` visited blogs{/tr}" assign="tpl_module_title"}
    {else}
    {eval var="{tr}Most visited blogs{/tr}" assign="tpl_module_title"}
    {/if}

    {tikimodule title=$tpl_module_title name="top_visited_blogs"}
    <table  border="0" cellpadding="0" cellspacing="0">
    {section name=ix loop=$modTopVisitedBlogs}
	<tr>{if $nonums != 'y'}<td class="module" valign="top">{$smarty.section.ix.index_next})</td>{/if}
	<td class="module"><a class="linkmodule" href="tiki-view_blog.php?blogId={$modTopVisitedBlogs[ix].blogId}">{$modTopVisitedBlogs[ix].title}</a></td></tr>
    {/section}
    </table>
    {/tikimodule}
{/if}
