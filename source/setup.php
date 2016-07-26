<?php
/**
 * @package      pkg_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class pkg_projectknifeInstallerScript
{
    public function uninstall(JAdapterInstance $adapter)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__pk_extensions');

        $db->setQuery($query);
        $pks = $db->loadColumn();

        $query->clear()
              ->update('#__extensions')
              ->set('protected = 0')
              ->where('extension_id IN(' . implode(', ', $pks) . ')');

        $db->setQuery($query);
        $db->execute();
    }


    public function postflight($type, JAdapterInstance $adapter)
    {
        if ($type == 'install') {
            $files = $adapter->manifest->files->file;
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $types = array(
                'libraries'  => 'library',
                'components' => 'component',
                'modules'    => 'module',
                'plugins'    => 'plugin'
            );

            $pks = array();

            foreach ($files AS $path)
            {
                $parts    = explode('/', $path);
                $type_key = $parts[0];
                $type     = $types[$type_key];
                $element  = $parts[(count($parts) - 1)];

                if ($type == 'plugin') {
                    $element_parts = explode('_', $element, 3);
                }


                // Get the extension id
                $query->clear();
                $query->select('extension_id')
                      ->from('#__extensions')
                      ->where($db->quoteName('type') . ' = ' . $db->quote($type));

                if ($type == 'plugin') {
                    $query->where('element = ' . $db->quote($element_parts[2]))
                          ->where('folder = ' . $db->quote($element_parts[1]));
                }
                else {
                    $query->where('element = ' . $db->quote($element));
                }

                $db->setQuery($query);
                $id = (int) $db->loadResult();

                if (!$id) {
                    continue;
                }


                // Set as protected
                $query->clear()
                      ->update('#__extensions')
                      ->set('protected = 1')
                      ->where('extension_id = ' . $id);

                $db->setQuery($query);
                $db->execute();


                // Check if already registered
                $query->clear()
                      ->select('id')
                      ->from('#__pk_extensions')
                      ->where('id = ' . $id);

                $db->setQuery($query);
                $exists = (int) $db->loadResult();

                if ($exists) {
                    continue;
                }


                // Register extension
                $ext             = new stdClass();
                $ext->id         = $id;
                $ext->admin_view = '';
                $ext->ordering   = 0;

                $db->insertObject('#__pk_extensions', $ext);
            }
        }

        return true;
    }
}