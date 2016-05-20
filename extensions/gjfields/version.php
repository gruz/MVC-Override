<?php
/**
 * @package     GJFileds
 *
 * @copyright   Copyright (C) All rights reversed.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL or later
 */

// No direct access
defined('_JEXEC') or die;

	// For Joomla 1.6
	jimport('joomla.form.formfield');

class JFormFieldNN_Version extends JFormField 	{
	/**
	 * The form field type
	 *
	 * @var		string
	 */
	public $type = 'Version';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()	{

		$return = '';

		if(version_compare(JVERSION,'3.0','ge')) {
			// Add CSS and JS once, define base global flag - runc only once
			if (!isset($GLOBALS[$this->type.'_initialized'])) {
				$path_to_assets = JURI::root().'libraries/gjfields/';
				/*
				$GLOBALS[$this->type.'_initialized'] = true;
				// It's my development need. If I use the same file for developing J2.5 and J3.0 I cannot properly determine the path, so I assume it's a default one (else)
				if (strpos(__DIR__,JPATH_SITE)) {
					$baseurl = str_replace('administrator/','',JURI::base());
					$path_to_assets = JPath::clean(str_replace($baseurl,'',$baseurl . str_replace(JPATH_SITE,'',__DIR__).'/'));
				}
				else {
					$path_to_assets = JURI::root().'libraries/gjfields/';

				}
				*/

				$scriptname = $path_to_assets.'js/'.JString::strtolower($this->type).'.js';
				$doc = JFactory::getDocument();
				$doc->addScript($scriptname);
			}
			$return .=  '<input type="hidden" name="' . $this->name
			//. '" id="' . $this->id
			. '" value="'
			. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' .  ' />'; // This input is used to store active tab position


		}

		$xml = $this->def('xml');

		// Load language
		$jinput = JFactory::getApplication()->input;
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName(array('element','folder','type')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('extension_id').'='.$db->Quote($jinput->get('extension_id',null)));
		$db->setQuery($query,0,1);
		$row = $db->loadAssoc();
		if ($row['type'] == 'plugin') {
			$this->plg_full_name = 'plg_'.$row['folder'].'_'.$row['element'];
			$this->langShortCode = null;//is used for building joomfish links
			$this->default_lang = JComponentHelper::getParams('com_languages')->get('admin');
			$language = JFactory::getLanguage();
			$language->load($this->plg_full_name, JPATH_ROOT.dirname( $xml), 'en-GB', true);
			$language->load($this->plg_full_name, JPATH_ROOT.dirname( $xml), $this->default_lang, true);
		}

		$extension = $this->def('extension');

		$user = JFactory::getUser();
		$authorise = $user->authorise('core.manage', 'com_installer');

		if (!JString::strlen($extension) || !JString::strlen($xml) || !$authorise) {
			return;
		}

		$version = '';
		if ($xml) {
			$xml = JApplicationHelper::parseXMLInstallFile(JPATH_SITE.'/'.$xml);
			if ($xml && isset($xml['version'])) {
				$version = $xml['version'];
			}
		}

		$document = JFactory::getDocument();
		$css = '';
		$css .= ".version {display:block;text-align:right;color:brown;font-size:10px;}";
		$css .= ".readonly.plg-desc {font-weight:normal;}";
		$css .= "fieldset.radio label {width:auto;}";
		$document->addStyleDeclaration($css);


		$return .= '<span class="version">'.JText::_('JVERSION').' '.$version."</span>";

		return $return;
	}


	private function def( $val, $default = '' )	{
		return ( isset( $this->element[$val] ) && (string) $this->element[$val] != '' ) ? (string) $this->element[$val] : $default;
	}
}
