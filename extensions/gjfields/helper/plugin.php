<?php
/**
 * A wrapper class to extend common joomla class with GJFields methods
 *
 * @package		GJFields
 * @author Gruz <arygroup@gmail.com>
 * @copyright	Copyleft - All rights reversed
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

class JPluginGJFields extends JPlugin {

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		$jinput = JFactory::getApplication()->input;
		if ($jinput->get('option',null) == 'com_dump') { return; }


		// Load languge for frontend
		$this->plg_name = $config['name'];
		$this->plg_type = $config['type'];
		$this->plg_full_name = 'plg_'.$config['type'].'_'.$config['name'];
		$this->langShortCode = null;//is used for building joomfish links
		$this->default_lang = JComponentHelper::getParams('com_languages')->get('site');
		$language = JFactory::getLanguage();
		$this->plg_path = JPATH_PLUGINS.'/'.$this->plg_type.'/'.$this->plg_name.'/';

		$language->load($this->plg_full_name, $this->plg_path, 'en-GB', true);
		$language->load($this->plg_full_name, $this->plg_path, $this->default_lang, true);

	}

	/**
	 * Determines if the current plugin has been just saved or applied and stores result into $this->pluginHasBeenSavedOrApplied
	 *
	 * The call of the function must be placed into __construct  or at least onAfterRoute.
	 * When saving a plugin Joomla uses a redirect. So onAfterRoute (and surely __construct) is run twice,
	 * while onAfterRender and futher functions only once (..onAfterRoute - redirect - ..onAfterRoute->onAfterRender)
	 * At the first onAfterRoute I can get such variables as task and jform to determine what action is performed.
	 * At the secon onAfterRoute the variables are empty, as they have been used before the redirect.
	 * So I check the needed variables to determin if the plugin is saved and enabled before the redirect onAfterRoute and store
	 * some flags to the session.
	 * After the redirect I get the flags from the session, clear the session not to run the plugin twice and run the main function body.
	 *
	 * @author Gruz <arygroup@gmail.com>
	 * @param	type	$name	Description
	 * @return	type			Description
	 */
	protected function _preparePluginHasBeenSavedOrAppliedFlag () {
		$jinput = JFactory::getApplication()->input;
		if ($jinput->get('option',null) == 'com_dump') { return; }

		//CHECK IF THE PLUGIN WAS JUST SAVED AND STORE A FLAG TO SESSION
		$jinput = JFactory::getApplication()->input;
		$this->pluginHasBeenSavedOrApplied = false;

		$session = JFactory::getSession();
		$option = $jinput->get('option',null);
		$task = $jinput->get('task',null);
		if ($option == 'com_plugins' && in_array ($task,array('plugin.save','plugin.apply'))) {
			// If the plugin which is saved is our current plugin and it's enabled
			$session = JFactory::getSession();
			$jform = $jinput->post->get('jform',null,'array');
			if(isset($jform['element']) && $jform['element'] == $this->plg_name && isset($jform['folder']) && $jform['folder'] == $this->plg_type) {
				if ($jform['enabled'] == '0') {
					//unset ($_SESSION[$this->plg_full_name]);
					$session->clear($this->plg_full_name);
				}
				else {
					$data = new stdClass;
					$data->runPlugin = true;
					$session->set($this->plg_full_name, $data);
				}
			}
		}
		else {
			$sessionInfo = $session->get($this->plg_full_name,array());
			$session->clear($this->plg_full_name);
			if (empty($sessionInfo) || empty($sessionInfo->runPlugin)) {	return; } // If we do not have to run plugin - joomla is not saving the plugin
			else {$this->pluginHasBeenSavedOrApplied = $sessionInfo->runPlugin; }
		}
	}


	/**
	 * Parses parameters of gjfileds (variablefileds) into a convinient arrays
	 *
	 * @author Gruz <arygroup@gmail.com>
	 * @param	string	$group_name	Name of the group in the XML file
	 * @return	type			Description
	 */
	function getGroupParams ($group_name) {
		$jinput = JFactory::getApplication()->input;
		if ($jinput->get('option',null) == 'com_dump') { return; }

		if (!isset($GLOBALS[$this->plg_name]['variable_group_name'][$group_name])) {
			$GLOBALS[$this->plg_name]['variable_group_name'][$group_name] = true;
		}
		else {
			return;
		}

		// Get defauls values from XML {
		$group_name_start = $group_name;
		$group_name_end = str_replace('{','',$group_name).'}';
		$xmlfile = $this->plg_path.'/'.$this->plg_name.'.xml';
		$xml = simplexml_load_file($xmlfile);
		//unset ($xml->scriptfile);
		$field = 'field';
		$xpath = 'config/fields/fieldset';

		$started = false;
		$defaults = array();
		foreach ($xml->xpath('//'.$xpath.'/'.$field) as $f) {
			$field_name = (string)$f['name'];
			if ($field_name == $group_name_start)  { $started = true; continue; }
			if (!$started) { continue; }
			if ($f['basetype'] == 'toggler') { continue; }
			if ($f['basetype'] == 'blockquote') { continue; }
			if ($f['basetype'] == 'note') { continue; }
			$defaults[$field_name] = '';
			$def = (string)$f['default'];
			if (!empty($def)) {
				$defaults[$field_name] = $def;
			} else if ($def == 0)	{
				$defaults[$field_name] = $def;
			}
			if ($field_name == $group_name_end)  { break; }
		}
		// Get defauls values from XML }

		// Get all parameters
		$params = $this->params->toObject();
		$pparams = array();
		/*
		if (empty($params->{$group_name})) {
			$override_parameters = array (
				'ruleEnabled'=>$this->paramGet('ruleEnabled'),
				'menuname'=>$this->paramGet('menuname'),
				'show_articles'=>$this->paramGet('show_articles'),
				'categories'=>$this->paramGet('categories'),
				'regeneratemenu'=>$this->paramGet('regeneratemenu')
			);
			$pparams[] = $override_parameters;
		}
		*/
		if (empty($params->{$group_name})) {
			$params->{$group_name} = array();
		}
		$pparams_temp  = $params->{$group_name};
		foreach ($pparams_temp as $fieldname=>$values) {
			$group_number = 0;
			$values = (array) $values;
			foreach ($values as $n=>$value) {
				if ($value == 'variablefield::'.$group_name) {
					$group_number++;
				}
				else if (is_array($value) && $value[0] == 'variablefield::'.$group_name){
					if (!isset($pparams[$group_number][$fieldname])) {
						$pparams[$group_number][$fieldname] = array();
					}
					$group_number++;
				}
				else if (is_array($value) ) {
					$pparams[$group_number][$fieldname][] = $value[0];
				}
				else if ( $fieldname == $group_name ) {
					$pparams[$group_number][$fieldname][] = $value;
				}
				else {
					$pparams[$group_number][$fieldname] = $value;
				}
			}
		}
		// Update params with default values if there are no stored in the DB. Usefull when adding a new XML field and a user don't resave settings {
		foreach ($pparams as $param_key=>$param) {
			foreach ($defaults as $k=>$v) {
				if (!isset($param[$k])) {
					$pparams[$param_key][$k] = $v;
				}
			}
		}
		// Update params with default values if there are no stored in the DB. Usefull when adding a new XML field and a user don't resave settings }


		$this->pparams = $pparams;
	}

	/**
	 * Sets some default values
	 *
	 * In J1.7+ the default values written in the XML file are not passed to the script
	 * till first time save the plugin options. The defaults are used only to show values when loading
	 * the setting page for the first time. And if a user just publishes the plugin from the plugin list,
	 * ALL the fields doesn't have values set. So this function
	 * is created to avoid duplicating the defaults in the code.
	 * Usage:
	 * Instead of
	 * <code>$this->params->get( 'some_field_name', 'default_value' )</code>
	 * use
	 * <code>$this->paramGet( 'some_field_name',[optional 'default_value'])</code>
	 *
	 * @author Gruz <arygroup@gmail.com>
	 * @param string $name XML field name
	 * @param mixed $default Default value if not default is found
	 * @return mixed default value
	 */
	function paramGet($name,$default=null) {
		$hash = get_class();
		$session = JFactory::getSession();
		$params = $session->get('DefaultParams',false,$hash); // Get cached parameteres
		if (empty($params) || empty($params[$name])) {
			//$xmlfile = dirname(__FILE__).'/'.basename(__FILE__,'.php').'.xml';
			$xmlfile = $this->plg_path.'/'.$this->plg_name.'.xml';
			$xml = simplexml_load_file($xmlfile);
			//unset ($xml->scriptfile);
			$field = 'field';
			$xpath = 'config/fields/fieldset';

			foreach ($xml->xpath('//'.$xpath.'/'.$field) as $f) {
				if (isset($f['default']) ) {
					if (preg_match('~[0-9]+,[0-9]*~',(string)$f['default'])) {
						$params[(string)$f['name']] = explode (',',(string)$f['default']);
					}
					else {
						$params[(string)$f['name']] = (string)$f['default'];
					}
				}
			}
			$session->set('DefaultParams',$params,$hash);
		}
		if (!isset ($params[$name])) {
			$params[$name] = $default;
		}
		return $this->params->get( $name,$params[$name]);
	}


	/**
	 * Checks if current view is a plugin edit view
	 *
	 * @author Gruz <arygroup@gmail.com>
	 * @return	bool			true if currentrly editing current plugin, false - if another plugin view
	 */

	function checkIfNowIsCurrentPluginEditWindow() {
		$jinput = JFactory::getApplication()->input;

		$option = $jinput->get('option',null);
		if ($option !== 'com_plugins') { return false; }
		$view = $jinput->get('view',null);
		$layout = $jinput->get('layout',null);
		$current_extension_id = $jinput->get('extension_id',null);
		if ($view == 'plugin' && $layout == 'edit') { // Means we are editing a plugin
			$db = JFactory::getDBO();
			$db->setQuery('SELECT extension_id FROM #__extensions WHERE type ='.$db->quote('plugin'). ' AND element = '. $db->quote($this->plg_name).' AND folder = '.$db->quote($this->plg_type));
			$extension_id = $db->loadResult();
			if ($current_extension_id == $extension_id) {
				return true;
			}
		}
		return false;
	}

	function checkIfAPluginPublished ($plugin_group,$plugin_name, $show_message = true) {
		$plugin_state = JPluginHelper::getPlugin($plugin_group, $plugin_name);

		if (!$plugin_state) {
			if ($show_message) {
				$db = JFactory::getDBO();
				$db->setQuery('SELECT name FROM #__extensions WHERE type ='.$db->quote('plugin'). ' AND element = '. $db->quote($plugin_name).' AND folder = '.$db->quote($plugin_group));
				$name = $db->loadResult();
				$plugin_name = JText::_($name);
				$application = JFactory::getApplication();
				$application->enqueueMessage(JText::sprintf('LIB_GJFIELDS_PLUGIN_NOT_PUBLISHED',$plugin_name,$plugin_group,$plugin_name,$plugin_name,$plugin_group), 'error');
			}
			return false;
		}
		else {return true;}
	}

}
