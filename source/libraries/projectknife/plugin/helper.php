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


use Joomla\Registry\Registry;


/**
 * Projectknife Plugin Helper Class
 *
 */
abstract class PKPluginHelper extends JPluginHelper
{
    /**
     * Gets the parameter object for the plugin
     *
     * @param     string      $type      The plugin type
     * @param     string      $name      The plugin name
     * @param     boolean     $strict    If set and the plugin does not exist, false will be returned
     *
     * @return    registry               A Registry object.
     */
    public static function getParams($type, $name, $strict = false)
    {
        $plugin = static::getPlugin($type, $name);

        if (!is_object($plugin)) {
            if ($strict) {
                return false;
            }

            $params = new Registry();

            return $params;
        }

        $params = new Registry();

        $params->loadString($plugin->params);

        return $params;
    }
}
