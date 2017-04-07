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
            // Check if we have the alias as part of the slug. If not, load it.
            if (strpos($query['id'], ':') === false) {
                $db  = JFactory::getDbo();
                $dbq = $db->getQuery(true);

				$dbq->select('alias')
                    ->from('#__pk_projects')
                    ->where('id = ' . (int) $query['id']);

				$db->setQuery($dbq);
				$query['id'] = $query['id'] . ':' . $db->loadResult();
            }

            // Add the alias to the URL
            list($tmp, $id) = explode(':', $query['id'], 2);

            $segments[] = $id;
            unset($query['id']);
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
        $total = count($segments);
        $vars  = array();

        if ($total >= 1) {
            if (strval(intval($segments[0])) === strval($segments[0])) {
                $vars['id'] = intval($segments[0]);
            }
            else {
                $db  = JFactory::getDbo();
                $dbq = $db->getQuery(true);

				$dbq->select('id')
				    ->from('#__pk_projects')
				    ->where('alias = ' . $db->quote($segments[0]));

				$db->setQuery($dbq);
				$vars['id'] = intval($db->loadResult());
            }
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
