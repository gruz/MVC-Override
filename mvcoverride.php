<?php
/**
 * MVC override plugin
 *
 * @package		MVC override plugin
 * @author Gruz <arygroup@gmail.com>
 * @copyright	Copyleft - All rights reversed
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
if (!defined('DS')) { // Do this because some extensions still use DS, i.e. com_adsmanager
	define ('DS',DIRECTORY_SEPARATOR);
}

jimport('joomla.plugin.plugin');
jimport( 'gjfields.helper.plugin' );

if (!class_exists('JPluginGJFields')) {
	JFactory::getApplication()->enqueueMessage('Strange, but missing GJFields library for <span style="color:black;">'.__FILE__.'</span><br> The library should be installed together with the extension... Anyway, reinstall it: <a href="http://www.gruz.org.ua/en/extensions/gjfields-sefl-reproducing-joomla-jform-fields.html">GJFields</a>', 'error');
}
else {
	class PlgSystemMVCOverride extends JPluginGJFields {
		/**
		 * Extension name to use a variable, not to hardcode
		 *
		 * @var string
		 */
		private $_extensionName  = 'mvcoverride';


		/**
		 * Constructor
		 *
		 * @author Gruz <arygroup@gmail.com>
		 */
		public function __construct(& $subject, $config) {
			parent::__construct($subject, $config);
			$jinput = & JFactory::getApplication()->input;
			if ($jinput->get('option',null) == 'com_dump') { return; }

			$this->getGroupParams('{overridegroup');// Get variable fields params parsed in a nice way, stored to $this->pparams
		}

		/**
		 * Override MVC
		 */
		public function onAfterInitialise() {

			$jinput = JFactory::getApplication()->input;
			if ($jinput->get('option',null) == 'com_dump') { return; }
			$app = JFactory::getApplication();
			$router = $app->getRouter();
			$uri     = JUri::getInstance();
			JURI::current();// It's very strange, but without this line at least Joomla 3 fails to fulfill the parse below task
			$parsed = $router->parse($uri);
			if (isset($parsed['mvcoverride_disable']) && $parsed['mvcoverride_disable'] == '1' ) {
				return;
			}

			// Add compatibility with Ajax Module Loader
			if (
				isset($parsed['option']) && $parsed['option'] == 'com_content'
				&&	isset($parsed['view']) && $parsed['view'] == 'article'
				&&	isset($parsed['format']) && $parsed['format'] == 'module'
			) {
				return;
			}

			$option = $this->getOption();

			//constants to replace JPATH_COMPONENT, JPATH_COMPONENT_SITE and JPATH_COMPONENT_ADMINISTRATOR
			define('JPATH_SOURCE_COMPONENT',JPATH_BASE.'/components/'.$option);
			define('JPATH_SOURCE_COMPONENT_SITE',JPATH_SITE.'/components/'.$option);
			define('JPATH_SOURCE_COMPONENT_ADMINISTRATOR',JPATH_ADMINISTRATOR.'/components/'.$option);

			jimport('joomla.filesystem.file');
			jimport('joomla.filesystem.folder');

			// Process each override
			foreach ($this->pparams as $override) {
				$rules = explode ('-.-.-.-.-',$override['textparams']);
				foreach($rules as $rule) {
					$override['textparams'] = $rule;
					$this->_override($override);
				}
			}

			// Fallback to Julio's method for backwards compatibility
			// This automatically searches and matches files in the code directory
			// without having to configure directly in the plugin params
			$this->_autoOverride();

		}

		/**
		 * Name or short description
		 *
		 * Full description (multiline)
		 *
		 * @author Gruz <arygroup@gmail.com>
		 * @param	type	$name	Description
		 * @return	type			Description
		 */
		function parseTextAreaIntoArray($override) {
			$override['overridePath'] = null;
			$varNames = array ('basePath','changePrivate','className','includes','option','scope');
			$text = explode(PHP_EOL,$override['textparams']);
			foreach ($text as $key=>$line) {
				$line = explode(':|:',$line);
				$line[0] = JString::trim ($line[0]);
				if (isset($line[1])) { $line[1] = JString::trim ($line[1]); }
				if (in_array($line[0],$varNames) && isset($line[1])) {
					if ($line[0] == 'includes') {
						$line[1] = explode (',',$line[1]);
						foreach ($line[1] as $k=>$v) {
							$line[1][$k] = trim ($v);
						}
						$line[1] = implode(PHP_EOL,$line[1]);
					}
					$override[$line[0]] = $line[1];
					unset ($text[$key]);
				}
			}
			foreach ($text as $k=>$v) {
				//$text[$k] = JString::trim($v);
			}
			$text = implode(PHP_EOL,$text);
			$override['code'] = str_replace('code:|:','',$text);

			return $override;
		}


		/**
		 * Name or short description
		 *
		 * Full description (multiline)
		 *
		 * @author Gruz <arygroup@gmail.com>
		 * @param	type	$name	Description
		 * @return	type			Description
		 */
		function checkPathes(&$override) {
			// Absolute paths to the files
			if(file_exists($override['basePath']) && file_exists(JPATH_ROOT.'/'.$override['basePath'])) {
				$override['basePath'] = JPATH_ROOT.'/'.$override['basePath'];
			}
			if(!file_exists($override['basePath'])) {
				$override['basePath'] = JPATH_ROOT.'/'.$override['basePath'];
			}
			if(!file_exists($override['basePath'])) {
				$this->message(JText::_('JERROR_LOADFILE_FAILED').' <i>'.$override['basePath'].'</i> ','error');
				return false;
			}
			if ($override['textorfields'] == 1) {
				$overrider_path_default = dirname(__FILE__) . '/' . 'code' . '/' . $override['overridePath'];
				if(file_exists($overrider_path_default)) {
					$override['overridePath'] = $overrider_path_default;
					return true;
				}
				if(!file_exists($override['overridePath'])) {
					$override['overridePath'] = JPATH_ROOT.'/'. $override['overridePath'];
				}
				if(!file_exists($override['overridePath'])) {
					$override['overridePath'] = dirname(__FILE__) . '/' . 'code' . '/' . $override['overridePath'];
				}
				if(!file_exists($override['overridePath'])) {
					$this->message(JText::_('JERROR_LOADFILE_FAILED').' <i>'.$override['overridePath'].'</i> ','error');
					return false;
				}
			}
			//$overridePath = dirname(__FILE__) . '/' . 'overrides' . '/' . $override['overridePath'];

			// Check that the override and base files exist
			//if (file_exists($basePath) && file_exists($overridePath)) {
			return true;
		}

		private function _override($override) {
			if($override['ruleEnabled'] != 1) {return;}
			if($override['textorfields'] == 0) {
				$override = $this->parseTextAreaIntoArray($override);
			}
			if (!$this->checkPathes($override)) {
				return;
			}
			// Check override component condition. If a component was specified, check that it matches the current component.
			$jinput = JFactory::getApplication()->input;
			$option = $this->getOption();
			if(class_exists($override['className'])) {
				$this->message(JText::_('PLG_SYSTEM_MVCOVERRIDE_CLASS_IS_ALREADY_DECLARED').': '.$override['className'],'notice');
				return;
			}

			if ($override['option'] !='' && $override['option'] != $option) return;
			// Check scope condition
			$app = JFactory::getApplication();
			if (($override['scope']=='admin' && !$app->isAdmin()) || ($override['scope']=='site' && $app->isAdmin())) return;

			// Read in the base class
			$buffer = JFile::read ($override['basePath']);

			// Strip trailing <?
			$buffer = trim($buffer);
			$key = '?>';
			if(strlen($buffer) - strlen($key) == strrpos($buffer,$key)) {
				$buffer = substr($buffer,0,strlen($buffer)-2);
			}

			//detect if source file use some constants
			$buffer = preg_replace(array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),$buffer);


			// Determine the class name or use the given class
			$rx = '/class *[a-z0-9]* *(extends|{)/i';
			if ($override['className'] != '') {
				$rx = '/class *'.$override['className'].' *(extends|{)/i';
			}
			preg_match($rx, $buffer, $classes);
			if (empty($classes)) {
				$rx = '/class *[a-z0-9]*/i';
				preg_match($rx, $buffer, $classes);
			}
			// The regex matching will return a phrase such as "class ClassName {" so we break it into individual words
			$name = explode(' ', $classes[0]);
			if (isset($name[1])) { // The class name should always be the second word. Make sure we have a class name before proceeding
				if (!class_exists($name[1].'Default')) {
					// Include additional supporting files
					$includes = explode(PHP_EOL, $override['includes']);
					foreach ($includes as $include) {
						$include = JPATH_BASE.'/'.$include;
						if ($override['includes'] != '' && file_exists($include)) {
							$iname = $this->_getClass($include);
							//if (!class_exists($iname)) require($include);
							if (!empty($iname)) {
								//JLoader::register($iname, $include);
								if (!class_exists($iname)) { require $include; }
							}
						}
					}

					// Append "Base" to the class name (ex. ClassNameBase). We insert the new class name into the original regex match to get
					// the complete phrase (ie "class ClassNameBase {")
					$base = str_replace($name[1], $name[1].'Default', $classes[0]);

					// Now we replace the class declaration phrase in the buffer
					$buffer = preg_replace($rx, $base, $buffer);

					// Change private methods to protected methods
					if ($override['changePrivate']) {
						$buffer = preg_replace('/private *function/i', 'protected function', $buffer);
					}
					// Finally we can load the base class
					eval('?>'.$buffer.PHP_EOL.'?>');
				}

				// And load our overrider class which is now a subclass of the base class
				if($override['textorfields'] == 1) {
					JLoader::register($name[1], $override['overridePath'], true);
				}
				else {
					ob_start();
					// Do eval()
					$error_reporting = '';
					if ($this->paramGet('showDebug')) {
						$error_reporting = 'error_reporting(E_ALL);'.PHP_EOL;
					}
					$check = eval($error_reporting.$override['code']);
					$output = ob_get_contents();
					ob_end_clean();
					// Send output or report errors
					if ($check === false) {
						$this->message(JText::_('PLG_SYSTEM_MVCOVERRIDE_EVAL_FAILED').'<br/>'.$output.'<pre style="text-align:left;">'.htmlentities($override['code']).'</pre>','error');
					}
				}
				if ($this->paramGet('bruteMode') == 1 ) {
					if (!class_exists($name[1])) {
						require($override['overridePath']);
						}
					$originalFilePath = $override['basePath'];
					JFile::move($originalFilePath,$originalFilePath.'1');
					file_put_contents($originalFilePath,'');
					require $originalFilePath;
					JFile::move($originalFilePath.'1',$originalFilePath);
				}
			}
		}

		private function _autoOverride() {
			$option = $this->getOption();

			//get files that can be overrided
			$componentOverrideFiles = $this->loadComponentFiles($option);
			//application name
			$applicationName = JFactory::getApplication()->getName();
			//template name
			$template = JFactory::getApplication()->getTemplate();

			//code paths
			$includePath = array();
			//template code path
			$includePath[] = JPATH_THEMES.'/'.$template.'/code';
			//base extensions path
			$includePath[] = JPATH_BASE.'/code';
			//administrator extensions path
			$includePath[] = JPATH_ADMINISTRATOR.'/code';

			//loading override files
			if( !empty($componentOverrideFiles) ){
				foreach($componentOverrideFiles as $componentFile)
				{
					if($filePath = JPath::find($includePath,$componentFile))
					{
						//include the original code and replace class name add a Default on
						$originalFilePath =  JPATH_BASE.'/components/'.$componentFile;
						$bufferFile = JFile::read($originalFilePath);

						//detect if source file use some constants
						preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferFile, $definesSource);

						$bufferOverrideFile = JFile::read($filePath);

						//detect if override file use some constants
						preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferOverrideFile, $definesSourceOverride);


						// Append "Default" to the class name (ex. ClassNameDefault). We insert the new class name into the original regex match to get
						$rx = '/class *[a-z0-9]* *(extends|{)/i';

						preg_match($rx, $bufferFile, $classes);

						if (empty($classes)) {
							$rx = '/class *[a-z0-9]*/i';
							preg_match($rx, $bufferFile, $classes);
						}

						$parts = explode(' ',$classes[0]);

						$originalClass = $parts[1];

						$replaceClass = $originalClass.'Default';

						if (count($definesSourceOverride[0]))
						{
							JError::raiseError('Plugin MVC Override','Your override file use constants, please replace code constants<br />JPATH_COMPONENT -> JPATH_SOURCE_COMPONENT,<br />JPATH_COMPONENT_SITE -> JPATH_SOURCE_COMPONENT_SITE and<br />JPATH_COMPONENT_ADMINISTRATOR -> JPATH_SOURCE_COMPONENT_ADMINISTRATOR');
						}
						else
						{
							//replace original class name by default
							$bufferContent = str_replace($originalClass,$replaceClass,$bufferFile);
							//replace JPATH_COMPONENT constants if found, because we are loading before define these constants
							if (count($definesSource[0]))
							{
								$bufferContent = preg_replace(array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),$bufferContent);
							}

							// Change private methods to protected methods
							if ($this->params->get('changePrivate',0))
							{
								$bufferContent = preg_replace('/private *function/i', 'protected function', $bufferContent);
							}

							// Finally we can load the base class
							eval('?>'.$bufferContent.PHP_EOL.'?>');

							require $filePath;
							if ($this->paramGet('bruteMode') == 1 ) {
								JFile::move($originalFilePath,$originalFilePath.'1');
								file_put_contents($originalFilePath,'');
								require $originalFilePath;
								JFile::move($originalFilePath.'1',$originalFilePath);
							}

						}

					}
				}
			}
		}

		/**
		 * loadComponentFiles function.
		 *
		 * @access private
		 * @param mixed $option
		 * @return void
		 */
		private function loadComponentFiles($option)
		{
			$JPATH_COMPONENT = JPATH_BASE.'/components/'.$option;
			$files = array();

			//check if default controller exists
			if (JFile::exists($JPATH_COMPONENT.'/controller.php'))
			{
				$files[] = $JPATH_COMPONENT.'/controller.php';
			}

			//check if controllers folder exists
			if (JFolder::exists($JPATH_COMPONENT.'/controllers'))
			{
				$controllers = JFolder::files($JPATH_COMPONENT.'/controllers', '.php', false, true);
				$files = array_merge($files, $controllers);
			}

			//check if models folder exists
			if (JFolder::exists($JPATH_COMPONENT.'/models'))
			{
				$models = JFolder::files($JPATH_COMPONENT.'/models', '.php', false, true);
				$files = array_merge($files, $models);
			}

			//check if views folder exists
			if (JFolder::exists($JPATH_COMPONENT.'/views'))
			{
				//reading view folders
				$views = JFolder::folders($JPATH_COMPONENT.'/views');
				foreach ($views as $view)
				{
					//get view formats files
					$viewsFiles = JFolder::files($JPATH_COMPONENT.'/views/'.$view, '.php', false, true);
					$files = array_merge($files, $viewsFiles);
				}
			}

			//check if helpers folder exists
			$foldername = 'helpers';
			//check if models folder exists
			if (JFolder::exists($JPATH_COMPONENT.'/'.$foldername))
			{
				$models = JFolder::files($JPATH_COMPONENT.'/'.$foldername, '.php', false, true);
				$files = array_merge($files, $models);
			}


			$return = array();
			//cleaning files
			foreach ($files as $file)
			{
				$file = JPath::clean($file);
				$file = substr($file, strlen(JPATH_BASE.'/components/'));
				$return[] = $file;
			}

			return $return;
		}

		private function _getClass($filePath) {
			// Read in file
			$buffer = JFile::read ($filePath);

			// Get the class name
			$rx = '/class *[a-z0-9]* *(extends|{)/i';
			preg_match($rx, $buffer, $classes);
			if (empty($classes)) {
				$rx = '/class *[a-z0-9]*/i';
				preg_match($rx, $buffer, $classes);
			}
			if (empty($classes)) {
				return null;
			}
			$name = explode(' ', $classes[0]);

			return isset($name[1]) ? $name[1] : false;
		}

		/**
		 * Shows debug messages if settings allow
		 *
		 * @author Gruz <arygroup@gmail.com>
		 * @param	string	$msg	Message
		 * @param	string	$type	Type of the message
		 * @return	void			Description
		 */
		private function message ($msg,$type='') {
			$showDebug = $this->paramGet('showDebug');
			switch ($showDebug) {
				case '1':
					$app = JFactory::getApplication();
					if ($app->isAdmin()) {
						break;
					}
					return;
					break;
				case '2':
					break;
				case '0':
				default :
					return;
					break;
			}
			JFactory::getApplication()->enqueueMessage($msg, $type);
			return;
		}


		/**
		 * Get's current $option as it's not defined at onAfterInitialize
		 *
		 * @author Gruz <arygroup@gmail.com>
		 * @return	string			Option, i.e. com_content
		 */
		function getOption() {
			$jinput = JFactory::getApplication()->input;
			$option = $jinput->get('option',null);
			if(empty($option) && JFactory::getApplication()->isSite() ) {
				$app = JFactory::getApplication();
				$router = $app->getRouter();
				$uri     = JUri::getInstance();
				JURI::current();// It's very strange, but without this line at least Joomla 3 fails to fulfill the parse below task
				$parsed = $router->parse($uri);
				$option = $parsed['option'];
				/*
				$menuDefault = JFactory::getApplication()->getMenu()->getDefault();
				if (is_int($menuDefault) && $menuDefault == 0) return;
				$componentID = $menuDefault->component_id;
				$db = JFactory::getDBO();
				$db->setQuery('SELECT * FROM #__extensions WHERE extension_id ='.$db->quote($componentID));
				$component = $db->loadObject();
				$option = $component->element;
				*/
			}
			return $option;
		}
	}
}
