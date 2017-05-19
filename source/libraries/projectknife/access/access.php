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


/**
 * Projectknife Access Helper Class
 *
 */
abstract class PKAccess
{
    /**
     * Current user id
     *
     * @var    integer
     */
    protected static $user_id = null;

    /**
     * Cached project permission check results
     *
     * @var    array
     */
    protected static $project_cache = array();

    /**
     * Cached project category permission check results
     *
     * @var    array
     */
    protected static $category_cache = array();


    /**
     * Method to check if a user is authorised to perform an action, optionally on an item id.
     *
     * @param     array      $cache      The permission cache
     * @param     string     $context    The asset context
     * @param     integer    $user_id    Id of the user for which to check authorisation.
     * @param     string     $action     The name of the action to authorise.
     * @param     mixed      $item_id    Item id. Defaults to the global asset node.
     *
     * @return    boolean                True if authorised.
     */
    protected static function checkItem(&$cache, $context, $user_id, $action, $item_id = 0)
    {
        $str_id  = strval($item_id);
        $user_id = (int) $user_id;
        $item_id = (int) $item_id;

        if ($context == "project" && $str_id == "any") {
            $asset = 'com_pkprojects.' . $context . '.' . $item_id;
        }
        elseif ($item_id > 0) {
            $asset = 'com_pkprojects.' . $context . '.' . $item_id;
        }
        else {
            $asset = 'com_pkprojects';
        }

        if (!isset($cache[$user_id])) {
            $cache[$user_id] = array();
        }

        if (!isset($cache[$user_id][$asset])) {
            $cache[$user_id][$asset] = array();
        }

        if (array_key_exists($action, $cache[$user_id][$asset])) {
            return $cache[$user_id][$asset][$action];
        }

        // Check any project
        if ($context == 'project' && $str_id == 'any') {
            $db    = JFactory::getDbo();
            $user  = JFactory::getUser();
            $query = $db->getQuery(true);

            $levels   = $user->getAuthorisedViewLevels();
            $projects = PKUserHelper::getProjects();

            $query->select('id')
                  ->from('#__pk_projects')
                  ->where('(access IN(' . implode(', ', $levels) . ') OR id IN(' . implode(', ', $projects) . '))')
                  ->order('id ASC');

            try {
                $db->setQuery($query);
                $list = $db->loadColumn();
            }
            catch (RuntimeException $e) {
                throw new RuntimeException('Failed to retrieve authorised project list because of a database error.', 500, $e);
            }

            $result = null;

            foreach ($list AS $project_id)
            {
                $result = self::checkItem($cache, $context, $user_id, $action, $project_id);

                if ($result === true) {
                    break;
                }
            }

            if ($result !== true) {
                $result = false;
            }

            $cache[$user_id][$asset][$action] = $result;

            return $result;
        }

        $result = JAccess::check($user_id, $action, $asset);

        $cache[$user_id][$asset][$action] = $result;

        return $result;
    }


    /**
     * Method to check if a user is authorised to perform an action, optionally on a project.
     *
     * @param     integer    $user_id    Id of the user for which to check authorisation.
     * @param     string     $action     The name of the action to authorise.
     * @param     mixed      $item_id    Integer project id. Defaults to the global asset node.
     *
     * @return    boolean                True if authorised.
     */
    public static function checkProject($user_id, $action, $item_id = 0)
    {
        return self::checkItem(self::$project_cache, 'project', $user_id, $action, $item_id);
    }


    /**
     * Method to check if a user is authorised to perform an action, optionally on a category.
     *
     * @param     integer    $user_id    Id of the user for which to check authorisation.
     * @param     string     $action     The name of the action to authorise.
     * @param     mixed      $item_id    Integer category id. Defaults to the global asset node.
     *
     * @return    boolean                True if authorised.
     */
    public static function checkCategory($user_id, $action, $item_id = 0)
    {
        return self::checkItem(self::$category_cache, 'category', $user_id, $action, $item_id);
    }
}
