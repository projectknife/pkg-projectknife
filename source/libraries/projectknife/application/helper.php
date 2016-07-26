<?php
/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


/**
 * Projectknife Application Helper Class
 *
 */
abstract class PKApplicationHelper
{
    // Holds a list of all Projectknife extensions
    protected static $extensions;

    // Holds a list of Projectknife components
    protected static $components;

    // Holds a list of Projectknife modules
    protected static $modules;

    // Holds a list of Projectknife plugins
    protected static $plugins;

    // Holds a list of Projectknife templates
    protected static $templates;

    // Holds the names of all Projectknife components
    protected static $component_names;

    // The id of the current project
    protected static $project_id = null;

    // The title of the current project
    protected static $project_title = null;

    // The alias of the current project
    protected static $project_alias = null;

    // Holds the menu item id's
    protected static $menu_items = array();


    /**
     * Method to load all Projectknife extensions
     *
     * @return    void
     */
    private static function loadExtensions()
    {
        // Check if cached
        if (is_array(self::$extensions)) {
            return;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id, a.admin_view, a.ordering')
              ->select('e.type, e.element, e.folder, e.client_id, e.enabled, e.access')
              ->from('#__pk_extensions AS a')
              ->join('inner', '#__extensions AS e ON (e.extension_id = a.id)')
              ->order('a.ordering ASC');

        $db->setQuery($query);
        $extensions = $db->loadObjectList();

        if (!is_array($extensions)) {
            $extensions = array();
        }

        $components = array();
        $modules    = array();
        $plugins    = array();
        $templates  = array();
        $count      = count($extensions);

        for ($i = 0; $i != $count; $i++)
        {
            switch ($extensions[$i]->type)
            {
                case 'component':
                    $components[] = $extensions[$i];
                    break;

                case 'module':
                    $modules[] = $extensions[$i];
                    break;

                case 'plugin':
                    $plugins[] = $extensions[$i];
                    break;

                case 'template':
                    $templates[] = $extensions[$i];
                    break;
            }
        }

        self::$extensions = $extensions;
        self::$components = $components;
        self::$modules    = $modules;
        self::$plugins    = $plugins;
        self::$templates  = $templates;
    }


    /**
     * Method to get all Projectknife components
     *
     * @return    array
     */
    public static function getComponents()
    {
        // Check if cache isset
        if (!is_array(self::$components)) {
            self::loadExtensions();
        }

        return self::$components;
    }


    /**
     * Method to get all Projectknife modules
     *
     * @param     mixed    $client    Optional module client id
     *
     * @return    array
     */
    public static function getModules($client = null)
    {
        // Check if cache isset
        if (!is_array(self::$modules)) {
            self::loadExtensions();
        }

        if (is_null($client)) {
            return self::$modules;
        }

        $list  = array();
        $count = count(self::$modules);

        for ($i = 0; $i != $count; $i++)
        {
            if (self::$modules[$i]->client_id == $client) {
                $list[] = self::$modules[$i];
            }
        }

        return $list;
    }


    /**
     * Method to get all Projectknife plugins
     *
     * @param     mixed    $type    Optional plugin type
     *
     * @return    array
     */
    public static function getPlugins($type = null)
    {
        // Check if cache isset
        if (!is_array(self::$plugins)) {
            self::loadExtensions();
        }

        if (is_null($type)) {
            return self::$plugins;
        }

        $list  = array();
        $count = count(self::$plugins);

        for ($i = 0; $i != $count; $i++)
        {
            if (strcmp(self::$plugins[$i]->folder, $type) === 0) {
                $list[] = self::$plugins[$i];
            }
        }

        return $list;
    }


    /**
     * Method to get all Projectknife templates
     *
     * @return    array
     */
    public static function getTemplates()
    {
        // Check if cache isset
        if (!is_array(self::$templates)) {
            self::loadExtensions();
        }

        return self::$templates;
    }


    /**
     * Method to get all Projectknife component names
     *
     * @return    array
     */
    public static function getComponentNames()
    {
        // Check if cache isset
        if (is_array(self::$component_names)) {
            return self::$component_names;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.element')
              ->from('#__extensions AS a')
              ->join('inner', '#__pk_extensions AS e ON (e.id = a.extension_id)')
              ->where('a.type = ' . $db->quote('component'))
              ->order('e.ordering ASC');

        $db->setQuery($query);
        $names = $db->loadColumn();

        if (!is_array($names)) {
            $names = array();
        }

        self::$component_names = $names;

        return self::$component_names;
    }


    /**
     * Method to check if an extension exists or not
     *
     * @param     string     $type    The extension type
     * @param     string     $name    The extension name
     *
     * @return    boolean
     */
    public static function exists($type, $name)
    {
        switch ($type)
        {
            case 'component':
                $list = self::$components;
                break;

            case 'module':
                $list = self::$modules;
                break;

            case 'plugin':
                $list = self::$plugins;
                break;

            case 'template':
                $list = self::$templates;
                break;

            default:
                $list = array();
                break;
        }

        $extension = null;
        $count     = count($list);
        $exists    = false;

        for ($i = 0; $i != $count; $i++)
        {
            $extension = $list[$i];

            if ($extension->element == $name) {
                $exists = true;
                break;
            }
        }
    }


    /**
     * Method to check if a component is enabled or not
     *
     * @param     string     $type    The extension type
     * @param     string     $name    The extension name
     *
     * @return    boolean
     */
    public static function enabled($type, $name)
    {
        switch ($type)
        {
            case 'component':
                $list = self::$components;
                break;

            case 'module':
                $list = self::$modules;
                break;

            case 'plugin':
                $list = self::$plugins;
                break;

            case 'template':
                $list = self::$templates;
                break;

            default:
                $list = array();
                break;
        }

        $extension = null;
        $count     = count($list);
        $exists    = false;

        for ($i = 0; $i != $count; $i++)
        {
            $extension = $list[$i];

            if ($extension->element == $name) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            if ($extension->enabled == '1') {
                $enabled = true;
            }
        }

        return false;
    }


    /**
     * Method to set the current project id.
     *
     * @param     int     $id    The project id
     *
     * @return    bool           True on sucess.
     */
    public static function setProjectId($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            self::$project_id    = 0;
            self::$project_title = '';
            self::$project_alias = '';
            return true;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, title, alias')
              ->from('#__pk_projects')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $project = $db->loadObject();

        if (empty($project)) {
            // Project not found...
            return false;
        }

        if (self::$project_id != (int) $project->id) {
            $app = JFactory::getApplication();
            $app->setUserState('projectknife.project_id', (int) $project->id);
        }

        self::$project_id    = (int) $project->id;
        self::$project_title = $project->title;
        self::$project_alias = $project->alias;


        return true;
    }


    /**
     * Method to get the current project id.
     *
     * @return    int
     */
    public static function getProjectId()
    {
        if (is_null(self::$project_id)) {
            $id = JFactory::getApplication()->getUserState('projectknife.project_id');

            self::$project_id = (is_null($id) ? 0 : (int) $id);
        }

        return self::$project_id;
    }


    /**
     * Method to get the current project title.
     *
     * @return    string
     */
    public static function getProjectTitle()
    {
        if (is_null(self::$project_title)) {
            if (self::$project_id > 0) {
                $db    = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query->select('title')
                      ->from('#__pk_projects')
                      ->where('id = ' . (int) self::$project_id);

                $db->setQuery($query);
                self::$project_title = (string) $db->loadResult();
            }
            else {
                self::$project_title = '';
            }
        }

        return self::$project_title;
    }


    /**
     * Method to get the current project alias.
     *
     * @return    string
     */
    public static function getProjectAlias()
    {
        if (is_null(self::$project_alias)) {
            if (self::$project_id > 0) {
                $db    = JFactory::getDBO();
                $query = $db->getQuery(true);

                $query->select('alias')
                      ->from('#__pk_projects')
                      ->where('id = ' . (int) self::$project_id);

                $db->setQuery($query);
                self::$project_alias = (string) $db->loadResult();
            }
            else {
                self::$project_alias = '';
            }
        }

        return self::$project_alias;
    }


    /**
     * Method to retrieve the menu item id of a component view
     *
     * @param     string     $component
     * @param     string     $view
     * @param     array      $needles
     *
     * @return    integer
     */
    public static function getMenuItemId($component, $view = null, $needles = null)
    {
        $language = '*';

        if (is_array($needles) && isset($needles['language'])) {
            $language = $needles['language'];
        }

        // Get current id?
        if (strtolower($component) == 'active' && is_null($view) && is_null($needles)) {
            $app    = JFactory::getApplication();
            $menus  = $app->getMenu('site');
            $active = $menus->getActive();

            if ($active) {
                return $active->id;
            }

            // If not found, return language specific home link
            $default = $menus->getDefault($language);

            return !empty($default->id) ? $default->id : null;
        }

        if (!$view) {
            $view = 'default';
        }


        // Prepare reverse lookup
        if (!isset(self::$menu_items[$component])) {
            self::$menu_items[$component] = array($language => array());

            $com        = JComponentHelper::getComponent($component);
            $attributes = array('component_id');
            $values     = array($com->id);

            if ($language != '*') {
                $attributes[] = 'language';
                $values[]     = array($needles['language'], '*');
            }

            $app   = JFactory::getApplication();
            $menus = $app->getMenu('site');
            $items = $menus->getItems($attributes, $values);

            foreach ($items as $item)
            {
                if (isset($item->query) && isset($item->query['view'])) {
                    $item_view = $item->query['view'];

                    if (!isset(self::$menu_items[$component][$language][$item_view])) {
                        self::$menu_items[$component][$language][$item_view] = array();
                    }

                    if (isset($item->query['id'])) {
                        if (!isset(self::$menu_items[$component][$language][$item_view][$item->query['id']]) || $item->language != '*') {
                            self::$menu_items[$component][$language][$item_view][$item->query['id']] = $item->id;
                        }
                    }
                    elseif (!isset(self::$menu_items[$component][$language][$item_view][0])) {
                        self::$menu_items[$component][$language][$item_view][0] = $item->id;
                    }
                }
            }
        }

        // Try to find the menu item id
        if (isset(self::$menu_items[$component][$language][$view])) {
            if (!$needles) {
                $needles = array(0);
            }

            foreach ($needles AS $id)
            {
                if (isset(self::$menu_items[$component][$language][$view][(int) $id])) {
                    return self::$menu_items[$component][$language][$view][(int) $id];
                }
            }
        }

        // Not found; fall back to active menu item
        $app    = JFactory::getApplication();
        $menus  = $app->getMenu('site');
        $active = $menus->getActive();

        if ($active) {
            return $active->id;
        }

        // If not found, return language specific home link
        $default = $menus->getDefault($language);

        return !empty($default->id) ? $default->id : null;
    }
}
