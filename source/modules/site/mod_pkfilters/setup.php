<?php
/**
 * @package      pkg_projectknife
 * @subpackage   mod_pkfilters
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class mod_PKFiltersInstallerScript
{
    public function postflight($type, JAdapterInstance $adapter)
    {
        if ($type == 'install')
        {
            // Get the module id
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__modules')
                  ->where('module = ' . $db->quote('mod_pkfilters'));

            $db->setQuery($query);
            $id = (int) $db->loadResult();

            if ($id) {
                // Publish the module
                $query->clear()
                      ->update('#__modules')
                      ->set('published = 1')
                      ->set('position = ' . $db->quote('position-7'))
                      ->where('id = ' . $id);

                $db->setQuery($query);
                $db->execute();

                $query->clear()
                      ->select('moduleid')
                      ->from('#__modules_menu')
                      ->where('moduleid = ' . $id);

                $db->setQuery($query);
                $exists = (int) $db->loadResult();

                if (!$exists) {
                    // Display on all projectknife menu items
                    $query->clear()
                          ->select('id')
                          ->from('#__menu')
                          ->where('menutype = ' . $db->quote('projectknife'));

                    $db->setQuery($query);
                    $menu_items = $db->loadColumn();

                    $obj = new stdClass();
                    $obj->moduleid = $id;
                    $obj->menuid   = 0;

                    foreach ($menu_items AS $item_id)
                    {
                        $obj->menuid = (int) $item_id;
                        $db->insertObject('#__modules_menu', $obj);
                    }
                }
            }
        }

        return true;
    }
}
