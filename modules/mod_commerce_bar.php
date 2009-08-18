<?php
//
// +----------------------------------------------------------------------+
// | bitcommerce                                                          |
// +----------------------------------------------------------------------+
// | Copyright (c) 2007 bitcommerce.org                                   |
// |                                                                      |
// | http://www.bitcommerce.org                                           |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license        |
// +----------------------------------------------------------------------+
//  $Id: mod_commerce_bar.php,v 1.2 2009/08/18 20:45:50 spiderr Exp $
//
	global $gBitDb, $gBitProduct, $currencies, $gBitUser, $gBitCustomer;

	require_once( BITCOMMERCE_PKG_PATH.'includes/bitcommerce_start_inc.php' );
	if( !empty( $gBitCustomer->mCart ) && is_object( $gBitCustomer->mCart ) && $gBitCustomer->mCart->count_contents() > 0 ) {
		$gBitSmarty->assign_by_ref( 'sessionCart', $gBitCustomer->mCart );
	}

?>
