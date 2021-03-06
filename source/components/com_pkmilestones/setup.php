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


class com_pkmilestonesInstallerScript
{
    public function postflight($type, JAdapterInstance $adapter)
    {
        if ($type == 'install')
        {
            // Get the extension id
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('extension_id')
                  ->from('#__extensions')
                  ->where('element = ' . $db->quote('com_pkmilestones'))
                  ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

            $db->setQuery($query);
            $id = (int) $db->loadResult();


            // Check if exists
            $query->clear()
                  ->select('id')
                  ->from('#__pk_extensions')
                  ->where('id = ' . $id);

            $db->setQuery($query);
            $exists = (int) $db->loadResult();

            // Register the extension
            if (!$exists) {
                $ext             = new stdClass();
                $ext->id         = $id;
                $ext->admin_view = 'milestones';
                $ext->ordering   = 3;

                $db->insertObject('#__pk_extensions', $ext);
            }


            // Check if Projectknife menu exists
            $query->clear()
                  ->select('id')
                  ->from('#__menu_types')
                  ->where('menutype = ' . $db->quote('projectknife'));

            $db->setQuery($query);
            $menu_id = (int) $db->loadResult();

            // Create nav menu item
            if ($menu_id) {
                $data = array(
                    'title'        => 'Milestones',
                    'alias'        => 'milestones',
                    'link'         => 'index.php?option=com_pkmilestones&view=list',
                    'component_id' => $id,
                    'menutype'     => 'projectknife',
                    'parent_id'    => 1,
                    'level'        => 1,
                    'published'    => 1,
                    'type'         => 'component',
                    'language'     => '*',
                    'access'       => 1,
                    'params'       => '{}',
                    'ordering'     => 0,
                    'id'           => null
                );

                $menu = JTable::getInstance('menu');
                $menu->setLocation(1, 'last-child');

                if ($menu->bind($data) && $menu->check() && $menu->store()) {
                    $query->clear();
                    $query->update('#__menu')
                          ->set('parent_id = 1')
                          ->set('level = 1')
                          ->where('id = ' . (int) $menu->id);

                    $db->setQuery($query);
                    $db->execute();

                    $menu->parent_id = 1;
                    $menu->level     = 1;

                    $menu->setLocation(1, 'last-child');
                    $menu->rebuildPath($menu->id);
                }
            }
        }

        return true;
    }
}