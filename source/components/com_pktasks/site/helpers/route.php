<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKtasksHelperRoute
{
    protected static $lookup = array();


    /**
     * Get the list route.
     *
     * @return    string    The project list route.
     */
    public static function getListRoute()
    {
        $link = 'index.php?option=com_pktasks&view=list';

        return $link;
    }


    /**
     * Get the item route.
     *
     * @param     string  $slug The project id slug
     *
     * @return    string    The project list route.
     */
    public static function getItemRoute($slug)
    {
        $link = 'index.php?option=com_pktasks&view=item&id=' . $slug;

        if ($item = PKRouteHelper::getMenuItemId('com_pktasks', 'item', array($slug))) {
            $link .= '&Itemid=' . $item;
        }
        else {
            $link .= '&Itemid=' . PKRouteHelper::getMenuItemId('active');
        }

        return $link;
    }


    /**
     * Get the form route.
     *
     * @param     string  $slug The project id slug
     *
     * @return    string    The project form route.
     */
    public static function getFormRoute($slug = null)
    {
        $link = 'index.php?option=com_pktasks&task=form.edit';

        if ($slug) {
            $link .= "&id=" . $slug;
        }

        if ($item = self::_findItem()) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }


    /**
     * Find an item ID.
     *
     * @param     array    $needles    An array of language codes.
     *
     * @return    mixed                The ID found or null otherwise.
     */
    protected static function _findItem($needles = null)
    {
        $app      = JFactory::getApplication();
        $menus    = $app->getMenu('site');
        $language = isset($needles['language']) ? $needles['language'] : '*';

        // Prepare the reverse lookup array.
        if (!isset(self::$lookup[$language]))
        {
            self::$lookup[$language] = array();

            $component  = JComponentHelper::getComponent('com_pktasks');

            $attributes = array('component_id');
            $values     = array($component->id);

            if ($language != '*') {
                $attributes[] = 'language';
                $values[]     = array($needles['language'], '*');
            }

            $items = $menus->getItems($attributes, $values);

            foreach ($items as $item)
            {
                if (isset($item->query) && isset($item->query['view'])) {
                    $view = $item->query['view'];

                    if (!isset(self::$lookup[$language][$view])) {
                        self::$lookup[$language][$view] = array();
                    }

                    if (isset($item->query['id'])) {
                        /**
                         * Here it will become a bit tricky
                         * language != * can override existing entries
                         * language == * cannot override existing entries
                         */
                        if (!isset(self::$lookup[$language][$view][$item->query['id']]) || $item->language != '*') {
                            self::$lookup[$language][$view][$item->query['id']] = $item->id;
                        }
                    }
                }
            }
        }

        if ($needles) {
            foreach ($needles as $view => $ids)
            {
                if (isset(self::$lookup[$language][$view])) {
                    foreach ($ids as $id) {
                        if (isset(self::$lookup[$language][$view][(int) $id])) {
                            return self::$lookup[$language][$view][(int) $id];
                        }
                    }
                }
            }
        }

        // Check if the active menuitem matches the requested language
        $active = $menus->getActive();

        if ($active && $active->component == 'com_pktasks' && ($language == '*' || in_array($active->language, array('*', $language)) || !JLanguageMultilang::isEnabled())) {
            return $active->id;
        }

        // If not found, return language specific home link
        $default = $menus->getDefault($language);

        return !empty($default->id) ? $default->id : null;
    }
}
