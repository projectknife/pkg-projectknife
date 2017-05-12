<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKTasksHelperRoute
{
    /**
     * Get the list route.
     *
     * @return    string    The tasks list route.
     */
    public static function getListRoute()
    {
        $link = 'index.php?option=com_pktasks&view=list';

        if ($item = PKRouteHelper::getMenuItemId('com_pktasks', 'list')) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }


    /**
     * Get the item route.
     *
     * @param     string    $slug            The task slug
     * @param     string    $project_slug    The project slug
     *
     * @return    string                     The task item route.
     */
    public static function getItemRoute($slug, $project_slug)
    {
        $link = 'index.php?option=com_pktasks&view=item&id=' . $slug . '&filter_project_id=' . $project_slug;

        if ($item = PKRouteHelper::getMenuItemId('com_pktasks', 'item', array($slug))) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }


    /**
     * Get the form route.
     *
     * @param     string    $slug            The task slug
     * @param     string    $project_slug    The project slug
     *
     * @return    string                     The task form route.
     */
    public static function getFormRoute($slug = null, $project_slug = null)
    {
        $link = 'index.php?option=com_pktasks&task=form.edit';

        if ($slug) {
            $link .= "&id=" . $slug;
        }

        if ($project_slug) {
            $link .= "&filter_project_id=" . $project_slug;
        }

        if ($item = PKRouteHelper::getMenuItemId('com_pktasks', 'form')) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }
}
