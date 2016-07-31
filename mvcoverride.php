<?php
/**
 * MVC override plugin
 *
 * @package     Joomla.plugin
 * @subpackage  mvcoverride
 * @author      Gruz <arygroup@gmail.com>
 * @copyright   Copyleft (є) 2016 - All rights reversed
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Do this because some extensions still use DS, i.e. com_adsmanager
if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

jimport('joomla.plugin.plugin');
jimport('gjfields.helper.plugin');

if (!class_exists('JPluginGJFields'))
{
	JFactory::getApplication()->enqueueMessage('Strange, but missing GJFields library for <span style="color:black;">'
		. __FILE__ . '</span><br> The library should be installed together with the extension... Anyway, reinstall it: '
		. '<a href="http://www.gruz.org.ua/en/extensions/gjfields-sefl-reproducing-joomla-jform-fields.html">GJFields</a>', 'error');
}
else
{
	/**
	 * Main MVC override class
	 *
	 * @author  Gruz <arygroup@gmail.com>
	 * @since   0.0.1
	 */
	class PlgSystemMVCOverride extends JPluginGJFields
				{
		/**
		 * Constructor
		 *
		 * Full description (multiline)
		 *
		 * @param   mixed  &$subject  Subject
		 * @param   mixed  $config    Config object
		 */
		public function __construct(&$subject, $config)
		{
			parent::__construct($subject, $config);

			if (!empty($this->plg_name))
			{
				JLog::addLogger(
					array(
						'text_file' => 'log.' . $this->plg_name . '.php',
						'text_entry_format' => '{DATETIME}	{PRIORITY} {CLIENTIP}		{CATEGORY}	{MESSAGE}',
					),
					JLog::ALL,
					$this->plg_name
				);
			}

			if (JFactory::getApplication()->input->get('option', null) == 'com_dump')
			{
				return;
			}

			// Get variable fields params parsed in a nice way, stored to $this->pparams
			$this->getGroupParams('{overridegroup');
		}

		/**
		 * The entry point of the plugin
		 *
		 * @return   void
		 */
		public function onAfterInitialise()
		{
			$jinput = JFactory::getApplication()->input;

			if ($jinput->get('option', null) == 'com_dump')
			{
				return;
			}

			$app = JFactory::getApplication();
			$router = $app->getRouter();
			$uri     = JUri::getInstance();

			$parsed = $router->parse($uri);

			if (isset($parsed['mvcoverride_disable']) && $parsed['mvcoverride_disable'] == '1' )
			{
				return;
			}

			// Add compatibility with Ajax Module Loader
			if (isset($parsed['option']) && $parsed['option'] == 'com_content'
				&&	isset($parsed['view']) && $parsed['view'] == 'article'
				&&	isset($parsed['format']) && $parsed['format'] == 'module')
			{
				return;
			}

			$option = $this->getOption();

			// Constants to replace JPATH_COMPONENT, JPATH_COMPONENT_SITE and JPATH_COMPONENT_ADMINISTRATOR
			define('JPATH_SOURCE_COMPONENT', JPATH_BASE . '/components/' . $option);
			define('JPATH_SOURCE_COMPONENT_SITE', JPATH_SITE . '/components/' . $option);
			define('JPATH_SOURCE_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/' . $option);

			jimport('joomla.filesystem.file');
			jimport('joomla.filesystem.folder');

			// Process each override
			foreach ($this->pparams as $override)
			{
				$rules = explode('-.-.-.-.-', $override['textparams']);

				foreach ($rules as $rule)
				{
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
		 * Parses textarea from plugin settings into a common override options array
		 *
		 * @param   array  $override  Override array with options
		 *
		 * @return  array  Modified $override array
		 */
		public function parseTextAreaIntoArray($override)
		{
			$override['overridePath'] = null;
			$varNames = array ('basePath','changePrivate','className','includes','option','scope');
			$text = explode(PHP_EOL, $override['textparams']);

			foreach ($text as $key => $line)
			{
				$line = explode(':|:', $line);
				$line[0] = JString::trim($line[0]);

				if (isset($line[1]))
				{
					$line[1] = JString::trim($line[1]);
				}

				if (in_array($line[0], $varNames) && isset($line[1]))
				{
					if ($line[0] == 'includes')
					{
						$line[1] = explode(',', $line[1]);

						foreach ($line[1] as $k => $v)
						{
							$line[1][$k] = trim($v);
						}

						$line[1] = implode(PHP_EOL, $line[1]);
					}

					$override[$line[0]] = $line[1];
					unset ($text[$key]);
				}
			}

			$text = implode(PHP_EOL, $text);
			$override['code'] = str_replace('code:|:', '', $text);

			return $override;
		}

		/**
		 * Checks paths
		 *
		 * @param   array  &$override  Array of override options
		 *
		 * @return  type   Description
		 */
		public function checkPaths(&$override)
		{
			// Absolute paths to the files
			if (file_exists($override['basePath']) && file_exists(JPATH_ROOT . '/' . $override['basePath']))
			{
				$override['basePath'] = JPATH_ROOT . '/' . $override['basePath'];
			}

			if (!file_exists($override['basePath']))
			{
				$override['basePath'] = JPATH_ROOT . '/' . $override['basePath'];
			}

			if (!file_exists($override['basePath']))
			{
				$this->message(JText::_('JERROR_LOADFILE_FAILED') . ' <i>' . $override['basePath'] . '</i> ', 'error');

				return false;
			}

			if ($override['textorfields'] == 1)
			{
				$overrider_path_default = dirname(__FILE__) . '/' . 'code' . '/' . $override['overridePath'];

				if (file_exists($overrider_path_default))
				{
					$override['overridePath'] = $overrider_path_default;

					return true;
				}

				if (!file_exists($override['overridePath']))
				{
					$override['overridePath'] = JPATH_ROOT . '/' . $override['overridePath'];
				}

				if (!file_exists($override['overridePath']))
				{
					$override['overridePath'] = dirname(__FILE__) . '/' . 'code' . '/' . $override['overridePath'];
				}

				if (!file_exists($override['overridePath']))
				{
					$this->message(JText::_('JERROR_LOADFILE_FAILED') . ' <i>' . $override['overridePath'] . '</i> ', 'error');

					return false;
				}
			}

			// $overridePath = dirname(__FILE__) . '/' . 'overrides' . '/' . $override['overridePath'];

			// Check that the override and base files exist
			// if (file_exists($basePath) && file_exists($overridePath)) {

			return true;
		}

		/**
		 * Main override function
		 *
		 * @param   array  $override  Array with override options
		 *
		 * @return   void
		 */
		private function _override($override)
		{
			if ($override['ruleEnabled'] != 1)
			{
				return;
			}

			if ($override['textorfields'] == 0)
			{
				$override = $this->parseTextAreaIntoArray($override);
			}

			if (!$this->checkPaths($override))
			{
				return;
			}

			// Check override component condition. If a component was specified, check that it matches the current component.
			$jinput = JFactory::getApplication()->input;
			$option = $this->getOption();

			if (class_exists($override['className']))
			{
				$this->message(JText::_('PLG_SYSTEM_MVCOVERRIDE_CLASS_IS_ALREADY_DECLARED') . ': ' . $override['className'], 'notice');

				return;
			}

			if ($override['option'] != '' && $override['option'] != $option)
			{
				return;
			}

			// Check scope condition
			$app = JFactory::getApplication();

			if (($override['scope'] == 'admin' && !$app->isAdmin()) || ($override['scope'] == 'site' && $app->isAdmin()))
			{
				return;
			}

			// Read in the base class
			$buffer = file_get_contents($override['basePath']);

			// Strip trailing <?
			$buffer = trim($buffer);
			$key = '?>';

			if (strlen($buffer) - strlen($key) == strrpos($buffer, $key))
			{
				$buffer = substr($buffer, 0, strlen($buffer) - 2);
			}

			// Detect if source file use some constants
			$buffer = preg_replace(
				array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),
				array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),
				$buffer
			);

			// Determine the class name or use the given class
			$rx = '/class *[a-z0-9]* *(extends|{|\n)/i';

			if ($override['className'] != '')
			{
				$rx = '/class *' . $override['className'] . ' *(extends|{|\n)/i';
			}

			preg_match($rx, $buffer, $classes);

			if (empty($classes))
			{
				$rx = '/class *[a-z0-9]*/i';
				preg_match($rx, $buffer, $classes);
			}

			$classes = array_map('trim', $classes);

			// The regex matching will return a phrase such as "class ClassName {" so we break it into individual words
			$name = explode(' ', $classes[0]);

			// The class name should always be the second word. Make sure we have a class name before proceeding
			if (isset($name[1]))
			{
				if (!class_exists($name[1] . 'Default'))
				{
					// Include additional supporting files
					$includes = explode(PHP_EOL, $override['includes']);

					foreach ($includes as $include)
					{
						$include = JPATH_BASE . '/' . $include;

						if ($override['includes'] != '' && file_exists($include))
						{
							$iname = $this->_getClass($include);

							if (!empty($iname))
							{
								// JLoader::register($iname, $include);
								if (!class_exists($iname))
								{
									require $include;
								}
							}
						}
					}

					// Append "Base" to the class name (ex. ClassNameBase). We insert the new class name into the original regex match to get
					// the complete phrase (ie "class ClassNameBase {")
					$base = str_replace($name[1], $name[1] . 'Default', $classes[0]);

					// Now we replace the class declaration phrase in the buffer
					$buffer = preg_replace($rx, $base, $buffer);

					// Change private methods to protected methods
					if ($override['changePrivate'])
					{
						$buffer = preg_replace('/private *function/i', 'protected function', $buffer);
					}

					// Finally we can load the base class
					eval('?>' . $buffer . PHP_EOL . '?>');
				}

				// And load our overrider class which is now a subclass of the base class
				if ($override['textorfields'] == 1)
				{
					JLoader::register($name[1], $override['overridePath'], true);
				}
				else
				{
					// Do eval()
					ob_start();
					$error_reporting = '';

					if ($this->paramGet('showDebug'))
					{
						$error_reporting = 'error_reporting(E_ALL);' . PHP_EOL;
					}

					$check = eval($error_reporting . $override['code']);
					$output = ob_get_contents();
					ob_end_clean();

					// Send output or report errors
					if ($check === false)
					{
						$this->message(
							JText::_('PLG_SYSTEM_MVCOVERRIDE_EVAL_FAILED')
								. '<br/>' . $output . '<pre style="text-align:left;">'
								. htmlentities($override['code']) . '</pre>',
							'error'
						);
					}
				}

				if ($this->paramGet('bruteMode') == 1 )
				{
					if (!class_exists($name[1]))
					{
						require $override['overridePath'];
					}

					$originalFilePath = $override['basePath'];
					JFile::move($originalFilePath, $originalFilePath . '1');
					file_put_contents($originalFilePath, '');
					require $originalFilePath;
					JFile::move($originalFilePath . '1', $originalFilePath);
				}
			}
		}

		/**
		 * Looks for code folder in three places and overrides if possible
		 *
		 * @return   void
		 */
		private function _autoOverride()
		{
			if (JFactory::getApplication()->input->get('option', null) == 'com_dump')
			{
				return;
			}

			$option = $this->getOption();

			// Application name
			$applicationName = JFactory::getApplication()->getName();

			// Template name
			$template = JFactory::getApplication()->getTemplate();

			// Code paths
			$includePath = array();

			// Template code path
			$includePath[] = JPATH_THEMES . '/' . $template . '/code';

			// Base extensions path
			$includePath[] = JPATH_BASE . '/code';

			// Administrator extensions path
			$includePath[] = JPATH_ADMINISTRATOR . '/code';

			$files_to_override = array();

			foreach ($includePath as $k => $codefolder)
			{
				if (JFolder::exists($codefolder))
				{
					$files = str_replace($codefolder, '', JFolder::files($codefolder, '.php', true, true));
					$files = array_fill_keys($files, $codefolder);
					$files_to_override = array_merge($files_to_override, $files);
				}
			}

			if (empty($files_to_override))
			{
				return;
			}

			// Loading override files
			foreach ($files_to_override as $fileToOverride => $overriderFolder)
			{
				if (JFile::exists(JPATH_ROOT . $fileToOverride))
				{
					$originalFilePath = JPATH_ROOT . $fileToOverride;
				}
				elseif (strpos($fileToOverride, '/com_') === 0 && JFile::exists(JPATH_ROOT . '/components' . $fileToOverride))
				{
					$originalFilePath = JPATH_ROOT . '/components' . $fileToOverride;
				}
				else
				{
					JLog::add("Can see an overrider file ($overriderFolder" . "$fileToOverride) , but cannot find what to override", JLog::INFO, $this->plg_name);
					continue;
				}

				// Do not run override if current option and the default path option are different
				// Avoid loading classes when not needed
				preg_match('~.*/(com_[^/]*)/.*~Ui', $originalFilePath, $matches);

				if (!empty($matches[1]) && $matches[1] != $option )
				{
					continue;
				}

				// Include the original code and replace class name add a Default on
				$bufferFile = file_get_contents($originalFilePath);

				if (strpos($originalFilePath, '/controllers/') !== false )
				{
					$temp = explode('/controllers/', $originalFilePath);
					require_once $temp[0] . '/controller.php';
				}

				// Detect if source file use some constants
				preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferFile, $definesSource);

				$overriderFilePath = $overriderFolder . $fileToOverride;
				$bufferOverrideFile = file_get_contents($overriderFilePath);

				// Detect if override file use some constants
				preg_match_all('/JPATH_COMPONENT(_SITE|_ADMINISTRATOR)|JPATH_COMPONENT/i', $bufferOverrideFile, $definesSourceOverride);

				// Append "Default" to the class name (ex. ClassNameDefault). We insert the new class name into the original regex match to get
				$rx = '/class *[a-z0-9]* *(extends|{|\n)/i';

				preg_match($rx, $bufferFile, $classes);

				if (empty($classes))
				{
					$rx = '/class *[a-z0-9]*/i';
					preg_match($rx, $bufferFile, $classes);
				}

				$parts = explode(' ', $classes[0]);

				$originalClass = $parts[1];

				$replaceClass = trim($originalClass) . 'Default';

				if (count($definesSourceOverride[0]))
				{
					$error = 'Plugin MVC Override:: Your override file use constants, please replace code constants<br />JPATH_COMPONENT -> JPATH_SOURCE_COMPONENT,'
						. '<br />JPATH_COMPONENT_SITE -> JPATH_SOURCE_COMPONENT_SITE and<br />'
						. 'JPATH_COMPONENT_ADMINISTRATOR -> JPATH_SOURCE_COMPONENT_ADMINISTRATOR';
					throw new Exception(str_replace('<br />', PHP_EOL, $error), 500);

					// JFactory::getApplication()->enqueueMessage($error, 'error');
				}
				else
				{
					// Replace original class name by default
					$bufferContent = str_replace($originalClass, $replaceClass, $bufferFile);

					// Replace JPATH_COMPONENT constants if found, because we are loading before define these constants
					if (count($definesSource[0]))
					{
						$bufferContent = preg_replace(
							array('/JPATH_COMPONENT/','/JPATH_COMPONENT_SITE/','/JPATH_COMPONENT_ADMINISTRATOR/'),
							array('JPATH_SOURCE_COMPONENT','JPATH_SOURCE_COMPONENT_SITE','JPATH_SOURCE_COMPONENT_ADMINISTRATOR'),
							$bufferContent
						);
					}

					// Change private methods to protected methods
					if ($this->params->get('changePrivate', 0))
					{
						$bufferContent = preg_replace('/private *function/i', 'protected function', $bufferContent);
					}

					// Finally we can load the base class
					eval('?>' . $bufferContent . PHP_EOL . '?>');

					require $overriderFilePath;

					if ($this->paramGet('bruteMode') == 1 )
					{
						JFile::move($originalFilePath, $originalFilePath . '1');
						file_put_contents($originalFilePath, '');
						require $originalFilePath;
						JFile::move($originalFilePath . '1', $originalFilePath);
					}
				}
			}
		}

		/**
		 * Gets class name from a file using regexp
		 *
		 * @param   string  $filePath  Path to file
		 *
		 * @return   mixed  Class name on success and false if regepx failed
		 */
		private function _getClass($filePath)
		{
			// Read in file
			$buffer = file_get_contents($filePath);

			// Get the class name
			$rx = '/class *[a-z0-9]* *(extends|{|\n)/i';
			preg_match($rx, $buffer, $classes);

			if (empty($classes))
			{
				$rx = '/class *[a-z0-9]*/i';
				preg_match($rx, $buffer, $classes);
			}

			if (empty($classes))
			{
				return null;
			}

			$classes = array_map('trim', $classes);

			$name = explode(' ', $classes[0]);

			return isset($name[1]) ? $name[1] : false;
		}

		/**
		 * Shows debug messages if settings allow
		 *
		 * @param   string  $msg   Message
		 * @param   string  $type  Type of the message
		 *
		 * @return	void			Description
		 */
		private function message ($msg,$type='')
		{
			switch ($this->paramGet('showDebug'))
			{
				case '1':
					$app = JFactory::getApplication();

					if ($app->isAdmin())
					{
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
		public function getOption()
		{
			$jinput = JFactory::getApplication()->input;
			$option = $jinput->get('option', null);

			if (empty($option) && JFactory::getApplication()->isSite())
			{
				$app = JFactory::getApplication();
				$router = $app->getRouter();
				$uri     = JUri::getInstance();

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
