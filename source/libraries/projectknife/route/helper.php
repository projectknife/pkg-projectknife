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
    /**
     * Method to get the id that's part of the "slug" in the URL
     *
     * @param     string     $slug    The slug to parse
     *
     * @return    integer             
     */
    public function getSlugId($slug)
    {
        if (strpos($slug, ':') === false) {
            return (int) $slug;
        }

        list($id, $alias) = explode(':', $slug, 2);
        return (int) $id;
    }

}
