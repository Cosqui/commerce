<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//  $Id: footer.php,v 1.5 2008/06/25 22:21:07 spiderr Exp $
//

$obcontent = ob_get_contents();
$gBitSmarty->assign_by_ref( 'bitcommerceAdmin', $obcontent );
ob_end_clean();

$gBitSystem->mLayout = array( 1 );

$gBitSystem->display( 'bitpackage:bitcommerce/admin_bitcommerce.tpl', tra( 'Admin' ).' '.ucfirst( BITCOMMERCE_PKG_DIR ).' : '.HEADING_TITLE , array( 'display_mode' => 'admin' ));

?>