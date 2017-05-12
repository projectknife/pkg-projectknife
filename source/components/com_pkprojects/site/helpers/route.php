<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKProjectsHelperRoute
{
    /**
     * Get the list route.
     *
     * @return    string    The project list route.
     */
    public static function getListRoute()
    {
        $link = 'index.php?option=com_pkprojects&view=list'
              . '&Itemid=' . intval(PKRouteHelper::getMenuItemId('com_pkprojects', 'list'));

        return $link;
    }


    /**
     * Get the item route.
     *
     * @param     string    $slug    The project id slug
     *
     * @return    string             The project item route.
     */
    public static function getItemRoute($slug)
    {
        $link = 'index.php?option=com_pkdashboard&&view=overview&id=' . $slug
              . '&Itemid=' . intval(PKRouteHelper::getMenuItemId('com_pkdashboard', 'overview'));

        return $link;
    }


    /**
     * Get the form route.
     *
     * @param     string    $slug    The project id slug
     *
     * @return    string             The project form route.
     */
    public static function getFormRoute($slug = null)
    {
        $link = 'index.php?option=com_pkprojects&task=form.edit';

        if ($slug) {
            $link .= "&id=" . $slug;
        }

        $link .= '&Itemid=' . intval(PKRouteHelper::getMenuItemId('com_pkprojects', 'form'));

        return $link;
    }
}
