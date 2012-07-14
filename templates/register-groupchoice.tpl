{if $prefs.user_register_prettytracker eq 'y' and $prefs.user_register_prettytracker_tpl and $prefs.socialnetworks_user_firstlogin != 'y'}
	{if isset($theChoiceGroup)}
		<input type="hidden" name="chosenGroup" value="{$theChoiceGroup|escape}" />
	{elseif isset($listgroups)}
		{foreach item=gr from=$listgroups}
			{if $gr.registrationChoice eq 'y'}
				<div class="registergroup">
					<input type="radio" name="chosenGroup" id="gr_{$gr.id}" value="{$gr.groupName|escape}" />
					<label for="gr_{$gr.id}">
						{if $gr.groupDesc}
							{tr}{$gr.groupDesc|escape}{/tr}
						{else}
							{$gr.groupName|escape}
						{/if}
					</label>
				</div>
			{/if}
		{/foreach}
	{/if}
{else}
	{* Groups *}
	{if isset($theChoiceGroup)}
		<input type="hidden" name="chosenGroup" value="{$theChoiceGroup|escape}" />
		{jq}
$.getJSON('group_tracker_ajax.php', {chosenGroup:'{{$theChoiceGroup}}'}, function(data) {
	$("#registerTracker").html(data['res']).modal();
});
		{/jq}
		<tr><td colspan="2"><div id="registerTracker"></div></td></tr>
	{elseif isset($listgroups)}
		<tr>
			<td>{tr}Group{/tr}</td>
			<td>
				{foreach item=gr from=$listgroups}
					{if $gr.registrationChoice eq 'y'}
						<div class="registergroup">
							<input type="radio" name="chosenGroup" id="gr_{$gr.id}" value="{$gr.groupName|escape}"
									{if !empty($smarty.post.chosenGroup) and $smarty.post.chosenGroup eq $gr.groupName|escape}checked="checked"{/if} />
							<label for="gr_{$gr.id}">
								{if $gr.groupDesc}
									{tr}{$gr.groupDesc|escape}{/tr}
								{else}
									{$gr.groupName|escape}
								{/if}
							</label>
						</div>
					{/if}
				{/foreach}
			</td>
		</tr>
		<tr><td colspan="2"><div id="registerTracker"></div></td></tr>
		{jq}
$("input[name='chosenGroup']").change(function() {
	$("#registerTracker").modal("{tr}Loading...{/tr}");
	var gr = $("input[name='chosenGroup']:checked").val();
	$.getJSON('group_tracker_ajax.php',{chosenGroup:gr}, function(data) {
		if ($("#registerTracker").children().length === 0) {
			$(".trackerplugindesc").parents("tr").remove();
		}
		$("#registerTracker").html(data['res']).modal();
		$("input[name^=captcha]").parents("tr").show();
		if (data['validation']) {
			var $v = $("#registerTracker").parents('form').validate();
			$.extend( true, $v.settings, data['validation'] );
		}
		$("#registerTracker").parents("table:first").css({borderSpacing:"0 !important",borderCollapse:"collapse !important"});
		$("tr td:first", "#registerTracker").width($("#registerTracker").parents('table:first').find("td:first").width());
	});
}){{if !empty($smarty.post.chosenGroup)}}.change(){{/if}};
$("input[name^=captcha]").parents("tr").hide();
		{/jq}
	{/if}
{/if}
