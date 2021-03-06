<?php
// +----------------------------------------------------------------------+
// | bitcommerce Open Source E-commerce                                   |
// | Copyright (c) 2009 bitcommerce.org                                   |
// | http://www.bitcommerce.org/                                          |
// | This source file is subject to version 2.0 of the GPL license        |
// +----------------------------------------------------------------------+
//  $Id$
require('includes/application_top.php');
require_once( BITCOMMERCE_PKG_PATH.'classes/CommerceOrder.php');

require( BITCOMMERCE_PKG_PATH.'classes/CommerceShipping.php');
$shipping = new CommerceShipping();
$order->calculate();
// get all available shipping quotes

if( !empty( $_REQUEST['change_shipping'] ) && !empty( $_REQUEST['shipping'] ) ) {
	list($module, $method) = explode('_', $_REQUEST['shipping']);
	if ( is_object($$module) ) {
		$quote = $shipping->quote( $order->getWeight(), $method, $module);
		$order->changeShipping( current( $quote ), $_REQUEST );
		zen_redirect( $_SERVER['HTTP_REFERER'] );
	}
} else {
	$gBitSmarty->assign( 'quotes', $shipping->quote( $order->getWeight() ) );
	print $gBitSmarty->fetch( 'bitpackage:bitcommerce/admin_shipping_change_ajax.tpl' );
}

?>
