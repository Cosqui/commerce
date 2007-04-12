<div class="floaticon">{bithelp}</div>
<div class="edit bitcommerce {$smarty.request.main_page}">
	<div class="header">
		<h1>{tr}Sales and Commissions{/tr}</h1>
	</div>

	<div class="body">

{form method="post" enctype="multipart/form-data" action=$smarty.server.REQUEST_URI}
	<div class="row">
		{formlabel label="Payment Method"}
		{forminput}
			{html_options id="commissions_payment_method" name="commissions_payment_method" options=$paymentOptions selected=$gBitUser->getPreference('commissions_payment_method')  onchange="updatePaymentMethod()" }
			{formhelp note="Select the way in which you would like to receive payments. <a href='`$smarty.const.WIKI_PKG_URL`Selling+Products'>Fees</a> may be required for some payment methods."}
		{/forminput}
	</div>

	{include file="bitpackage:bitcommerce/commissions_payment_options_inc.tpl"}

	<div class="row submit">
		<input type="submit" name="save_commission_settings" value="Save" />
	</div>
{/form}

<div class="clear"></div>

{literal}
<script type="text/javascript">
function updatePaymentMethod() {
	hideById('commissionstorecredit');
	hideById('commissionpaypal');
	hideById('commissionworldpay');
	hideById('commissioncheck');
methodValue = 'commission'+$('commissions_payment_method').value;
showById(methodValue);
	return true;
}

updatePaymentMethod();
</script>

{/literal}
	</div>
</div>

<div class="edit bitcommerce {$smarty.request.main_page}">
	<div class="body">

<div class="row">
	{formlabel label="Commission History"}

	{forminput}

{if $commissionList}
	<table class="data">
	<tr>
		<th style="text-align:left">{tr}Date Purchased{/tr}</th>
		<th style="text-align:left">{tr}Product Sold{/tr}</th>
		<th colspan="2" style="text-align:right">{tr}Commission Earned{/tr}</th>
		<th colspan="2" style="text-align:right">{tr}Commission Received{/tr}</th>
	</tr>
	{foreach from=$commissionList key=orderId item=commission}
	<tr>
		{if $commission.orders_products_id}
		<td style="text-align:left" class="item">{$commission.date_purchased}</td>
		<td style="text-align:left" class="item"><a href="{$commission.products_id}">{$commission.products_name}</a></td>
		<td style="text-align:right" class="item">{$commission.products_quantity} @ ${$commission.products_commission}</td>
		<td style="text-align:right" class="item">${$commission.products_quantity*$commission.products_commission}</td>
		<td style="text-align:right" class="item"></td>
		<td style="text-align:left" class="item"></td>
		{elseif $commission.commissions_payments_id}
		<td style="text-align:left" class="item">{$commission.period_end_date}</td>
		<td style="text-align:left" class="item">{tr}Commission Payment{/tr}</td>
		<td style="text-align:right" class="item"></td>
		<td class="item"></td>
		<td style="text-align:right" class="item">${$commission.payment_amount}</td>
		<td style="text-align:left" class="item">{$commission.payment_method}</td>
		{/if}
	</tr>
	{/foreach}
	</table>
{else}
	<div>
{tr}No sales with commissions.{/tr}
	</div>
{/if}
	{/forminput}

</div>

	</div>
</div>

