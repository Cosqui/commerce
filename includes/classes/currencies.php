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
// $Id: currencies.php,v 1.10 2005/08/24 08:45:57 lsces Exp $
//

////
// Class to handle currencies
// TABLES: currencies
  class currencies extends BitBase {
    var $currencies;

// class constructor
    function currencies() {
      global $db;
	  BitBase::BitBase();
      $this->currencies = array();
      $currencies_query = "SELECT `code`, `title`, `symbol_left`, `symbol_right`, `decimal_point`,
                                  `thousands_point`, `decimal_places`, `value`
                          FROM " . TABLE_CURRENCIES;

      $currencies = $db->Execute($currencies_query);

      while (!$currencies->EOF) {
        $this->currencies[$currencies->fields['code']] = array('title' => $currencies->fields['title'],
                                                       'symbol_left' => $currencies->fields['symbol_left'],
                                                       'symbol_right' => $currencies->fields['symbol_right'],
                                                       'decimal_point' => $currencies->fields['decimal_point'],
                                                       'thousands_point' => $currencies->fields['thousands_point'],
                                                       'decimal_places' => $currencies->fields['decimal_places'],
                                                       'value' => $currencies->fields['value']);

      $currencies->MoveNext();
      }
    }

    function formatAddTax( $pPrice, $pTax ) {
		$this->format( zen_add_tax( $pPrice, $pTax  ) );
	}

// class methods
    function format($number, $calculate_currency_value = true, $currency_type = '', $currency_value = '') {

		if (empty($currency_type)) {
			$currency_type = !empty( $_SESSION['currency'] ) && !empty( $this->currencies[ $_SESSION['currency']] ) ? $_SESSION['currency'] : DEFAULT_CURRENCY;
		}

		if ($calculate_currency_value == true) {
			$rate = (zen_not_null($currency_value)) ? $currency_value : $this->currencies[$currency_type]['value'];
			$format_string = $this->currencies[$currency_type]['symbol_left'] . number_format(zen_round($number * $rate, $this->currencies[$currency_type]['decimal_places']), $this->currencies[$currency_type]['decimal_places'], $this->currencies[$currency_type]['decimal_point'], $this->currencies[$currency_type]['thousands_point']) . $this->currencies[$currency_type]['symbol_right'];
		} else {
			$format_string = $this->currencies[$currency_type]['symbol_left'] . number_format(zen_round($number, $this->currencies[$currency_type]['decimal_places']), $this->currencies[$currency_type]['decimal_places'], $this->currencies[$currency_type]['decimal_point'], $this->currencies[$currency_type]['thousands_point']) . $this->currencies[$currency_type]['symbol_right'];
		}

		if (DOWN_FOR_MAINTENANCE=='true' and DOWN_FOR_MAINTENANCE_PRICES_OFF=='true') {
			$format_string= '';
		}

		return ' '.$format_string;
    }

    function value($number, $calculate_currency_value = true, $currency_type = '', $currency_value = '') {

      if (empty($currency_type)) $currency_type = $_SESSION['currency'];

      if ($calculate_currency_value == true) {
        if ($currency_type == DEFAULT_CURRENCY) {
          $rate = (zen_not_null($currency_value)) ? $currency_value : 1/$this->currencies[$_SESSION['currency']]['value'];
        } else {
          $rate = (zen_not_null($currency_value)) ? $currency_value : $this->currencies[$currency_type]['value'];
        }
        $currency_value = zen_round($number * $rate, $this->currencies[$currency_type]['decimal_places']);
      } else {
        $currency_value = zen_round($number, $this->currencies[$currency_type]['decimal_places']);
      }

      return $currency_value;
    }

    function is_set($code) {
      if (isset($this->currencies[$code]) && zen_not_null($this->currencies[$code])) {
        return true;
      } else {
        return false;
      }
    }

    function get_value($code) {
      return $this->currencies[$code]['value'];
    }

    function get_decimal_places($code) {
      return $this->currencies[$code]['decimal_places'];
    }

    function display_price($products_price, $products_tax, $quantity = 1) {
      return $this->format(zen_add_tax($products_price, $products_tax) * $quantity);
    }

	function verify( &$pParamHash ) {
		if( empty( $pParamHash['code'] ) ) {
			$this->mErrors = tra( 'A currency code is required' );
		} else {
			$pParamHash['currency_store']['code'] = $pParamHash['code'];
		}
		$pParamHash['currency_store']['decimal_places'] = ( !empty( $pParamHash['decimal_places'] ) ? $pParamHash['decimal_places'] : 2 );
		$pParamHash['currency_store']['decimal_point'] = ( !empty( $pParamHash['decimal_point'] ) ? $pParamHash['decimal_point'] : '.' );
		$pParamHash['currency_store']['thousands_point'] = ( !empty( $pParamHash['thousands_point'] ) ? $pParamHash['thousands_point'] : ',' );

		if( empty( $pParamHash['symbol_left'] ) && empty( $pParamHash['symbol_right'] ) ) {
			$pParamHash['currency_store']['symbol_right'] = $pParamHash['code'];
		}
		if( !empty( $pParamHash['symbol_left'] ) ) {
			$pParamHash['currency_store']['symbol_left'] = $pParamHash['symbol_left'];
		}
		if( !empty( $pParamHash['symbol_right'] ) ) {
			$pParamHash['currency_store']['symbol_right'] = $pParamHash['symbol_right'];
		}
		if( !empty( $pParamHash['title'] ) ) {
			$pParamHash['currency_store']['title'] = trim( $pParamHash['title'] );
		}
		$pParamHash['currency_store']['value'] = ( !empty( $pParamHash['value'] ) ? $pParamHash['value'] : 1 );
		$pParamHash['currency_store']['last_updated'] = $this->mDb->sysTimeStamp;

		return( count( $this->mErrors ) == 0 );
	}

	function store( &$pParamHash ) {
		if( $this->verify( $pParamHash ) ) {
			if( $currenciesId = $this->currencyExists( $pParamHash['currency_store']['code'] ) ) {
				$this->mDb->associateUpdate( TABLE_CURRENCIES, $pParamHash['currency_store'], array( 'name' => 'currencies_id', 'value'=>$currenciesId ) );
			} else {
				$this->mDb->associateInsert( TABLE_CURRENCIES, $pParamHash['currency_store'] );
				$currenciesId = zen_db_insert_id( TABLE_CURRENCIES, 'currencies_id' );
			}
		}
	}

	function currencyExists( $code ) {
		return $this->mDb->getOne( "select `currencies_id` from " . TABLE_CURRENCIES . " where `code` = ?", array( $code ) );
	}

	function bulkImport( $pBulkString ) {
		$lines = explode( "\n", $pBulkString );
		if( count( $lines ) ) {
			foreach( $lines as $line ) {
				$currValues = array();
				preg_match( '/([A-Z]{3}) ([\w]+[\w ]*) [ ]+([\d\.]+)[ ]+([\d\.]+)/', $line, $currValues );
				if( count( $currValues ) > 1 ) {
					$currHash['code'] = $currValues[1];
					$currHash['title'] = $currValues[2];
					$currHash['value'] = $currValues[4];
					$this->store( $currHash );
				}
			}
		}
		sscanf( $pBulkString, "\n" );
	}
  }
?>