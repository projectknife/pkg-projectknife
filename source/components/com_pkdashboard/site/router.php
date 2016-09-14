<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkdashboard
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class PKDashboardRouter extends JComponentRouterBase
{
    /**
     * Build the route for the  component
     *
     * @param     array    $query    An array of URL arguments
     *
     * @return    array              The URL arguments to use to assemble the subsequent URL.
     */
    public function build(&$query)
    {
        $segments = array();


        // #1: We don't need the view, because there is only one
        if (isset($query['view'])) {
            unset($query['view']);
        }

        // #2: Get the Menu item
        if (isset($query['Itemid'])) {
            $menu_item = $this->menu->getItem($query['Itemid']);
            $menu_item_given = true;
        }
        else {
            $menu_item = $this->menu->getActive();
            $menu_item_given = false;
        }


        // #3: Check if the menu item id in the URL belongs to this component
        if ($menu_item_given && ($menu_item instanceof stdClass) && $menu_item->component != 'com_pkdashboard') {
            $menu_item_given = false;

            if (isset($query['Itemid'])) {
                unset($query['Itemid']);
            }
        }



        // #4: Check if the link leads to a project overview, which happens to be a menu item
        if ($menu_item_given && ($menu_item instanceof stdClass) && isset($query['id']) && isset($menu_item->query['id']) && $menu_item->query['id'] == (int) $query['id']) {
            unset($query['id']);

            return $segments;
        }


        // #5: Store the id var in the segments
        if (isset($query['id'])) {
            $segments[] = $query['id'];
            unset($query['id']);
        }


        // #5: Store the return var in the segments
        if (isset($query['return'])) {
            $segments[] = $query['return'];
            unset($query['return']);
        }

        return $segments;

        // OLD code below
        $segments = array();
        $view     = '';

        // We need a menu item. Either the one specified in the query, or the current active one if none specified
        if (empty($query['Itemid'])) {
            $menu_item = $this->menu->getActive();
            $menu_item_given = false;
        }
        else {
            $menu_item = $this->menu->getItem($query['Itemid']);
            $menu_item_given = true;
        }

        // Check again
        if ($menu_item_given && ($menu_item instanceof stdClass) && $menu_item->component != 'com_pkdashboard') {
            $menu_item_given = false;
            unset($query['Itemid']);
        }

        // Are we dealing with an article or category that is attached to a menu item?
        if (($menu_item instanceof stdClass) && $menu_item->query['view'] == $query['view'] && isset($query['id']) && $menu_item->query['id'] == (int) $query['id']) {
            unset($query['view'], $query['id']);

            return $segments;
        }

        if (isset($query['id'])) {
            $segments[] = $query['id'];
        }

        return $segments;
    }


    /**
     * Parse the segments of a URL.
     *
     * @param     array  $segments    The segments of the URL to parse.
     *
     * @return    array               The URL attributes to be used by the application.
     */
    public function parse(&$segments)
    {
        $vars  = array();

        $total = count($segments);
        $vars  = array();

        for ($i = 0; $i < $total; $i++)
        {
            // $segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);
        }


        if ($total >= 1) {
            $vars['id'] = PKRouteHelper::getSlugId($segments[0]);
        }

        if ($total >= 2) {
            $vars['return'] = $segments[1];
        }

        return $vars;
    }
}


/**
 * Proxy for the new router interface for old SEF extensions.
 *
 * @param     array  $query    An array of URL arguments
 *
 * @return    array            The URL arguments to use to assemble the subsequent URL.
 */
function pkdashboardBuildRoute(&$query)
{
    $router = new PKDashboardRouter();

    return $router->build($query);
}


/**
 * Proxy for the new router interface for old SEF extensions.
 *
 * @param     array    $segments    The segments of the URL to parse.
 *
 * @return    array                 The URL attributes to be used by the application.
 */
function pkdashboardParseRoute($segments)
{
    $router = new PKDashboardRouter();

    return $router->parse($segments);
}
