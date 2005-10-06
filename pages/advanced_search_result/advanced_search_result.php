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
// $Id: advanced_search_result.php,v 1.1 2005/10/06 19:38:27 spiderr Exp $
//
?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td class="pageHeading"><h1><?php echo HEADING_TITLE; ?></h1></td>
  </tr>
  <tr>
    <td class="main">
<?php
  require (DIR_WS_BLOCKS . 'blk_advanced_search_result.php');
?>
    </td>
  </tr>
  <tr>
    <td class="main"><?php echo '<a href="' . zen_href_link(FILENAME_ADVANCED_SEARCH, zen_get_all_get_params(array('sort', 'page', 'x', 'y')), 'NONSSL', true, false) . '">' . zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) . '</a>'; ?></td>
  </tr>
</table>