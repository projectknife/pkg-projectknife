<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_system_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class plgSystemProjectknifeInstallerScript
{
    public function uninstall(JAdapterInstance $adapter)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Find the menu id
        $query->select('id')
              ->from('#__menu_types')
              ->where('menutype = ' . $db->quote('projectknife'));

        $db->setQuery($query);
        $menu_id = (int) $db->loadResult();

        // Delete menu if exists
        if ($menu_id) {
            JLoader::register('MenusModelMenu', JPATH_ADMINISTRATOR . '/components/com_menus/models/menu.php');

            $menu_model = new MenusModelMenu(array('ignore_request' => true));
            $menu_model->delete(array($menu_id));
        }
    }


    public function postflight($type, JAdapterInstance $adapter)
    {
        if ($type == 'install')
        {
            $app = JFactory::getApplication();

            // Get the extension id
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('extension_id')
                  ->from('#__extensions')
                  ->where('element = ' . $db->quote('projectknife'))
                  ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                  ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));

            $db->setQuery($query);
            $id = (int) $db->loadResult();


            // Publish the plugin
            $query->clear()
                  ->update('#__extensions')
                  ->set('enabled = 1')
                  ->where('extension_id = ' . $id);

            $db->setQuery($query);
            $db->execute();


            // Check if Projectknife menu exists
            $query->clear()
                  ->select('id')
                  ->from('#__menu_types')
                  ->where('menutype = ' . $db->quote('projectknife'));

            $db->setQuery($query);
            $menu_id = (int) $db->loadResult();


            // Create Projectknife menu, if not exists
            if (!$menu_id) {
                JLoader::register('MenusModelMenu', JPATH_ADMINISTRATOR . '/components/com_menus/models/menu.php');
                $menu_model = new MenusModelMenu(array('ignore_request' => true));

                // Create the menu
                $data = array('title'       => 'Projectknife',
                              'menutype'    => 'projectknife',
                              'description' => 'Projectknife Menu');

                if ($menu_model->save($data)) {
                    // Create the module
                    $menu_id = (int) $menu_model->getState('menu.id');
                    $module  = JTable::getInstance('module');

                    $module->set('title',    'Projectknife');
                    $module->set('module',   'mod_menu');
                    $module->set('access',    1);
                    $module->set('published', 1);
                    $module->set('showtitle', 1);
                    $module->set('client_id', 0);
                    $module->set('language',  '*');
                    $module->set('position',  'position-7');
                    $module->set('params',    '{"menutype":"projectknife"}');

                    if ($module->store()) {
                        $module_id = (int) $module->get('id');

                        // Display on all pages
                        if ($module_id && $menu_id) {
                            $record           = new stdClass();
                            $record->moduleid = $module_id;
                            $record->menuid   = 0;

                            $db->insertObject('#__modules_menu', $record);
                        }
                    }
                }
            }
        }

        return true;
    }
}
