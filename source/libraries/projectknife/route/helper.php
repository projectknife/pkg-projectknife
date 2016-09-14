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
 * Projectknife Route Helper Class
 *
 */
abstract class PKRouteHelper
{
    // Holds the menu item id's
    protected static $menu_items = array();

    /**
     * Method to get the id that's part of the "slug" in the URL
     *
     * @param     string     $slug    The slug to parse
     *
     * @return    integer
     */
    public static function getSlugId($slug)
    {
        if (strpos($slug, ':') === false) {
            return (int) $slug;
        }

        list($id, $alias) = explode(':', $slug, 2);
        return (int) $id;
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
