<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKmilestonesHelperRoute
{
    /**
     * Get the list route.
     *
     * @return    string    The milestone list route.
     */
    public static function getListRoute()
    {
        $link = 'index.php?option=com_pkmilestones&view=list';

        if ($item = PKRouteHelper::getMenuItemId('com_pkmilestones', 'list')) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }


    /**
     * Get the item route.
     *
     * @param     string    $slug            The milestone id slug
     * @param     string    $project_slug    The project id slug
     *
     * @return    string                     The milestone item route.
     */
    public static function getItemRoute($slug, $project_slug)
    {
        $link = 'index.php?option=com_pkmilestones&view=item&id=' . $slug . '&filter_project_id=' . $project_slug;

        if ($item = PKRouteHelper::getMenuItemId('com_pkmilestones', 'item', array($slug))) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }


    /**
     * Get the form route.
     *
     * @param     string    $slug            The milestone id slug
     * @param     string    $project_slug    The project id slug
     *
     * @return    string                     The milestone form route.
     */
    public static function getFormRoute($slug = null, $project_slug = null)
    {
        $link = 'index.php?option=com_pkmilestones&task=form.edit';

        if ($slug) {
            $link .= "&id=" . $slug;
        }

        if ($project_slug) {
            $link .= "&filter_project_id=" . $project_slug;
        }

        if ($item = PKRouteHelper::getMenuItemId('com_pkmilestones', 'form')) {
            $link .= '&Itemid=' . $item;
        }

        return $link;
    }
}
