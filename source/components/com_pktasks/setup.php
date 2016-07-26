<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class com_pktasksInstallerScript
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
                  ->where('element = ' . $db->quote('com_pktasks'))
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

            if ($exists) {
                return true;
            }

            // Register the extension
            $ext             = new stdClass();
            $ext->id         = $id;
            $ext->admin_view = 'tasks';
            $ext->ordering   = 4;

            $db->insertObject('#__pk_extensions', $ext);
        }

        return true;
    }
}