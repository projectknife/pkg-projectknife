<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKMilestonesRouter extends JComponentRouterBase
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
        if ($menu_item_given && isset($menu_item) && $menu_item->component != 'com_pkmilestones') {
            $menu_item_given = false;
            unset($query['Itemid']);
        }

        if (!$menu_item_given) {
            return $segments;
        }

        if (isset($query['view'])) {
            $view = $query['view'];
        }
        else {
            // We need to have a view in the query or it is an invalid URL
            return $segments;
        }

        // Handle the item view
        if ($view == 'item')
        {
            unset($query['view']);

            if (isset($query['filter_project_id'])) {
                // Check if we have the alias as part of the slug. If not, load it.
                if (strpos($query['filter_project_id'], ':') === false) {
                    $db  = JFactory::getDbo();
                    $dbq = $db->getQuery(true);

    				$dbq->select('alias')
                        ->from('#__pk_projects')
                        ->where('id = ' . (int) $query['filter_project_id']);

    				$db->setQuery($dbq);
    				$query['id'] = $query['id'] . ':' . $db->loadResult();
                }

                // Add the alias to the URL
                list($tmp, $id) = explode(':', $query['filter_project_id'], 2);

                $segments[] = $id;
                unset($query['filter_project_id']);
            }
            else {
                $segments[] = '0';
            }

            if (isset($query['id'])) {
                // Check if we have the alias as part of the slug. If not, load it.
                if (strpos($query['id'], ':') === false) {
                    $db  = JFactory::getDbo();
                    $dbq = $db->getQuery(true);

    				$dbq->select('alias')
                        ->from('#__pk_milestones')
                        ->where('id = ' . (int) $query['id']);

    				$db->setQuery($dbq);
    				$query['id'] = $query['id'] . ':' . $db->loadResult();
                }

                // Add the alias to the URL
                list($tmp, $id) = explode(':', $query['id'], 2);

                $segments[] = $id;
                unset($query['id']);
            }

            if (isset($query['layout'])) {
                unset($query['layout']);
            }

            return $segments;
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

        // 2 segments = item view
        if ($total == 2) {
            $vars['view'] = 'item';

            if (strval(intval($segments[0])) === strval($segments[0])) {
                $vars['filter_project_id'] = intval($segments[0]);
            }
            else {
                $db  = JFactory::getDbo();
                $dbq = $db->getQuery(true);

				$dbq->select('id')
				    ->from('#__pk_projects')
				    ->where('alias = ' . $db->quote($segments[0]));

				$db->setQuery($dbq);
				$vars['filter_project_id'] = intval($db->loadResult());
            }

            if (strval(intval($segments[1])) === strval($segments[1])) {
                $vars['id'] = intval($segments[1]);
            }
            else {
                $db  = JFactory::getDbo();
                $dbq = $db->getQuery(true);

				$dbq->select('id')
				    ->from('#__pk_milestones')
				    ->where('alias = ' . $db->quote($segments[1]));

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
function pkmilestonesBuildRoute(&$query)
{
    $router = new PKMilestonesRouter();

    return $router->build($query);
}


/**
 * Proxy for the new router interface for old SEF extensions.
 *
 * @param     array    $segments    The segments of the URL to parse.
 *
 * @return    array                 The URL attributes to be used by the application.
 */
function pkmilestonesParseRoute($segments)
{
    $router = new PKMilestonesRouter();

    return $router->parse($segments);
}
