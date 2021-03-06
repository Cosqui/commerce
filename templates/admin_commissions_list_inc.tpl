	<table class="table data">
	<tr>
		<th class="item" colspan="2">{tr}Payee{/tr}</th>
		<th class="item">{tr}Commission Due{/tr}</th>
		<th class="item" colspan="2">{tr}Payment Method{/tr}</th>
	</tr>
	{foreach from=$commissionsDue key=userId item=commission}
	{cycle assign="oddeven" values="odd,even"}
	<tr>
		<td class="item {$oddeven}">{displayname hash=$commission} ( {$commission.user_id} )</td>
		<td class="item {$oddeven}">{$commission.email}</td>
		<td class="item {$oddeven}" style="text-align:right">{$commission.commission_sum|string_format:"$%.2f"}</td>
		<td class="item {$oddeven}">
			<a href="#" onclick="BitBase.toggleElementDisplay('enterpayment{$userId}','table-cell',true);return false;">Enter Payment</a>
		</td>
		<td class="item">{$commission.payment_method}</td>
	</tr>
	<tr>
		<td colspan="5" class="item {$oddeven}" style="display:none" id="enterpayment{$commission.user_id}">
			{include file="bitpackage:bitcommerce/admin_commission_payment_inc.tpl" commission=$commission}
		</td>
	</tr>

	{foreachelse}
	<tr>
		<td class="item">{tr}No Commissions.{/tr}</td>
	</tr>
	{/foreach}
	</table>

