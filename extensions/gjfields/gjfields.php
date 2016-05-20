<?php
/**
 * @package     GJFileds
 *
 * @copyright   Copyright (C) All rights reversed.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL or later
 */

// No direct access
defined( '_JEXEC' ) or die();
/**
 * Base class to extend with gjfields fileds
 *
 */
class JFormFieldGJFields extends JFormField {
	function __construct($form = null) {
		parent::__construct($form);
		JHTML::_('behavior.framework', true);
		$app = JFactory::getApplication();
		if (!$app->get($this->type.'_initialized',false)) {
			$app->set($this->type.'_initialized',true);


			$url_to_assets = '/libraries/gjfields/';
			$path_to_assets = JPATH_ROOT.'/libraries/gjfields/';
			$doc = JFactory::getDocument();

			$cssname = $url_to_assets.'css/common.css';
			$cssname_path = $path_to_assets.'css/common.css';
			if (file_exists($cssname_path)) {
				$doc->addStyleSheet($cssname);
			}
			$this->type = JString::strtolower($this->type);

			$cssname = $url_to_assets.'css/'.$this->type.'.css';
			$cssname_path = $path_to_assets.'css/'.$this->type.'.css';
			if (file_exists($cssname_path)) {
				$doc->addStyleSheet($cssname);
			}

			$jversion = new JVersion;
			$common_script = $url_to_assets.'js/script.js?v='.$jversion->RELEASE;
			$doc->addScript($common_script);

			$scriptname = $url_to_assets.'js/'.$this->type.'.js?v='.$this->_getGJFieldsVersion();
			$scriptname_path = $path_to_assets.'js/'.$this->type.'.js';
			if (file_exists($scriptname_path)) {
				$doc->addScript($scriptname);
			}
		}

		$this->HTMLtype = 'div';
		if (JFactory::getApplication()->isAdmin() && JFactory::getApplication()->getTemplate() !== 'isis') {
			$this->HTMLtype = 'li';
		}
		$var_name = basename(__FILE__,'.php').'_HTMLtype';
		if (!$app->get($var_name,false)) {
			$app->set($var_name,true);
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration('var '.$var_name.' = "'.$this->HTMLtype.'";');
			$doc->addScriptDeclaration('var lang_reset = "'.JText::_('JSEARCH_RESET').'?";');
		}

	}

	function getInput() {
	}
	function def( $val, $default = '' )	{
		return ( isset( $this->element[$val] ) && (string) $this->element[$val] != '' ) ? (string) $this->element[$val] : $default;
	}

	static function _getGJFieldsVersion () {
		$gjfields_version = file_get_contents(dirname(__FILE__).'/gjfields.xml');
		preg_match('~<version>(.*)</version>~Ui',$gjfields_version,$gjfields_version);
		$gjfields_version = $gjfields_version[1];
		return $gjfields_version;
	}

}
