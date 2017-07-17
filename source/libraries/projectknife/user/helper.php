<?php
/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


JLoader::register('PKAccess', JPATH_LIBRARIES . '/projectknife/access/access.php');


/**
 * Projectknife User Helper Class
 *
 */
abstract class PKUserHelper
{
    /**
     * Current user id
     *
     * @var    integer
     */
    protected static $id = null;

    /**
     * Super Admin flag
     *
     * @var    boolean
     */
    protected static $is_root = null;

    /**
     * Authorised view levels from user groups
     *
     * @var    array
     */
    protected static $group_view_levels = null;

    /**
     * Authorised view levels from projects
     *
     * @var    array
     */
    protected static $project_view_levels = null;

    /**
     * Authorised view levels from user groups and projects
     *
     * @var    array
     */
    protected static $view_levels = null;

    /**
     * List of authorised projects
     *
     * @var    array
     */
    protected static $auth_projects = null;


    /**
     * Method to check if the current user is authorised to perform an action, optionally on a project.
     *
     * @param     string     $action     The name of the action to authorise.
     * @param     mixed      $project    Integer project id. Defaults to the global asset node.
     *
     * @return    boolean                True if authorised.
     */
    public static function authProject($action, $project = 0)
    {
        if (self::isSuperAdmin()) {
            return true;
        }

        return PKAccess::checkProject(self::$id, $action, $project);
    }


    /**
     * Method to check if the current user is authorised to perform an action, optionally on a category.
     *
     * @param     string     $action     The name of the action to authorise.
     * @param     mixed      $project    Integer category id. Defaults to the global asset node.
     *
     * @return    boolean                True if authorised.
     */
    public static function authCategory($action, $category = 0)
    {
        if (self::isSuperAdmin()) {
            return true;
        }

        return PKAccess::checkCategory(self::$id, $action, $category);
    }


    /**
     * Method to check if the current user is a super admin
     *
     * @return    boolean
     */
    public static function isSuperAdmin()
    {
        if (is_null(self::$id)) {
            $user = JFactory::getUser();

            self::$id      = $user->id;
            self::$is_root = $user->get('isRoot');

            if (is_null(self::$is_root)) {
                // Value not yet initialised...
                $user->authorise('core.admin');

                self::$is_root = $user->get('isRoot');
            }
        }

        return self::$is_root;
    }


    /**
     * Returns a list of all authorised view levels
     *
     * @return    array
     */
    public static function getAccessLevels()
    {
        if (!is_null(self::$view_levels)) {
            return self::$view_levels;
        }

        if (is_null(self::$group_view_levels)) {
            self::$group_view_levels = JFactory::getUser()->getAuthorisedViewLevels();
        }

        if (is_null(self::$project_view_levels)) {
            self::$project_view_levels = self::getProjectViewLevels();
        }

        self::$view_levels = array_unique(array_merge(self::$group_view_levels, self::$project_view_levels));

        return self::$view_levels;
    }


    /**
     * Returns a list of all authorised project view levels
     *
     * @return    array
     */
    public static function getProjectViewLevels()
    {
        if (!is_null(self::$project_view_levels)) {
            return self::$project_view_levels;
        }

        $user  = JFactory::getUser();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('p.access')
              ->from('#__pk_projects AS p')
              ->join('INNER', '#__pk_project_users AS u ON u.project_id = p.id')
              ->where('u.user_id = ' . (int) $user->id)
              ->group('p.access');

        try {
            $db->setQuery($query);
            self::$project_view_levels = $db->loadColumn();
        }
        catch (RuntimeException $e) {
            throw new RuntimeException('Failed to retrieve project view levels because of a database error.', 500, $e);
        }

        if (!is_array(self::$project_view_levels) || !count(self::$project_view_levels)) {
            self::$project_view_levels = array(0);
        }

        return self::$project_view_levels;
    }


    /**
     * Returns a list of projects, that the user is allowed to view
     *
     * @return array
     */
    public static function getProjects()
    {
        if (!is_null(self::$auth_projects)) {
            return self::$auth_projects;
        }

        $user  = JFactory::getUser();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('project_id')
              ->from('#__pk_project_users')
              ->where('user_id = ' . (int) $user->id);

        try {
            $db->setQuery($query);
            self::$auth_projects = $db->loadColumn();
        }
        catch (RuntimeException $e) {
            throw new RuntimeException('Failed to retrieve authorised projects because of a database error.', 500, $e);
        }

        if (!is_array(self::$auth_projects) || !count(self::$auth_projects)) {
            self::$auth_projects = array(0);
        }

        return self::$auth_projects;
    }


    /**
     *
     * @param    integer   $id      The user id for which to get a link
     * @param    string    $alias   The user alias to use in the URL if applicable
     *
     * @return   mixed              String on success, Null if not found.
     */
    public static function getProfileLink($id = null, $alias = null)
    {
        static $cache  = array();
        static $target = null;

        if (is_null($id)) {
            $id = (int) JFactory::getUser()->id;
        }

        $id = (int) $id;

        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }

        if (is_null($target)) {
            $sys_params = PKPluginHelper::getParams('system', 'projectknife');
            $target     = $sys_params->get('user_profile_link', '');
        }

        $cache[$id] = null;

        switch ($target)
        {
            case 'cb':
                $cache[$id] = self::getCommunityBuilderProfileLink($id, $alias);
                break;

            case 'jomsocial':
                $cache[$id] = self::getJomsocialProfileLink($id);
                break;

            case 'joomla':
                $cache[$id] = self::getJoomlaProfileLink($id, $alias);
                break;
        }

        return $cache[$id];
    }


    /**
     * Returns a community builder profile link
     *
     * @param    integer   $id      The user id for which to get a link
     * @param    string    $alias   The user alias to use in the URL if applicable
     *
     * @return   mixed              String on success, Null if not found.
     */
    private static function getCommunityBuilderProfileLink($id, $alias = null)
    {
        static $extension_exists = null;
        static $menu_itemid      = null;

        // Check if the extension exists once
        if (is_null($extension_exists)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('extension_id')
                  ->from('#__extensions')
                  ->where('name = ' . $db->quote('com_comprofiler'));

            $db->setQuery($query);
            $extension_exists = ($db->loadResult() > 0);
        }

        if (!$extension_exists) {
            return null;
        }


        // Try to find a menu item id once
        if (is_null($menu_itemid)) {
            $app	= JFactory::getApplication();
			$menu	= $app->getMenu();
			$com	= JComponentHelper::getComponent('com_comprofiler');
			$items	= $menu->getItems('component_id', $com->id);

            $profile_id = 0;
            $cb_id = 0;

			// If no items found, set to empty array.
			if (!$items) $items = array();

            foreach ($items as $item)
            {
                if (!isset($item->query['task']) || $item->query['task'] == 'userProfile') {
    				$menu_itemid = $item->id;
    				break;
    			}
            }

            if (!$menu_itemid) $menu_itemid = 0;
        }

        // Create slug
        $slug = (int) $id . (empty($alias) ? '' : ':' . $alias);


        return 'index.php?option=com_comprofiler&task=userProfile&user=' . $slug . ($menu_itemid ? '&Itemid=' . $menu_itemid : '');
    }


    /**
     * Returns a Jomsocial profile link
     *
     * @param    integer   $id      The user id for which to get a link
     *
     * @return   mixed              String on success, Null if not found.
     */
    private static function getJomsocialProfileLink($id)
    {
        static $extension_exists = null;
        static $router           = null;

        // Check if the extension exists once
        if (is_null($extension_exists)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('extension_id')
                  ->from('#__extensions')
                  ->where('name = ' . $db->quote('com_jomsocial'));

            $db->setQuery($query);
            $extension_exists = ($db->loadResult() > 0);
        }

        if (!$extension_exists) {
            return null;
        }


        // Include the route helper once
        if (is_null($router)) {
            $file = JPATH_SITE . '/components/com_community/helpers/url.php';

            if (!file_exists($file)) {
                $router = false;
            }
            else {
                require_once $file;
                $router = class_exists('CUrlHelper');
            }
        }

        if (!$router) {
            return null;
        }


        return CUrlHelper::userLink((int) $id, false);
    }


    /**
     * Returns a Joomla profile link
     *
     * @param    integer   $id      The user id for which to get a link
     * @param    string    $alias   The user alias to use in the URL if applicable
     *
     * @return   mixed              String on success, Null if not found.
     */
    private static function getJoomlaProfileLink($id, $alias = null)
    {
        static $router       = null;
        static $menu_itemid  = null;
        static $menu_checked = false;

        if (is_null($router)) {
            require_once JPATH_SITE . '/components/com_users/helpers/route.php';
            $router = class_exists('UsersHelperRoute');
        }

        if (!$router) {
            return null;
        }

        if (!$menu_checked) {
            $menu_itemid  = UsersHelperRoute::getProfileRoute();
            $menu_checked = true;
        }


        // Create slug
        $slug = (int) $id . (empty($alias) ? '' : ':' . $alias);

        return 'index.php?option=com_users&view=profile&id=' . $slug . ($menu_itemid ? '&Itemid=' . $menu_itemid : '');
    }
}
