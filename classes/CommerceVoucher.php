<?php
// :vim:tabstop=4:
// +--------------------------------------------------------------------+
// | Copyright (c) 2005-2010 bitcommerce.org							|
// | http://www.bitcommerce.org											|
// | This source file is subject to version 2.0 of the GPL license		|
// +--------------------------------------------------------------------+
// | Portions Copyright (c) 2003 The zen-cart developers				|
// | Portions Copyright (c) 2003 osCommerce								|	
// +--------------------------------------------------------------------+
//

require_once( KERNEL_PKG_PATH.'BitBase.php' );

class CommerceVoucher extends BitBase {
	public $pCategoryId;

	function __construct( $pCouponId=NULL ) {
		$this->mCouponId = $pCouponId;
		parent::__construct();
	}

	function load( $pCode=NULL, $pOnlyIfActive=NULL ) {
		$this->mInfo = array();
		if( !empty( $pCode ) || $this->isValid() ) {
			$error = true;
			$bindVars[] = $_SESSION['languages_id'];
			if( !empty( $pCode ) ) {
				$lookup = 'UPPER(cc.`coupon_code`)';
				$bindVars[] =  strtoupper( $pCode );
			} else {
				$lookup = 'cc.`coupon_id`';
				$bindVars[] = $this->mCouponId;
			}

			$query = "SELECT ccd.*,cc.*
					  FROM " . TABLE_COUPONS . " cc
						LEFT OUTER JOIN " . TABLE_COUPONS_DESCRIPTION . " ccd ON (cc.`coupon_id`=ccd.`coupon_id` AND ccd.`language_id`=?)
					  WHERE $lookup=?";
			if( $pOnlyIfActive ) {
				$query .= " AND coupon_active='Y' ";
			}
			if( ($this->mInfo = $this->mDb->getRow( $query, $bindVars )) ) {
				$this->mCouponId = $this->mInfo['coupon_id'];
				$bindVars = array( $_SESSION['languages_id'], $_SESSION['languages_id'], $this->mCouponId );
				$query = "SELECT `restrict_id` AS `hash_key`, ccr.*, cpd.`products_name`, ccatd.`categories_name`, cpa.`products_options_values_name`, cpt.`type_name` 
						  FROM " . TABLE_COUPON_RESTRICT . " ccr
							LEFT OUTER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " cpd ON (ccr.`product_id`=cpd.`products_id` AND cpd.`language_id`=?)
							LEFT OUTER JOIN " . TABLE_CATEGORIES_DESCRIPTION . " ccatd ON (ccr.`category_id`=ccatd.`categories_id` AND ccatd.`language_id`=?)
							LEFT OUTER JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " cpa ON (ccr.`products_options_values_id`=cpa.`products_options_values_id`)
							LEFT OUTER JOIN " . TABLE_PRODUCT_TYPES . " cpt ON (ccr.`product_type_id`=cpt.`type_id`)
						  WHERE `coupon_id` = ?";
				$this->mRestrictions = $this->mDb->getAssoc( $query, $bindVars );
			}
		}
		return( count( $this->mInfo ) );
	}

	function expunge() {
		if( $this->isValid() ) {
			$this->StartTrans();
			$query = "DELETE FROM " . TABLE_COUPON_RESTRICT . " WHERE `coupon_id`=?";
			$this->mDb->query( $query, array( $this->mCouponId ) );
			$query = "DELETE FROM " . TABLE_COUPON_EMAIL_TRACK . " WHERE `coupon_id`=?";
			$this->mDb->query( $query, array( $this->mCouponId ) );
			$query = "DELETE FROM " . TABLE_COUPONS . " WHERE `coupon_id`=?";
			$this->mDb->query( $query, array( $this->mCouponId ) );
			$this->CompleteTrans();
		}
	}

	function verify( &$pParamHash ) {

		if( empty( $pParamHash['coupon_id'] ) && empty( $pParamHash['coupon_code'] ) ) {
			$pParamHash['coupon_store']['coupon_code'] = CommerceVoucher::generateCouponCode();
		} else {
			if( $existingId = $this->mDb->getOne( "SELECT `coupon_id` FROM " . TABLE_COUPONS . " WHERE UPPER( `coupon_code` )=?", array( strtoupper( $pParamHash['coupon_code'] ) ) ) ) {
				if( !empty( $pParamHash['coupon_id'] ) && ($pParamHash['coupon_id'] != $existingId) ) {
					$this->mFeedback['error'][] = ERROR_COUPON_EXISTS;
				}
			}
			$pParamHash['coupon_store']['coupon_code'] = trim($pParamHash['coupon_code']);
		}

		if( empty( $pParamHash['coupon_name'] ) ) {
			$this->mFeedback['error'][] = 'No coupon name entered';
		} else {
			$languages = zen_get_languages();
			for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
				$languageId = $languages[$i]['id'];
				if( empty( $pParamHash['coupon_name'][$languageId] ) ) {
					$this->mFeedback['error'][] = 'No coupon name entered' . $languages[$i]['name'];
				} else {
					$pParamHash['voucher_lang_store'][$languageId]['coupon_name'] = trim($pParamHash['coupon_name'][$languageId]);
					$pParamHash['voucher_lang_store'][$languageId]['coupon_description'] = trim($pParamHash['coupon_description'][$languageId]);
				}
			}
		}


		foreach( array( 'coupon_amount', 'uses_per_coupon', 'uses_per_user', 'coupon_minimum_order', 'quantity_max' ) as $field ) {
			$pParamHash['coupon_store'][$field] = !empty( $pParamHash[$field] ) ? trim( preg_replace('/[^0-9\.\%]/', '', $pParamHash[$field] ) ) : NULL;
		}

		if ( empty( $pParamHash['coupon_store']['coupon_amount'] ) && empty( $pParamHash['coupon_free_ship'] ) ) {
			$this->mFeedback['error'][] = ERROR_NO_COUPON_AMOUNT;
		} else {
			$pParamHash['coupon_store']['free_ship'] = !empty( $pParamHash['free_ship'] ) ? 'y' : NULL;
			if( strpos( $pParamHash['coupon_store']['coupon_amount'], '%' ) !== FALSE ) {
				$pParamHash['coupon_store']['coupon_amount'] = str_replace( '%', '', $pParamHash['coupon_store']['coupon_amount'] );
				$pParamHash['coupon_store']['coupon_type'] = 'P';
			} elseif( !empty( $pParamHash['coupon_type'] ) ) {
				$pParamHash['coupon_store']['coupon_type'] = $pParamHash['coupon_type'];
			} else {
				$pParamHash['coupon_store']['coupon_type'] = 'F';
			}
		}

		foreach( array( 'restrict_to_shipping', 'restrict_to_products', 'restrict_to_categories', 'coupon_start_date', 'coupon_expire_date', 'admin_note' ) as $field ) {
			$pParamHash['coupon_store'][$field] = !empty( $pParamHash[$field] ) ? trim( $pParamHash[$field] ) : NULL;
		}

		$pParamHash['coupon_store']['date_modified'] = $this->mDb->NOW();

		if( !empty( $pParamHash['coupon_start_date'] ) ) {
			$pParamHash['coupon_store']['coupon_start_date'] = $pParamHash['coupon_start_date'];
		} elseif( !empty( $pParamHash['coupon_start_date_month'] ) ) {
			// Assume bitweaver Y-M-D input select
			$pParamHash['coupon_store']['coupon_start_date'] = date('m/d/Y', mktime(0, 0, 0, $pParamHash['coupon_start_date_month'],$pParamHash['coupon_start_date_day'] ,$pParamHash['coupon_start_date_year'] ));
		}

		if( !empty( $pParamHash['coupon_expire_date'] ) ) {
			$pParamHash['coupon_store']['coupon_expire_date'] = $pParamHash['coupon_expire_date'];
		} elseif( !empty( $pParamHash['coupon_expire_date_month'] ) ) {
			// Assume bitweaver Y-M-D input select
			$pParamHash['coupon_store']['coupon_expire_date'] = date('m/d/Y', mktime(0, 0, 0, $pParamHash['coupon_expire_date_month'],$pParamHash['coupon_expire_date_day'] ,$pParamHash['coupon_expire_date_year'] ));
		}

		return( empty( $this->mFeedback['error'] ) );
	}

	function store( &$pParamHash ) {
		if( $this->verify( $pParamHash ) ) {
			$this->StartTrans();
			$languages = zen_get_languages();

			if( empty( $pParamHash['coupon_id'] ) ) {
				$pParamHash['coupon_store']['date_created'] = $this->mDb->NOW();
				$this->mDb->associateInsert( TABLE_COUPONS, $pParamHash['coupon_store'] );
				$this->mCouponId = zen_db_insert_id( TABLE_COUPONS, 'coupon_id' );
				$pParamHash['coupon_store']['coupon_id'] = $this->mCouponId;

				for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
					$languageId = $languages[$i]['id'];
					$pParamHash['voucher_lang_store'][$languageId]['coupon_id'] = $pParamHash['coupon_store']['coupon_id'];
					$pParamHash['voucher_lang_store'][$languageId]['language_id'] = $languageId;
					$this->mDb->associateInsert(TABLE_COUPONS_DESCRIPTION, $pParamHash['voucher_lang_store'][$languageId]);
				}
			} else {
				$this->mDb->associateUpdate( TABLE_COUPONS, $pParamHash['coupon_store'], array( 'coupon_id' => $pParamHash['coupon_id'] ) );
				for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
					$languageId = $languages[$i]['id'];
					$this->mDb->query( "UPDATE " . TABLE_COUPONS_DESCRIPTION . " SET `coupon_name`=?, `coupon_description`=?  WHERE `coupon_id`=? AND `language_id`=?", 
						array( $pParamHash['voucher_lang_store'][$languageId]['coupon_name'],  $pParamHash['voucher_lang_store'][$languageId]['coupon_description'], $pParamHash['coupon_id'], $languageId ) );
				}
			}
			$this->CompleteTrans();
			$this->load();
		}
		return( empty( $this->mFeedback['error'] ) );
	}

	function storeRestriction( $pParamHash ) {
		if( $this->isValid() ) {
			$whereSql = '';
			$columns = array( 'coupon_id' => $this->mCouponId );
			$bindVars = array( $this->mCouponId );

			foreach( array( 'category_id', 'product_id', 'product_type_id', 'products_options_values_id' ) as $col ) {
				if( !empty( $pParamHash[$col] ) && BitBase::verifyId( $pParamHash[$col] ) ) {
					$whereSql .= ' AND `'.$col.'`=?';
					$bindVars[] = $pParamHash[$col];
					$columns[$col] = $pParamHash[$col];
				}
			}

			if( !$this->mDb->getOne( "SELECT `restrict_id` FROM " . TABLE_COUPON_RESTRICT . " WHERE coupon_id = ? $whereSql", $bindVars ) ) {
				if( $pParamHash['restrict_status'] == 'Allow' ) {
					$columns['coupon_restrict'] = 'N';
				} elseif( $pParamHash['restrict_status'] == 'Maybe' ) {
					$columns['coupon_restrict'] = 'O';
				} else {
					$columns['coupon_restrict'] = 'Y';
				}
				$this->mDb->AssociateInsert( TABLE_COUPON_RESTRICT, $columns );
			}
		}
	}
/*
	case 'switch_status':
		$status = $gBitDb->getOne( "SELECT coupon_restrict FROM " . TABLE_COUPON_RESTRICT . " WHERE restrict_id = ?", array( $_GET['info'] ) );
		$new_status = ($status == 'N') ? 'Y' : 'N'; 
		$gBitDb->query( "UPDATE " . TABLE_COUPON_RESTRICT . " SET coupon_restrict = ? WHERE restrict_id = ?", array( $new_status, $_GET['info'] ) );
		break;
	case 'remove':
		if( !empty( $_GET['info'] ) ) {
			$gBitDb->query("delete from " . TABLE_COUPON_RESTRICT . " where restrict_id = ?", array( $_GET['info'] ) );
		}
		break;

*/

	function isValid() {
		return $this->verifyId( $this->mCouponId );
	}

	////
	// Create a Coupon Code. length may be between 1 and 16 Characters
	// $salt needs some thought.

	function generateCouponCode( $salt="secret", $length = SECURITY_CODE_LENGTH ) {
		global $gBitDb;
		$ccid = md5(uniqid("","salt"));
		$ccid .= md5(uniqid("","salt"));
		$ccid .= md5(uniqid("","salt"));
		$ccid .= md5(uniqid("","salt"));
		srand((double)microtime()*1000000); // seed the random number generator
		$random_start = @rand(0, (128-$length));
		do {
			$code = substr($ccid, $random_start,$length);
			$query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_code = ?";
			$exists = $gBitDb->getOne( $query, array( $code ) );
		} while( $exists );
		return $code;
	}

	function isRedeemable() {
		if( $this->isValid() ) {
			$couponStart = $this->mDb->getOne("SELECT `coupon_start_date` FROM " . TABLE_COUPONS . " WHERE `coupon_start_date` <= NOW() AND `coupon_id`=?", array( $this->mCouponId ) );
			if ( !$couponStart ) {
				$this->mErrors['redeem_error'] = TEXT_INVALID_STARTDATE_COUPON;
			}

			$isExpired = $this->mDb->getOne("SELECT `coupon_expire_date` FROM " . TABLE_COUPONS . " WHERE `coupon_expire_date` < NOW() AND `coupon_id`=?", array( $this->mCouponId ) );
			if ( $isExpired ) {
				$this->mErrors['redeem_error'] = 'This coupon has expired.';
			}

			if( $this->getField( 'uses_per_coupon' ) ) {
				$query = "SELECT COUNT( `coupon_id` ) from " . TABLE_COUPON_REDEEM_TRACK . " WHERE coupon_id=?";
				$redeemCount = $this->mDb->getOne( $query, array( $this->mCouponId ) );
				if( $redeemCount >= $this->getField( 'uses_per_coupon' ) ) {
					$this->mErrors['redeem_error'] = 'This coupon has been used the maximum number of times allowed.';
				}
			}

			if ( $this->getField( 'uses_per_user' ) ) {
				// don't block cancelled/refunded orders
				$query = "SELECT COUNT( `coupon_id` ) AS `redeem_count` 
						  FROM " . TABLE_COUPON_REDEEM_TRACK . " ccrt
							LEFT JOIN " . TABLE_ORDERS . " co ON (ccrt.`order_id`=co.`orders_id`)
						  WHERE coupon_id = ? AND customer_id = ? AND `orders_status` > 0 ";
				$redeemCount = $this->mDb->getOne( $query, array( $this->mCouponId, $_SESSION['customer_id'] ) );
				if ( $redeemCount >= $this->getField('uses_per_user') && $this->getField('uses_per_user') > 0) {
					$this->mErrors['redeem_error'] = 'You have used coupon code the maximum number of times allowed per customer.';
				}
			}
		} else {
			$this->mErrors['redeem_error'] = "Invalid coupon code";
		}

		return( count( $this->mErrors ) === 0 );
	}

	function getGiftAmount( $pFormat=TRUE ) {
		global $gBitUser, $gBitDb, $currencies;
		$ret = NULL;
		if( $gBitUser->isRegistered() ) {
			$gv_query = "select `amount`
						from " . TABLE_COUPON_GV_CUSTOMER . "
						where `customer_id` = ?";
			if( ($ret = $gBitDb->getOne($gv_query, array( $gBitUser->mUserId ) )) && $pFormat ) {
				$ret = $currencies->format( $ret );
			}
		}
		return $ret;
	}

	function getCouponAmount( $pFormat=TRUE ) {
		global $gBitDb, $currencies;
		$ret = NULL;
		if( !empty( $_SESSION['gv_id'] ) ) {
			$gv_query = "SELECT `coupon_amount` FROM " . TABLE_COUPONS . " WHERE `coupon_id` = ?";
			if( ($ret = $gBitDb->getOne($gv_query, array( $_SESSION['gv_id'] ) )) && $pFormat ) {
				$ret = $currencies->format( $ret );
			}
		}
		return $ret;
	}

	// This method is very similar to customerSendCoupon, however is done via
	// admin or automated means. Eventually these two functions should be
	// merged/simplified
	function adminSendCoupon( $pParamHash ) {
		global $gBitUser, $gBitCustomer, $gCommerceSystem, $gBitSystem, $currencies;

		require_once( BITCOMMERCE_PKG_PATH. 'admin/'. DIR_WS_LANGUAGES . $gBitCustomer->getLanguage() . '/gv_mail.php' );
		require_once( BITCOMMERCE_PKG_PATH. 'admin/'. DIR_WS_LANGUAGES . $gBitCustomer->getLanguage() . '/email_extras.php' );
		$ret = FALSE;
		if( !empty( $pParamHash['email_to'] ) && !empty( $pParamHash['amount'] ) )  {
			$this->StartTrans();

			if( empty( $pParamHash['from'] ) ) {
				$pParamHash['from'] = EMAIL_FROM;
			}
			$adminNote = !empty( $pParamHash['admin_note'] ) ? $pParamHash['admin_note'] : NULL;

			$from = zen_db_prepare_input( $pParamHash['from'] );
			if( empty( $pParamHash['subject'] ) ) {
				$pParamHash['subject'] = $gBitSystem->getConfig( 'site_title' ).' '.tra( "Gift Certificate" );
			}
			$subject = zen_db_prepare_input( $pParamHash['subject'] );
			$mailSentTo = $pParamHash['email_to'];
			$id1 = $this->generateCouponCode( $pParamHash['email_to'] );
			if( empty( $pParamHash['message'] ) ) {
				$pParamHash['message'] = trim( stripslashes( strip_tags( TEXT_GV_ANNOUNCE ) ) );
			}
			$message = zen_db_prepare_input($pParamHash['message']);
			$html_msg['GV_MESSAGE'] = $message;
			$message .= "\n\n" . TEXT_GV_WORTH  . $currencies->format($pParamHash['amount']) . "\n\n";
			$message .= TEXT_TO_REDEEM;
			$message .= TEXT_WHICH_IS . ' ' . $id1 . ' ' . TEXT_IN_CASE . "\n\n";

			$html_msg['GV_AMOUNT']  = $currencies->format($pParamHash['amount']);
			$html_msg['GV_CODE']  = $id1; 
			$html_msg['GV_WORTH']  = TEXT_GV_WORTH  . $currencies->format($pParamHash['amount']) .'<br />';
			$html_msg['GV_REDEEM'] = TEXT_TO_REDEEM . TEXT_WHICH_IS . ' <strong>' . $id1 . '</strong> ' . TEXT_IN_CASE . '<p>' . TEXT_OR_VISIT .  '<a href="'.HTTP_SERVER  . DIR_WS_CATALOG.'">' . STORE_NAME . '</a>' . TEXT_ENTER_CODE . '</p>';

			
			if ( 0 && $gCommerceSystem->isConfigActive( 'SEARCH_ENGINE_FRIENDLY_URLS' ) ) { // TODO Fix
				$html_msg['GV_CODE_URL']  = HTTP_SERVER . DIR_WS_CATALOG . 'index.php/gv_redeem/gv_no/'.$id1;
				$html_msg['GV_CODE_LINK'] = '<a href="'.HTTP_SERVER . DIR_WS_CATALOG . 'index.php/gv_redeem/gv_no/'.$id1.'">' .TEXT_CLICK_TO_REDEEM . '</a>';
			} else {
				$html_msg['GV_CODE_URL'] = HTTP_SERVER . DIR_WS_CATALOG . 'index.php?main_page=gv_redeem&gv_no='.$id1;
				$html_msg['GV_CODE_LINK']  =  '<a href="'. HTTP_SERVER . DIR_WS_CATALOG . 'index.php?main_page=gv_redeem&gv_no='.$id1 .'">' .TEXT_CLICK_TO_REDEEM . '</a>';
			}

			$message .= $html_msg['GV_CODE_URL'] . "\n\n" . TEXT_OR_VISIT . HTTP_SERVER  . DIR_WS_CATALOG  . TEXT_ENTER_CODE . "\n\n";
			$html_msg['EMAIL_MESSAGE_HTML'] = !empty( $pParamHash['message_html'] ) ? zen_db_prepare_input($pParamHash['message_html']) : '';
			$html_msg['EMAIL_FIRST_NAME'] = ''; // unknown, since only an email address was supplied
			$html_msg['EMAIL_LAST_NAME']  = ''; // unknown, since only an email address was supplied
			// disclaimer
			$message .= "\n-----\n" . sprintf(EMAIL_DISCLAIMER, STORE_OWNER_EMAIL_ADDRESS) . "\n\n";

			// Now create the coupon main entry
			$this->mDb->query("insert into " . TABLE_COUPONS . " (coupon_code, coupon_type, coupon_amount, date_created, admin_note) values ( ?, 'G', ?, now(), ?)", array( $id1, $pParamHash['amount'], $adminNote ) );
			$insert_id = zen_db_insert_id( TABLE_COUPONS, 'coupon_id' );
			$this->mDb->query( "INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ( ?, '0', ?, ?, now() )", array( $insert_id, $gBitUser->getDisplayName(), $pParamHash['email_to'] ) );

			// Send the emails
			zen_mail( '', $pParamHash['email_to'], $subject , $message, $from, $from, $html_msg, 'gv_mail' );
			if (SEND_EXTRA_DISCOUNT_COUPON_ADMIN_EMAILS_TO_STATUS== '1' and SEND_EXTRA_DISCOUNT_COUPON_ADMIN_EMAILS_TO != '') {
				zen_mail('', SEND_EXTRA_DISCOUNT_COUPON_ADMIN_EMAILS_TO, SEND_EXTRA_DISCOUNT_COUPON_ADMIN_EMAILS_TO_SUBJECT . ' ' . $subject, $message, $from, $from, $html_msg, 'gv_mail_extra');
			}

			if( !empty( $pParamHash['oID'] ) ) {
				$order = new order( $pParamHash['oID'] );
				$status['comments'] = 'A $'.$pParamHash['amount'].' Gift Certificate ( '.$id1.' ) was emailed to '.$pParamHash['email_to'].' in relation to order '.$pParamHash['oID'].'';
				$order->updateStatus( $status );
			}
			$ret = TRUE;
			$this->CompleteTrans();
		}
		return $ret;
	}

	function customerSendCoupon( $pFromUser, $pRecipient, $pAmount ) {
		global $gBitDb, $gBitSmarty, $gCommerceSystem, $currencies;
		$ret = NULL;

		$gBitDb->StartTrans();

		$code = CommerceVoucher::generateCouponCode();
		$gvBalance = CommerceVoucher::getGiftAmount( FALSE );
		$newBalance = $gvBalance - $pAmount;
		if ($new_amount < 0) {
			$error = ERROR_ENTRY_AMOUNT_CHECK;
		} else {
			$gv_query = "UPDATE " . TABLE_COUPON_GV_CUSTOMER . "
						 SET `amount` = ?
						 WHERE `customer_id` = ?";
			$gBitDb->query( $gv_query, array( $newBalance, $pFromUser->mUserId ) );

			$gv_query = "INSERT INTO " . TABLE_COUPONS . " (`coupon_type`, `coupon_code`, `date_created`, `coupon_amount`) values ('G', ?, NOW(), ?)";
			$gv = $gBitDb->query($gv_query, array( $code, $pAmount ) );
			$gvId = zen_db_insert_id( TABLE_COUPONS, 'coupon_id' );

			$gv_query="insert into " . TABLE_COUPON_EMAIL_TRACK . "	(`coupon_id`, `customer_id_sent`, `emailed_to`, `date_sent`)
						values ( ?, ?, ?, now())";
			$gBitDb->query( $gv_query, array( $gvId, $pFromUser->mUserId, $pRecipient['email'] ) );
			$ret = $code;

			$gv_email_subject = tra( 'A gift from' ).' '.$pFromUser->getDisplayName().' '.tra( 'to' ).' '.$gCommerceSystem->getConfig( 'STORE_NAME' );

			$gBitSmarty->assign( 'gvCode', $code );
			$gBitSmarty->assign( 'gvSender', $pFromUser->getDisplayName() );
			$gBitSmarty->assign( 'gvAmount', $currencies->format( $pAmount, false ) );
			$gBitSmarty->assign( 'gvRedeemUrl', BITCOMMERCE_PKG_URI.'index.php?main_page=gv_redeem&gv_no='.$code );
			if( !empty( $pRecipient['message'] ) ) {
				$gBitSmarty->assign( 'gvMessage', $pRecipient['message'] );
			}

			$textMessage = $gBitSmarty->fetch( 'bitpackage:bitcommerce/gv_send_email_text.tpl' );
			$htmlMessage = $gBitSmarty->fetch( 'bitpackage:bitcommerce/gv_send_email_html.tpl' );

		// send the email
			zen_mail('', $pRecipient['email'], $gv_email_subject, $textMessage, STORE_NAME, EMAIL_FROM, $htmlMessage,'gv_send');

		// send additional emails
			if (SEND_EXTRA_GV_CUSTOMER_EMAILS_TO_STATUS == '1' and SEND_EXTRA_GV_CUSTOMER_EMAILS_TO !='') {
				zen_mail('', SEND_EXTRA_GV_CUSTOMER_EMAILS_TO, $gv_email_subject, $textMessage, STORE_NAME, EMAIL_FROM, $htmlMessage,'gv_send');
				if ($_SESSION['customer_id']) {
					$account_query = "select `customers_firstname`, `customers_lastname`, `customers_email_address`
										from " . TABLE_CUSTOMERS . "
										where `customers_id` = '" . (int)$_SESSION['customer_id'] . "'";

					$account = $gBitDb->Execute($account_query);
				}
				$extra_info=email_collect_extra_info($pRecipient['to_name'],$pRecipient['email'], $pFromUser->getDisplayName() , $pFromUser->getField( 'email' ) );
				$html_msg['EXTRA_INFO'] = $gCommerceSystem->getConfig('TEXT_GV_NAME').' Code: '.$code.'<br/>'.$extra_info['HTML'];
				zen_mail('', SEND_EXTRA_GV_CUSTOMER_EMAILS_TO, tra( '[GV CUSTOMER SENT]' ). ' ' . $gv_email_subject,
					$gCommerceSystem->getConfig('TEXT_GV_NAME').' Code: '.$code."\n".$gv_email . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg,'gv_send_extra');
			}


		}
		$gBitDb->CompleteTrans();
		return $ret;
	}

	function redeemCoupon( $pCustomerId, $pCode ) {
		global $gBitDb;
		$ret = FALSE;
		if( !empty( $pCode ) ) {
			$error = true;

			$gv_query = "SELECT `coupon_id`, `coupon_amount` FROM " . TABLE_COUPONS . " WHERE `coupon_code` = ? AND `coupon_active`='Y'";
			if( $coupon = $gBitDb->getRow($gv_query, array( $pCode ) ) ) {
				$redeem_query = "SELECT coupon_id FROM ". TABLE_COUPON_REDEEM_TRACK . " WHERE coupon_id = ?";
				$isRedeemed = $gBitDb->getOne( $redeem_query, array( $coupon['coupon_id'] ) );
				if( !$isRedeemed ) {
			// check for required session variables
					$_SESSION['gv_id'] = $coupon['coupon_id'];
					$error = false;
				}
			}
			if( !$error ) {
			// Update redeem status
				$gBitDb->StartTrans();
				$gv_query = "insert into  " . TABLE_COUPON_REDEEM_TRACK . " (coupon_id, customer_id, redeem_date, redeem_ip)
							values ( ?, ?, now(), ? )";
				$gBitDb->query($gv_query, array( $coupon['coupon_id'], $pCustomerId, $_SERVER['REMOTE_ADDR'] ) );

				$gv_update = "update " . TABLE_COUPONS . "
							set coupon_active = 'N'
							where coupon_id = ?";
				$gBitDb->query($gv_update, array( $coupon['coupon_id'] ) );

				CommerceVoucher::updateCustomerBalance( $pCustomerId, $_SESSION['gv_id'] );
				$gBitDb->CompleteTrans();
				$ret = $coupon['coupon_amount'];
			}
		}
		return $ret;
	}

	function updateCustomerBalance( $pCustomerId, $pCouponId ) {
		global $gBitDb;
		if( BitBase::verifyId( $pCustomerId ) ) {
			$query = "SELECT customer_id, amount FROM " . TABLE_COUPON_GV_CUSTOMER . " WHERE `customer_id` = ?";
			$customerBalance = $gBitDb->getRow( $query, array( $pCustomerId ) );

			$coupon_gv_query = "SELECT coupon_amount FROM " . TABLE_COUPONS . " WHERE coupon_id = ?";
			$couponAmount = $gBitDb->getOne($coupon_gv_query, array( $pCouponId ) );

			if( !empty( $customerBalance['customer_id'] ) ) {
				$newAmount = $customerBalance['amount'] + $couponAmount;
				$gv_query = "UPDATE " . TABLE_COUPON_GV_CUSTOMER . " SET amount = ? WHERE `customer_id` = ?";
				$gBitDb->query($gv_query, array( $newAmount, $pCustomerId ) );
			} else {
				$gv_query = "INSERT INTO " . TABLE_COUPON_GV_CUSTOMER . " (customer_id, amount) VALUES (?,?)";
				$gBitDb->query($gv_query, array( $pCustomerId, $couponAmount ) );
			}
		}
	}

	public static function getList( &$pListHash ) {
		global $gBitDb;

		$whereSql = '';
		$bindVars = array( $_SESSION['languages_id'] );
		$ret = array();

		if( !empty( $pListHash['coupon_type'] ) ) {
			$whereSql .= ' WHERE cc.`coupon_active`=? ';
			$bindVars = $pListHash['status'];
		} else {
			// By default all non-gift coupons
			$whereSql .= " WHERE `coupon_type` != 'G' "; 
		}

		if( !empty( $pListHash['uses'] ) ) {
			if( is_numeric( $pListHash['uses'] ) ) {
				$whereSql .= ' AND cc.`uses_per_coupon`=? ';
				$bindVars[] = $pListHash['uses'];
			} elseif( $pListHash['uses'] == '+' ) {
				$whereSql .= ' AND cc.`uses_per_coupon`>1 ';
			}
		}

		if( !empty( $pListHash['status'] ) ) {
			$whereSql .= ' AND cc.`coupon_active`=? ';
			$bindVars[] = $pListHash['status'];
		}

		if( empty ( $_REQUEST['sort_mode'] ) ) {
			$_REQUEST['sort_mode'] = 'coupon_start_date_desc';
		}
		BitBase::prepGetList( $pListHash );

/*
coupon_minimum_order  
uses_per_coupon       
uses_per_user         
restrict_to_products  
restrict_to_categories
restrict_to_customers 
date_created          
date_modified         
restrict_to_shipping  
restrict_to_quantity
*/

		$sql = "SELECT cc.`coupon_id`, cc.`coupon_code`, cc.`coupon_type`, cc.`coupon_amount`, cc.`coupon_start_date`, cc.`coupon_expire_date`, cc.`uses_per_coupon`, cc.`uses_per_user`, cc.`coupon_active`, cc.`admin_note`, ccd.`coupon_name`, ccd.`coupon_description`, cc.`free_ship`,
					MAX( `redeem_date` ) AS `redeemed_first_date`, 
					MIN( `redeem_date` ) AS `redeemed_last_date`, 
					COALESCE( SUM( cot.`orders_value` ), 0 ) AS `redeemed_sum`, 
					COALESCE( COUNT( cot.`orders_value` ), 0 ) AS `redeemed_count`, 
					COALESCE( SUM( cot2.`orders_value` ), 0 ) AS `redeemed_revenue`
				FROM " . TABLE_COUPONS . " cc
					LEFT OUTER JOIN " . TABLE_COUPONS_DESCRIPTION . " ccd ON (cc.`coupon_id`=ccd.`coupon_id` AND ccd.`language_id`=?)
					LEFT OUTER JOIN  " . TABLE_COUPON_REDEEM_TRACK . " ccrt ON (ccrt.`coupon_id`=cc.`coupon_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."users_users` uu ON (ccrt.`customer_id`=uu.`user_id`)
					LEFT OUTER JOIN " . TABLE_ORDERS_TOTAL . " cot ON (ccrt.`order_id`=cot.`orders_id` AND cot.`class`='ot_coupon' AND UPPER(cot.`title`) LIKE '%'||UPPER(cc.`coupon_code`)||'%')
					LEFT OUTER JOIN " . TABLE_ORDERS_TOTAL . " cot2 ON (ccrt.`order_id`=cot2.`orders_id` AND cot2.`class`='ot_total' )
				$whereSql
				GROUP BY cc.`coupon_id`, cc.`coupon_code`, cc.`coupon_type`, cc.`coupon_amount`, cc.`coupon_start_date`, cc.`coupon_expire_date`, cc.`uses_per_coupon`, cc.`uses_per_user`, cc.`coupon_active`, cc.`admin_note`, ccd.`coupon_name`, ccd.`coupon_description`, cc.`free_ship`, cc.`coupon_start_date`
				ORDER BY ".$gBitDb->convertSortmode( $_REQUEST['sort_mode'] );
		if( $rs = $gBitDb->query( $sql, $bindVars, $pListHash['max_records'], $pListHash['offset'] ) ) {
			while( $row = $rs->fetchRow() ) {
				$ret[$row['coupon_id']] = $row;
				$ret[$row['coupon_id']]['restrictions_count'] = $gBitDb->getOne( "SELECT COUNT(ccr.`coupon_id`) AS `restrictions_count` FROM " . TABLE_COUPON_RESTRICT . " ccr WHERE `coupon_id`=?", array( $row['coupon_id'] ) );
			}
			$pListHash['cant'] = $gBitDb->getOne( "SELECT COUNT(cc.`coupon_id`) FROM " . TABLE_COUPONS . " cc LEFT OUTER JOIN " . TABLE_COUPONS_DESCRIPTION . " ccd ON (cc.`coupon_id`=ccd.`coupon_id` AND ccd.`language_id`=?) $whereSql ", $bindVars );
		} else {
			$pListHash['cant'] = 0;
		}

		BitBase::postGetList( $pListHash );

		if( empty ( $_REQUEST['listInfo']['sort_mode'] ) ) {
			$_REQUEST['listInfo']['sort_mode'] = 'coupon_start_date_asc';
		}
		return $ret;
	}
}

?>
