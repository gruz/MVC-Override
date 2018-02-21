<?php
    /**
     * Kunena Component
     * @package    Kunena.Framework
     *
     * @copyright  (C) 2008 - 2017 Kunena Team. All rights reserved.
     * @license    https://www.gnu.org/copyleft/gpl.html GNU/GPL
     * @link       https://www.kunena.org
     **/
    defined('_JEXEC') or die();
     
    jimport('joomla.application.component.helper');
     
    /**
     * Class KunenaController
     */
    class KunenaController extends KunenaControllerDefault
    {
            /**
             * Method to get the appropriate controller.
             *
             * @param   string $prefix
             * @param   mixed  $config
             *
             * @return KunenaController
             * @throws Exception
             */
            public static function getInstance($prefix = 'Kunena', $config = array())
            {
                    static $instance = null;
     
                    if (!$prefix)
                    {
                            $prefix = 'Kunena';
                    }
     
                    if (!empty($instance) && !isset($instance->home))
                    {
                            return $instance;
                    }
     
                    $input = JFactory::getApplication()->input;
     
                    $app = JFactory::getApplication();
                    $command  = $input->get('task', 'display');
     
                    // Check for a controller.task command.
                    if (strpos($command, '.') !== false)
                    {
                            // Explode the controller.task command.
                            list ($view, $task) = explode('.', $command);
     
                            // Reset the task without the controller context.
                            $input->set('task', $task);
                    }
                    else
                    {
                            // Base controller.
                            $view = strtolower(JFactory::getApplication()->input->getWord('view', $app->isAdmin() ? 'cpanel' : 'home'));
                    }
     
                    $path = JPATH_COMPONENT . "/controllers/{$view}.php";
     
                    // If the controller file path exists, include it ... else die with a 500 error.
                    if (is_file($path))
                    {
                            /* ##mygruz20170819000208 {
                            It was:
                            require_once $path;
                            It became: */
                            JLoader::register('KunenaController' . ucfirst($view), $path);
                            /* ##mygruz20170819000208 } */
                    }
                    else
                    {
                            throw new Exception(JText::sprintf('COM_KUNENA_INVALID_CONTROLLER', ucfirst($view)), 404);
                    }
     
                    // Set the name for the controller and instantiate it.
                    if ($app->isAdmin())
                    {
                            $class = $prefix . 'AdminController' . ucfirst($view);
                            KunenaFactory::loadLanguage('com_kunena.controllers', 'admin');
                            KunenaFactory::loadLanguage('com_kunena.models', 'admin');
                            KunenaFactory::loadLanguage('com_kunena.sys', 'admin');
                            KunenaFactory::loadLanguage('com_kunena', 'site');
                    }
                    else
                    {
                            $class = $prefix . 'Controller' . ucfirst($view);
                            KunenaFactory::loadLanguage('com_kunena.controllers');
                            KunenaFactory::loadLanguage('com_kunena.models');
                            KunenaFactory::loadLanguage('com_kunena.sys', 'admin');
                    }
     
                    if (class_exists($class))
                    {
                            $instance = new $class;
                    }
                    else
                    {
                            throw new Exception(JText::sprintf('COM_KUNENA_INVALID_CONTROLLER_CLASS', $class), 404);
                    }
     
                    return $instance;
            }
    }
     
