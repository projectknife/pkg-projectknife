<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKprojectsHelperRoute
{
    /**
     * Get the list route.
     *
     * @return    string    The project list route.
     */
    public static function getListRoute()
    {
        $link = 'index.php?option=com_pkprojects&view=list';

        if ($item = PKApplicationHelper::getMenuItemId('com_pkprojects', 'list')) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }


    /**
     * Get the item route.
     *
     * @param     string  $slug The project id slug
     *
     * @return    string    The project item route.
     */
    public static function getItemRoute($slug)
    {
        $link = 'index.php?option=com_pkdashboard&&view=overview&id=' . $slug;

        if ($item = PKApplicationHelper::getMenuItemId('com_pkdashboard', 'overview')) {
            $link .= '&Itemid=' . $item;
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
        $link = 'index.php?option=com_pkprojects&task=form.edit';

        if ($slug) {
            $link .= "&id=" . $slug;
        }

        if ($item = PKApplicationHelper::getMenuItemId('com_pkprojects', 'form')) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }
}
