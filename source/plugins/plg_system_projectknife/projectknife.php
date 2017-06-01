<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_system_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class plgSystemProjectknife extends JPlugin
{
    /**
     * Registers the projectknife library
     *
     * @return    void
     */
    public function onAfterInitialise()
    {
        // Include the library
        $pk_lib = JPATH_LIBRARIES . '/projectknife/library.php';

        if (file_exists($pk_lib)) {
            require_once($pk_lib);
        }
        else {
            $this->loadLanguage();
            JError::raiseError(500, JText::_('PLG_SYSTEM_PROJECTKNIFE_ERROR_LIB_NOT_FOUND'));
        }

        // Load language
        if (defined('PK_LIBRARY')) {
            $this->loadComponentLanguage();

            if (JFactory::getApplication()->isSite()) {
                // Load Joomla backend language to the frontend
                // JFactory::getLanguage()->load('', JPATH_ADMINISTRATOR);
            }
        }

        // Load Projectknife plugins for ajax requests
        $input = JFactory::getApplication()->input;

        if ($input->get('option') === 'com_ajax' && $input->get('plugin')) {
            JPluginHelper::importPlugin('projectknife');
        }
    }


    /**
     * Loads relevant Projectknife component language files
     *
     * @return    void
     */
    protected function loadComponentLanguage()
    {
        $names = PKApplicationHelper::getComponentNames();
        $app   = JFactory::getApplication();
        $lang  = JFactory::getLanguage();
        $path  = ($app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_SITE);
        $count = count($names);

        // Load library language file
        if (!$lang->load('lib_projectknife', JPATH_SITE)) {
            $lang->load('lib_projectknife', JPATH_SITE . '/libraries/projectknife');
        }

        // Load admin component .sys language files
        $path = JPATH_ADMINISTRATOR;
        // $path  = ($app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_SITE);

        for($i = 0; $i != $count; $i++)
        {
            // Try core files first, then local extension directory.
            if (!$lang->load($names[$i] . '.sys', $path)) {
                $lang->load($names[$i] . '.sys', $path . '/components/' . $names[$i]);
            }
        }

        if ($app->isSite()) {
            // Load site component .sys language files
            $path = JPATH_SITE;

            for($i = 0; $i != $count; $i++)
            {
                // Try core files first, then local extension directory.
                if (!$lang->load($names[$i] . '.sys', $path)) {
                    $lang->load($names[$i] . '.sys', $path . '/components/' . $names[$i]);
                }
            }
        }
    }
}
