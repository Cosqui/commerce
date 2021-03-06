<?php

require_once( KERNEL_PKG_PATH.'BitSingleton.php' );

class CommerceSystem extends BitSingleton {
	public $mConfig = array();
	public $mProductTypeLayout = array();

	function __construct() {
		parent::__construct();
		$this->loadConfig();
	}

    public function __wakeup() {
		parent::__wakeup();
		$this->loadConstants();
	}

	public function __sleep() {
		return array_merge( parent::__sleep(), array( 'mConfig', 'mProductTypeLayout' ) );
	}

	private function loadConstants() {
		foreach( $this->mConfig AS $key=>$value ) {
			define($key, $value );
		}

		foreach( $this->mProductTypeLayout AS $key=>$value ) {
			define($key, $value );
		}
    }

	public function storeConfigId ( $pConfigId, $pConfigValue ) {
		$configKey = $this->mDb->getOne( 'SELECT `configuration_key` FROM ' . TABLE_CONFIGURATION . ' WHERE `configuration_id`=? ', array( $pConfigId ) );
		$this->storeConfig( $configKey, $pConfigValue );
	}

	public function storeConfig ( $pConfigKey, $pConfigValue ) {
		if( is_array( $pConfigValue ) ){
			// see usage in UPS and USPS
			$pConfigValue = implode( ", ", $pConfigValue );
			$pConfigValue = str_replace ( ", --none--", "", $pConfigValue );
		}

		if( $pConfigValue !== NULL ) {
			if( isset( $this->mConfig[$pConfigKey] ) ) {
				$this->mDb->query( "UPDATE " . TABLE_CONFIGURATION . " SET `configuration_value` = ?, `last_modified`='NOW' WHERE `configuration_key` = ?", array( $pConfigValue, $pConfigKey ) );
			} else {
				$defaultTitle = ucwords( strtolower( str_replace( '_', ' ', preg_replace( '/MODULE_[A-Z]*_/', '', $pConfigKey ) ) ) );
				$this->mDb->query( "INSERT INTO " . TABLE_CONFIGURATION . " ( `configuration_value`, `configuration_key`, `configuration_title` ) VALUES ( ?, ?, ? )", array( $pConfigValue, $pConfigKey, $defaultTitle ) );
			}
		} else {
			$this->mDb->query( "DELETE FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` = ?", array( $pConfigKey ) );
		}
		$this->mConfig[$pConfigKey] = $pConfigValue;
		$this->clearFromCache();
	}

	function loadConfig() {
		$this->mConfig = $this->mDb->getAssoc( 'SELECT `configuration_key` AS `cfgkey`, `configuration_value` AS `cfgvalue` FROM ' . TABLE_CONFIGURATION ); 
		$this->mProductTypeLayout = $this->mDb->getAssoc( 'select `configuration_key` as `cfgkey`, `configuration_value` as `cfgvalue` from ' . TABLE_PRODUCT_TYPE_LAYOUT );
		$this->loadConstants();
	}

	function getConfig( $pConfigName, $pDefault=NULL ) {
		global $gBitSystem;
		$ret = $pDefault;
		if( defined( strtoupper( $pConfigName ) ) ) {
			$ret = constant( strtoupper( $pConfigName ) );
		} elseif( $pDefault === NULL && strpos( 'MAX_DISPLAY', $pConfigName ) !== FALSE ) {
			$ret = $gBitSystem->getConfig( 'max_records', 20 );
		} else {
			$ret = $gBitSystem->getConfig( strtolower( $pConfigName ), $pDefault );
		}
		return $ret;
	}

	function isConfigLoaded( $pConfigName ) {
		return isset( $this->mConfig[$pConfigName] );
	}

	static function isConfigDefined( $pConfigName ) {
		return defined( $pConfigName );
	}

	static function isConfigActive( $pConfigName ) {
		return ((defined( $pConfigName ) && strtolower( constant( $pConfigName ) ) == 'true') ? true : false);
	}

	function setHeadingTitle( $pTitle ) {
		if( !defined( 'HEADING_TITLE' ) ) {
			define( 'HEADING_TITLE', $pTitle );
		}
	}
}

