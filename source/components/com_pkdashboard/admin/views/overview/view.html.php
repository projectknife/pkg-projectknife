<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkdashboard
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKdashboardViewOverview extends JViewLegacy
{
    /**
     * Sidebar HTML output
     *
     * @var    string
     */
    public $sidebar = '';


    /**
	 * Execute and display a template script.
	 *
	 * @param     string   $tpl     The name of the template file to parse
	 *
	 * @return    mixed             A string if successful, otherwise a Error object.
	 */
    public function display($tpl = null)
    {
        if ($this->getLayout() !== 'modal') {
            PKdashboardHelper::addSubmenu('overview');

            $this->addToolbar();

            $this->sidebar = JHtmlSidebar::render();
        }

        parent::display($tpl);
    }


    /**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */
    protected function addToolbar()
    {
        $user = JFactory::getUser();

        // Page title
        JToolbarHelper::title(JText::_('COM_PKDASHBOARD_DASHBOARD_TITLE'));

        // Configuration
        if ($user->authorise('core.admin', 'com_pkdashboard') || $user->authorise('core.options', 'com_pkdashboard')) {
			JToolbarHelper::preferences('com_pkdashboard');
		}
    }
}