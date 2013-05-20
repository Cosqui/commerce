<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce																			 |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers													 |
// |																																			|
// | http://www.zen-cart.com/index.php																		|
// |																																			|
// | Portions Copyright (c) 2003 osCommerce															 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,			 |
// | that is bundled with this package in the file LICENSE, and is				|
// | available through the world-wide-web at the following url:					 |
// | http://www.zen-cart.com/license/2_0.txt.														 |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to			 |
// | license@zen-cart.com so we can mail you a copy immediately.					|
// +----------------------------------------------------------------------+
//	$Id: orders.php,v 1.61 2010/07/14 15:19:58 spiderr Exp $
//

require('includes/application_top.php');

$gBitThemes->loadAjax( 'jquery', array( UTIL_PKG_PATH.'/javascript/libs/jquery/plugins/colorbox/jquery.colorbox-min.js' ) );
$gBitThemes->loadCss( UTIL_PKG_PATH.'javascript/libs/jquery/plugins/colorbox/colorbox.css', FALSE, 300, FALSE);

$currencies = new currencies();

if( $gBitThemes->isAjaxRequest() ) {
	require( BITCOMMERCE_PKG_PATH.'classes/CommerceProductManager.php' );
	$productManager = new CommerceProductManager();

	if( !empty( $_REQUEST['new_option_id'] ) ) {
		if( $optionValues = $productManager->getOptionsList( array( 'products_options_id' => $_REQUEST['new_option_id'] ) ) ) {
			if( !empty( $optionValues[$_REQUEST['new_option_id']]['values'] ) ) {
				foreach( $optionValues[$_REQUEST['new_option_id']]['values'] as $optValId=>$optVal ) {
					$optionValuesList[$optValId] = $optVal['products_options_values_name'];
				}
			} else {
				$optionValuesList[$optionValues[$_REQUEST['new_option_id']]['products_options_values_id']] = $optionValues[$_REQUEST['new_option_id']]['products_options_values_name'];
			}
			$gBitSmarty->loadPlugin( 'smarty_function_html_options' );
			print smarty_function_html_options(array( 'options'			=> $optionValuesList,
														'name'			=> 'newOrderOptionValue',
														'print_result'	=> FALSE ), $gBitSmarty );
			print '<input class="btn btn-small btn-primary" type="submit" value="save" name="save_new_option">';
		} else {
			print "<span class='alert alert-error'>Unkown Option</span>";
		}
	} elseif( !empty( $_REQUEST['address_type'] ) ) {
		$addressType = $_REQUEST['address_type'];
		$entry = $order->$addressType;
		if( isset( $entry['country']['countries_id'] ) ) {
			$countryId =	$entry['country']['countries_id'];
		} elseif( is_string( $entry['country'] ) ) {
			$countryId = zen_get_country_id( $entry['country'] );
		} else {
			$countryId = NULL;
		}
		if( defined( 'ACCOUNT_STATE' ) && ACCOUNT_STATE == 'true' ) {
			$statePullDown = zen_draw_input_field('state', $entry['state'] );
			$gBitSmarty->assign( 'statePullDown', $statePullDown );
		}

		$gBitSmarty->assign( 'countryPullDown', zen_get_country_list('country_id', $countryId ) );
		$gBitSmarty->assign_by_ref( 'address', $entry );
		$gBitSmarty->display( 'bitpackage:bitcommerce/order_address_edit.tpl' );
	} else {
			print "<span class='alert alert-error'>Empty Option</span>";
	}

	exit;
}

require(DIR_FS_ADMIN_INCLUDES . 'header.php');

// Put this after header.php because we have a custom <header> when viewing an order
define('HEADING_TITLE', 'Order'.( (!empty( $_REQUEST['oID'] )) ? ' #'.$_REQUEST['oID'] : 's'));

if( !empty( $order ) ) {
	require( BITCOMMERCE_PKG_PATH.'classes/CommerceProductManager.php' );
	$productManager = new CommerceProductManager();
	$optionsList = $productManager->getOptions();
	$optionsList[0] = "Add new order option...";
	$gBitSmarty->assign_by_ref( 'optionsList', $optionsList );

	$gBitSmarty->assign_by_ref( 'order', $order ); 
	$gBitSmarty->assign_by_ref( 'currencies', $currencies ); 
	if( !empty( $_REQUEST['del_ord_prod_att_id'] ) ) {
		$gBitDb->StartTrans();
		$rs = $gBitDb->query( "DELETE FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " WHERE `orders_products_attributes_id`=? AND `orders_id`=? ", array( $_REQUEST['del_ord_prod_att_id'], $_REQUEST['oID'] ) );
		$gBitDb->CompleteTrans();
		bit_redirect( $_SERVER['SCRIPT_NAME'].'?oID='.$_REQUEST['oID'] );
	}

	if( !empty( $_REQUEST['action'] ) ) {
	switch( $_REQUEST['action'] ) {
		case 'save_new_option':
			$query = "SELECT 
				cpo.`products_options_name` AS products_options,
				cpa.`products_options_values_name` AS products_options_values,
				options_values_price,
				price_prefix,
				product_attribute_is_free,
				products_attributes_wt,
				products_attributes_wt_pfix,
				attributes_discounted,
				attributes_price_base_inc,
				attributes_price_onetime,
				attributes_price_factor,
				attributes_pf_offset,
				attributes_pf_onetime,
				attributes_pf_onetime_offset,
				attributes_qty_prices,
				attributes_qty_prices_onetime,
				attributes_price_words,
				attributes_price_words_free,
				attributes_price_letters,
				attributes_price_letters_free,
				cpo.`products_options_id`,
				products_options_values_id
			FROM " . TABLE_PRODUCTS_OPTIONS . " cpo 
				INNER JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " cpa ON(cpa.products_options_id=cpo.products_options_id) 
			WHERE cpa.`products_options_values_id`=?";
			$newOption = $gBitDb->getRow( $query, array( $_REQUEST['newOrderOptionValue'] ) );
			$newOption['orders_id'] = $_REQUEST['oID'];
			$newOption['orders_products_id'] = $_REQUEST['orders_products_id'];
			$gBitDb->associateInsert( TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $newOption );
			bit_redirect( BITCOMMERCE_PKG_URL.'admin/orders.php?oID='.$_REQUEST['oID'].'&action=edit' );
			break;
		case 'save_address':
			$addressType = $_REQUEST['address_type'];
			$saveAddress[$addressType.'_name'] = $_REQUEST['name'];
			$saveAddress[$addressType.'_company'] = $_REQUEST['company'];
			$saveAddress[$addressType.'_street_address'] = $_REQUEST['street_address'];
			$saveAddress[$addressType.'_suburb'] = $_REQUEST['suburb'];
			$saveAddress[$addressType.'_city'] = $_REQUEST['city'];
			$saveAddress[$addressType.'_state'] = $_REQUEST['state'];
			$saveAddress[$addressType.'_postcode'] = $_REQUEST['postcode'];
			$saveAddress[$addressType.'_country'] = zen_get_country_name( $_REQUEST['country_id'] );
			$saveAddress[$addressType.'_telephone'] = $_REQUEST['telephone'];
			$gBitDb->StartTrans();
			$gBitDb->associateUpdate( TABLE_ORDERS, $saveAddress, array( 'orders_id'=>$_REQUEST['oID'] ) ); 
			$gBitDb->CompleteTrans();
			bit_redirect( $_SERVER['SCRIPT_NAME'].'?oID='.$_REQUEST['oID'] );
			exit;
			break;
		case 'update_order':
			// demo active test
			if (zen_admin_demo()) {
				$_GET['action']= '';
				$messageStack->add_session(ERROR_ADMIN_DEMO, 'caution');
				zen_redirect(zen_href_link_admin(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit', 'SSL'));
			}

			if( $order->updateStatus( $_REQUEST ) ) {
				$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
			} else {
				$messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
			}

			zen_redirect(zen_href_link_admin(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit', 'SSL'));
			break;
		case 'combine':
		if( @BitBase::verifyId( $_REQUEST['combine_order_id'] ) ) {
			$combineOrder = new order( $_REQUEST['combine_order_id'] );
			$combineHash['source_orders_id'] =	$_REQUEST['oID'];
			$combineHash['dest_orders_id'] = $_REQUEST['combine_order_id'];
			$combineHash['combine_notify'] = !empty( $_REQUEST['combine_notify'] );
			if( $combineOrder->combineOrders( $combineHash ) ) {
				bit_redirect( BITCOMMERCE_PKG_URL.'admin/orders.php?action=edit&oID='.$_REQUEST['combine_order_id'] );
			} else {
				print "<span class='error'>".$combineOrder->mErrors['combine']."</span>";
			}
		}
		break;
		case 'delete':
		$formHash['action'] = 'deleteconfirm';
		$formHash['oID'] = $oID;
		$gBitSystem->confirmDialog( $formHash, array( 'warning' => 'Are you sure you want to delete order #'.$oID.'?', 'error' => 'This cannot be undone!' ) );
		break;
		case 'deleteconfirm':
		// demo active test
		if (zen_admin_demo()) {
			$_GET['action']= '';
			$messageStack->add_session(ERROR_ADMIN_DEMO, 'caution');
			zen_redirect(zen_href_link_admin(FILENAME_ORDERS, zen_get_all_get_params(array('oID', 'action')), 'NONSSL'));
		}
		$gBitUser->verifyTicket();
		if( $order->expunge( $_POST['restock'] ) ) {
			bit_redirect( BITCOMMERCE_PKG_URL.'admin/' );
		}
		break;
		default:
		// reset single download to on
		if( !empty( $_REQUEST['ord_prod_att_id'] ) ) {
			
		}
		if( !empty( $_GET['download_reset_on'] ) ) {
			// adjust download_maxdays based on current date
			$check_status = $gBitDb->Execute("select customers_name, customers_email_address, orders_status,
										date_purchased from " . TABLE_ORDERS . "
										where `orders_id` = '" . $_REQUEST['oID'] . "'");
			$zc_max_days = zen_date_diff($check_status->fields['date_purchased'], date('Y-m-d H:i:s', time())) + DOWNLOAD_MAX_DAYS;

			$update_downloads_query = "update " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " set download_maxdays='" . $zc_max_days . "', download_count='" . DOWNLOAD_MAX_COUNT . "' where `orders_id`='" . $_REQUEST['oID'] . "' and orders_products_download_id='" . $_GET['download_reset_on'] . "'";
			$gBitDb->Execute($update_downloads_query);
			unset($_GET['download_reset_on']);

			$messageStack->add_session(SUCCESS_ORDER_UPDATED_DOWNLOAD_ON, 'success');
			zen_redirect(zen_href_link_admin(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit', 'SSL'));
		}
		// reset single download to off
		if( !empty( $_GET['download_reset_off'] ) ) {
			// adjust download_maxdays based on current date
			$update_downloads_query = "update " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " set download_maxdays='0', download_count='0' where `orders_id`='" . $_REQUEST['oID'] . "' and orders_products_download_id='" . $_GET['download_reset_off'] . "'";
			unset($_GET['download_reset_off']);
			$gBitDb->Execute($update_downloads_query);

			$messageStack->add_session(SUCCESS_ORDER_UPDATED_DOWNLOAD_OFF, 'success');
			zen_redirect(zen_href_link_admin(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit', 'SSL'));
		}
		break;
	}
	}
	if( !empty( $_REQUEST['delete_status'] ) ) {
		if( $gBitUser->isAdmin() ) {
			$order->expungeStatus( $_REQUEST['delete_status'] );
			bit_redirect( $_SERVER['SCRIPT_NAME'].'?oID='.$_REQUEST['oID'] );
		}
	}

	// scan fulfillment modules
	$fulfillmentFiles = array();
	$fulfillDir = DIR_FS_MODULES . 'fulfillment/';
	if( is_readable( $fulfillDir ) && $fulfillHandle = opendir( $fulfillDir ) ) {
		while( $ffFile = readdir( $fulfillHandle ) ) {
			if( is_file( $fulfillDir.$ffFile.'/admin_order_inc.php' ) ) {
				$fulfillmentFiles[] = $fulfillDir.$ffFile.'/admin_order_inc.php';
			}
		}
	}
	$gBitSmarty->assign_by_ref( 'fulfillmentFiles', $fulfillmentFiles );

}

$gBitSmarty->assign( 'customerStats', zen_get_customers_stats( $order->customer['id'] ) );

if( $order_exists ) {
	if ($order->info['payment_module_code']) {
		if (file_exists(DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php')) {
			require(DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php');
			$langFile = DIR_FS_CATALOG_LANGUAGES . $gBitCustomer->getLanguage() . '/modules/payment/' . $order->info['payment_module_code'] . '.php';
			if( file_exists( $langFile ) ) {
				require( $langFile );
			}
			if( $module = new $order->info['payment_module_code'] ) {
				if( method_exists( $module, 'admin_notification' ) ) {
					$gBitSmarty->assign( 'notificationBlock', $module->admin_notification($oID) );
				}
			}
		}
	}

	$gBitSmarty->assign( 'isForeignCurrency', !empty( $order->info['currency'] ) && $order->info['currency'] != DEFAULT_CURRENCY );
	$gBitSmarty->assign( 'orderStatuses', commerce_get_statuses( TRUE ) );
	$gBitSmarty->assign( 'customersInterests', CommerceCustomer::getCustomerInterests( $order->customer['id'] ) );

	print '<div class="row">';
	print '<div class="span8">'.$gBitSmarty->fetch( 'bitpackage:bitcommerce/admin_order.tpl' ).'</div>';
	print '<div class="span4">'.$gBitSmarty->fetch( 'bitpackage:bitcommerce/admin_order_status_history_inc.tpl' ).'</div>';
	print '</div>';

	// check if order has open gv
	$gv_check = $gBitDb->query("select `order_id`, `unique_id`
							from " . TABLE_COUPON_GV_QUEUE ."
							where `order_id` = '" . $_REQUEST['oID'] . "' and `release_flag`='N'");
	if ($gv_check->RecordCount() > 0) {
		$goto_gv = '<a href="' . zen_href_link_admin(FILENAME_GV_QUEUE, 'order=' . $_REQUEST['oID']) . '">' . zen_image_button('button_gift_queue.gif',IMAGE_GIFT_QUEUE) . '</a>';
		echo '			<tr><td align="right"><table width="225"><tr>';
		echo '				<td align="center">';
		echo $goto_gv . '&nbsp;&nbsp;';
		echo '				</td>';
		echo '			</tr></table></td></tr>';
	}
	?>
	</td>
</tr>
</table>
<?php

}

require(DIR_FS_ADMIN_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br />
</body>
</html>
<?php require(DIR_FS_ADMIN_INCLUDES . 'application_bottom.php'); ?>
