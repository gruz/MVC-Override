<?php
/*
 * @author Gruz <arygroup@gmail.com>
 * @copyright	Copyleft - All rights reversed
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html

 * */
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
/**
 * Script file
 */
class ScriptAry {
	function __construct() {
		}
	/**
	 * method to install the component
	 *
	 * @return void
	 */
	function install($parent) {
		// $parent is the class calling this method
		//$parent->getParent()->setRedirectURL('index.php?option=com_helloworld');
	}

	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	function uninstall($parent) {
		// $parent is the class calling this method
		//echo '<p>' . JText::_('You may wish to uninstall GJFields library used together with this extension. Other extensions may also use GJFields. If you uninstall GJFields by mistake, you can always reinstall it.') . '</p>';
	}

	/**
	 * method to update the component
	 *
	 * @return void
	 */
	function update($parent) {
		// $parent is the class calling this method
		//echo '<p>' . JText::_('COM_HELLOWORLD_UPDATE_TEXT') . '</p>';

	}



	/**
	 * A small helper class to get extension name from $this class name
	 *
	 * Full description (multiline)
	 *
	 * @author Gruz <arygroup@gmail.com>
	 * @param	type	$name	Description
	 * @return	type			Description
	 */
	static function _getExtensionName() {
		$className = get_called_class();
		preg_match('~(?:plg|mod_)(.*)InstallerScript~Ui',$className,$matches);
		if (isset($matches[1])) {
			return strtolower($matches[1]);
		}
		return false;
	}

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	function preflight($type, $parent) {
		$manifest = $parent->getParent()->getManifest();

		$this->ext_name = $this->_getExtensionName();
		$this->ext_group = (string)$manifest['group'];
		$this->ext_type = (string)$manifest['type'];
		$className = get_called_class();
		$ext = substr($className,0,3);
		switch ($ext) {
			case 'plg':
				$this->ext_full_name = $ext.'_'.$this->ext_group.'_'.$this->ext_name;
				break;
			case 'mod':
			case 'com':
			case 'lib':
			default :
				$this->ext_full_name = $ext.'_'.$this->ext_name;
				break;
		}

		$this->langShortCode = null;//is used for building joomfish links
		$this->default_lang = JComponentHelper::getParams('com_languages')->get('admin');
		$language = JFactory::getLanguage();
		$language->load($this->ext_full_name, dirname(__FILE__), 'en-GB', true);
		$language->load($this->ext_full_name, dirname(__FILE__), $this->default_lang, true);

	}

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight( $type, $parent ) {
		$manifest = $parent->getParent()->getManifest();

		if ($type != 'uninstall') {
			$this->_installExtensions($parent);
		}

		if ($type == 'install' && $this->ext_type == 'plugin') {
			$this->_publishPlugin($this->ext_name,$this->ext_group, $this->ext_full_name);
		}

		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)
		//echo '<p>' . JText::_('COM_HELLOWORLD_POSTFLIGHT_' . $type . '_TEXT') . '</p>';
		if (!empty($this->messages)) {
			echo '<ul><li>'.implode('</li><li>',$this->messages).'</li></ul>';
		}
	}
	private function _publishPlugin($plg_name,$plg_type, $plg_full_name = null) {
		$plugin = JPluginHelper::getPlugin($plg_type, $plg_name);
		$success = true;
		if (empty($plugin)) {

			//get the smallest order value
			$db = jfactory::getdbo();
			// publish plugin
			$query = $db->getquery(true);
			// fields to update.
			$fields = array(
				$db->quotename('enabled').'='.$db->quote('1')
			);
			// conditions for which records should be updated.
			$conditions = array(
				$db->quotename('type').'='.$db->quote('plugin'),
				$db->quotename('folder').'='.$db->quote($plg_type),
				$db->quotename('element').'='.$db->quote($plg_name),
			);
			$query->update($db->quotename('#__extensions'))->set($fields)->where($conditions);
			$db->setquery($query);
			$result = $db->execute();
			$getaffectedrows = $db->getAffectedRows();
			$success = $getaffectedrows;
		}


		if (empty($plg_full_name)) { $plg_full_name = $plg_name; }
		$msg = jtext::_('jglobal_fieldset_publishing').': <b style="color:blue;"> '.JText::_($plg_full_name).'</b> ... ';
		if($success) {
			$msg .= '<b style="color:green">'.jtext::_('jpublished').'</b>';
		}
		else {
			$msg .= '<b style="color:red">'.jtext::_('error').'</b>';
		}
		$this->messages[] = $msg;
	}
	private function _installExtensions ($parent) {
		jimport('joomla.filesystem.folder');
		jimport('joomla.installer.installer');

		JLoader::register('LanguagesModelInstalled', JPATH_ADMINISTRATOR.'/components/com_languages/models/installed.php');
		$lang = new LanguagesModelInstalled();
		$current_languages = $lang ->getData();
		$locales = array();
		foreach($current_languages as $lang) {
			$locales[]=$lang->language;
		}
		$extpath = dirname(__FILE__).'/extensions';
		if (!is_dir($extpath)) {
			return;
		}
		$folders = JFolder::folders ($extpath);
		foreach ($folders as $folder) {
			$folder_temp = explode('_',$folder,2);
			if (isset ($folder_temp[0])) {
				$check_if_language = $folder_temp[0];
				if (preg_match('~[a-z]{2}-[A-Z]{2}~',$check_if_language)) {
					if (!in_array($folder_temp[0],$locales)) {
						continue;
					}
				}

			}

			$installer = new JInstaller();
			if ($installer->install($extpath.'/'.$folder)) {
				$manifest = $installer->getManifest();
				$this->messages[] = JText::sprintf('COM_INSTALLER_INSTALL_SUCCESS','<b style="color:#0055BB;">['.$manifest->name.']<span style="color:green;">').'</span></b>';
			}
			else {
				$this->messages[] = '<span style="color:red;">'.$folder . ' '.JText::_('JERROR_AN_ERROR_HAS_OCCURRED') . '</span>';
			}
		}
	}

}
?>

