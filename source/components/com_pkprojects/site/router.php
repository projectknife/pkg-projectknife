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


class PKProjectsRouter extends JComponentRouterBase
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
        if ($menu_item_given && isset($menu_item) && $menu_item->component != 'com_pkprojects') {
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

        // Deal with the form view
        if ($view == 'form')
        {
            unset($query['view']);

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

        /*for ($i = 0; $i < $total; $i++)
        {
            $segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);
        }*/

        if ($total >= 1) {
            $vars['view']   = 'form';
            $vars['layout'] = 'edit';

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
function pkprojectsBuildRoute(&$query)
{
    $router = new PKProjectsRouter();

    return $router->build($query);
}


/**
 * Proxy for the new router interface for old SEF extensions.
 *
 * @param     array    $segments    The segments of the URL to parse.
 *
 * @return    array                 The URL attributes to be used by the application.
 */
function pkprojectsParseRoute($segments)
{
    $router = new PKProjectsRouter();

    return $router->parse($segments);
}
