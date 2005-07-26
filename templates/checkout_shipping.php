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
// $Id: checkout_shipping.php,v 1.6 2005/07/26 12:31:56 spiderr Exp $
//
  require(DIR_WS_CLASSES . 'http_client.php');

  global $gBitCustomer;

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
  }

// if the order contains only virtual products, forward the customer to the billing page as
// a shipping address is not needed
  if ($order->content_type == 'virtual') {
    $_SESSION['shipping'] = false;
    $_SESSION['sendto'] = false;
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

// if the customer is not logged on, redirect them to the login page
  if (!$_SESSION['customer_id']) {
    $_SESSION['navigation']->set_snapshot();
  }

// Validate Cart for checkout
  $_SESSION['valid_to_checkout'] = true;
  $_SESSION['cart']->get_products(true);
  if ($_SESSION['valid_to_checkout'] == false) {
    $messageStack->add('header', 'Please update your order ...', 'error');
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
  }

// Stock Check
  if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $_SESSION['cart']->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (zen_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
        zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
        break;
      }
    }
  }

	// if no shipping destination address was selected, use the customers own address as default
	if( empty( $_SESSION['sendto'] ) ) {
		if( empty( $_SESSION['customer_default_address_id'] ) ) {
			$_SESSION['customer_default_address_id'] = $gBitCustomer->getDefaultAddress();
		}
		$_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
	} elseif ( !$gBitCustomer->isAddressOwner( $_SESSION['sendto'] ) ) {
			$_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
			$_SESSION['shipping'] = '';
	}

//vd( $_SESSION );
  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;
$gBitSmarty->assign_by_ref( 'order', $order );

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
  $_SESSION['cartID'] = $_SESSION['cart']->cartID;

// if the order contains only virtual products, forward the customer to the billing page as
// a shipping address is not needed
	if ($order->content_type == 'virtual') {
		$_SESSION['shipping'] = 'free_free';
		$_SESSION['shipping']['title'] = 'free_free';
		$_SESSION['sendto'] = false;
		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
	}

  $total_weight = $_SESSION['cart']->show_weight();
  $total_count = $_SESSION['cart']->count_contents();

// load all enabled shipping modules
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;

  if ( defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') ) {
    $pass = false;

    switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
      case 'national':
        if ($order->delivery['country_id'] == STORE_COUNTRY) {
          $pass = true;
        }
        break;
      case 'international':
        if ($order->delivery['country_id'] != STORE_COUNTRY) {
          $pass = true;
        }
        break;
      case 'both':
        $pass = true;
        break;
    }

    $free_shipping = false;
    if ( ($pass == true) && ($_SESSION['cart']->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) ) {
      $free_shipping = true;
    }
  } else {
    $free_shipping = false;
  }

  require(DIR_WS_MODULES . 'require_languages.php');

// process the selected shipping method
	if( !empty( $_REQUEST['submit_address'] ) ) {
		if( !$gBitUser->isRegistered() ) {
			if( $gBitCustomer->register( $_REQUEST ) ) {
			}
		}
		if( empty( $_REQUEST['address'] ) || (zen_not_null( $_REQUEST['firstname'] ) && zen_not_null( $_REQUEST['lastname'] ) && zen_not_null( $_REQUEST['street_address'] )) ) {
			if( $gBitUser->isRegistered() ) {
				$_REQUEST['customers_id'] = $gBitUser->mUserId;
			}
			if( $gBitCustomer->storeAddress( $_REQUEST ) ) {
				$_SESSION['sendto'] = $_REQUEST['address'];
				zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
			} else {
				$gBitSmarty->assign( 'address', $_REQUEST['address_store'] );
				$gBitSmarty->assign_by_ref( 'errors', $gBitCustomer->mErrors );
			}
		} elseif( !empty( $_REQUEST['address'] ) ) {
			$_SESSION['shipping'] = $_REQUEST['address'];
			$reset_shipping = false;
			if ($_SESSION['sendto']) {
				if ($_SESSION['sendto'] != $_REQUEST['address']) {
					if ($_SESSION['shipping']) {
						$reset_shipping = true;
					}
				}
			}

			if( $gBitCustomer->isAddressOwner( $_REQUEST['address'] ) ) {
				if ($reset_shipping == true) {
					$_SESSION['shipping']; // WTF!? dunno what was supposed to be here. - spiderr
				}
				if( empty( $_SESSION['customer_default_address_id'] ) ) {
					$_SESSION['customer_default_address_id'] = $_REQUEST['address'];
				}
				if( !$gBitCustomer->getDefaultAddress() ) {
					$gBitCustomer->setDefaultAddress( $_REQUEST['address'] );
				}
				zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
			} else {
				$_SESSION['sendto'] = '';
			}
		} elseif( !empty( $_SESSION['customer_default_address_id'] ) ) {
			$_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
//			zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
		}
	} elseif( !$gBitUser->isRegistered() ) {
	} elseif( isset($_POST['action']) && ($_POST['action'] == 'process') ) {
		if (zen_not_null($_POST['comments'])) {
			$_SESSION['comments'] = zen_db_prepare_input($_POST['comments']);
		}
		$comments = $_SESSION['comments'];

		if ( (zen_count_shipping_modules() > 0) || ($free_shipping == true) ) {
			if ( (isset($_POST['shipping'])) && (strpos($_POST['shipping'], '_')) ) {
				$_SESSION['shipping'] = $_POST['shipping'];

				list($module, $method) = explode('_', $_SESSION['shipping']);
				if ( is_object($$module) || ($_SESSION['shipping'] == 'free_free') ) {
				if ($_SESSION['shipping'] == 'free_free') {
					$quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
					$quote[0]['methods'][0]['cost'] = '0';
				} else {
					$quote = $shipping_modules->quote($method, $module);
				}
				if (isset($quote['error'])) {
					$_SESSION['shipping'] = '';
				} else {
					if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
					$_SESSION['shipping'] = array('id' => $_SESSION['shipping'],
										'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
										'cost' => $quote[0]['methods'][0]['cost']);

					zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
					}
				}
				} else {
				$_SESSION['shipping'] = false;
				}
			}
		} else {
			$_SESSION['shipping'] = false;
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
		}
	}

		if( $_REQUEST['change_address'] || !$gBitCustomer->isValidAddress( $order->delivery ) ) {
			if( $addresses = CommerceCustomer::getAddresses( $_SESSION['customer_id'] ) ) {
				$gBitSmarty->assign( 'addresses', $addresses );
			}
			$gBitSmarty->assign( 'changeAddress', TRUE );
		} else {
			// get all available shipping quotes
			$quotes = $shipping_modules->quote();
			// if no shipping method has been selected, automatically select the cheapest method.
			// if the modules status was changed when none were available, to save on implementing
			// a javascript force-selection method, also automatically select the cheapest shipping
			// method if more than one module is now enabled
			if ( !$_SESSION['shipping'] || ( $_SESSION['shipping'] && ($_SESSION['shipping'] == false) && (zen_count_shipping_modules() > 1) ) ) {
				$_SESSION['shipping'] = $shipping_modules->cheapest();
			}


			$breadcrumb->add(NAVBAR_TITLE_1, zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
			$breadcrumb->add(NAVBAR_TITLE_2);

			if( count( $quotes ) ) {
				foreach( array_keys( $quotes ) as $i ) {
					if( count( $quotes[$i]['methods'] ) ) {
						foreach( array_keys( $quotes[$i]['methods'] ) as $j ) {
							$quotes[$i]['methods'][$j]['format_add_tax'] = $currencies->format(zen_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0)));
						}
					}
				}
			}

			$gBitSmarty->assign( 'shippingModules', zen_count_shipping_modules() );
			$gBitSmarty->assign_by_ref( 'quotes', $quotes );
			$gBitSmarty->register_object('currencies', $currencies, array(), true, array('formatAddTax'));
			$gBitSmarty->assign( 'freeShipping', $free_shipping );
			$gBitSmarty->assign( 'sessionShippingId', $_SESSION['shipping'] );
		}

  print $gBitSmarty->fetch( 'bitpackage:bitcommerce/checkout_shipping.tpl' );
?>