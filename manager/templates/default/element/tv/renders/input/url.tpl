<select id="tv{$tv->id}_prefix" name="tv{$tv->id}_prefix" onchange="MODx.fireResourceFormChange();" style="width: 20%;">
{foreach from=$urls item=url}
	<option value="{$url}" {if $url EQ $selected}selected="selected"{/if}>{$url}</option>
{/foreach}
</select>
<input id="tv{$tv->id}" name="tv{$tv->id}"
	type="text"
	value="{$tv->get('processedValue')}"
	style="width: 77%;"
	onchange="MODx.fireResourceFormChange();"
/>