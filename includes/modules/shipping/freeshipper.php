<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 The zen-cart developers                           |
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
// $Id$
//
  class freeshipper {
    var $code, $title, $description, $icon, $enabled;

// class constructor
    function freeshipper() {
      global $order, $gBitDb;
      $this->code = 'freeshipper';
			if( defined( 'MODULE_SHIPPING_FREESHIPPER_STATUS' ) ) {
				$this->title = MODULE_SHIPPING_FREESHIPPER_TEXT_TITLE;
				$this->description = MODULE_SHIPPING_FREESHIPPER_TEXT_DESCRIPTION;
				$this->sort_order = MODULE_SHIPPING_FREESHIPPER_SORT_ORDER;
				$this->icon = '';
				$this->tax_class = MODULE_SHIPPING_FREESHIPPER_TAX_CLASS;
				$this->enabled = ((MODULE_SHIPPING_FREESHIPPER_STATUS == 'True') ? true : false);
			}

      if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_FREESHIPPER_ZONE > 0) ) {
        $check_flag = false;
        $check = $gBitDb->Execute("select `zone_id` from " . TABLE_ZONES_TO_GEO_ZONES . " where `geo_zone_id` = '" . MODULE_SHIPPING_FREESHIPPER_ZONE . "' and `zone_country_id` = '" . $order->delivery['country']['countries_id'] . "' order by `zone_id`");
        while (!$check->EOF) {
          if ($check->fields['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check->fields['zone_id'] == $order->delivery['zone_id']) {
            $check_flag = true;
            break;
          }
          $check->MoveNext();
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

// class methods
    function quote( $pShipHash = array() ) {
      global $order;

      $this->quotes = array('id' => $this->code,
                            'module' => MODULE_SHIPPING_FREESHIPPER_TEXT_TITLE,
                            'methods' => array(array('id' => $this->code,
                                                     'title' => MODULE_SHIPPING_FREESHIPPER_TEXT_WAY,
                                                     'cost' => MODULE_SHIPPING_FREESHIPPER_COST + MODULE_SHIPPING_FREESHIPPER_HANDLING)));

      if ($this->tax_class > 0) {
        $this->quotes['tax'] = zen_get_tax_rate($this->tax_class, $order->delivery['country']['countries_id'], $order->delivery['zone_id']);
      }

		if (zen_not_null($this->icon)) {
			$this->quotes['icon'] = $this->icon;
		}

      return $this->quotes;
    }

    function check() {
      global $gBitDb;
      if (!isset($this->_check)) {
        $check_query = $gBitDb->Execute("select `configuration_value` from " . TABLE_CONFIGURATION . " where `configuration_key` = 'MODULE_SHIPPING_FREESHIPPER_STATUS'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;
    }

    function install() {
      global $gBitDb;
      $gBitDb->Execute("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `set_function`, `date_added`) values ('Enable Free Shipping', 'MODULE_SHIPPING_FREESHIPPER_STATUS', 'True', 'Do you want to offer Free shipping?', '6', '0', 'zen_cfg_select_option(array(''True'', ''False''), ', now())");
      $gBitDb->Execute("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `date_added`) values ('Free Shipping Cost', 'MODULE_SHIPPING_FREESHIPPER_COST', '0.00', 'What is the Shipping cost?', '6', '6', now())");
      $gBitDb->Execute("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `date_added`) values ('Handling Fee', 'MODULE_SHIPPING_FREESHIPPER_HANDLING', '0', 'Handling fee for this shipping method.', '6', '0', now())");
      $gBitDb->Execute("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `use_function`, `set_function`, `date_added`) values ('Tax Class', 'MODULE_SHIPPING_FREESHIPPER_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '0', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");
      $gBitDb->Execute("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `use_function`, `set_function`, `date_added`) values ('Shipping Zone', 'MODULE_SHIPPING_FREESHIPPER_ZONE', '0', 'If a zone is selected, only enable this shipping method for that zone.', '6', '0', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
      $gBitDb->Execute("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `date_added`) values ('Sort Order', 'MODULE_SHIPPING_FREESHIPPER_SORT_ORDER', '0', 'Sort order of display.', '6', '0', now())");
    }

    function remove() {
      global $gBitDb;
      $gBitDb->Execute("delete from " . TABLE_CONFIGURATION . " where `configuration_key` in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_SHIPPING_FREESHIPPER_STATUS', 'MODULE_SHIPPING_FREESHIPPER_COST', 'MODULE_SHIPPING_FREESHIPPER_HANDLING', 'MODULE_SHIPPING_FREESHIPPER_TAX_CLASS', 'MODULE_SHIPPING_FREESHIPPER_ZONE', 'MODULE_SHIPPING_FREESHIPPER_SORT_ORDER');
    }
  }
?>
