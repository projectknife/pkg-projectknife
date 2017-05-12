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


use Joomla\Registry\Registry;


JPluginHelper::importPlugin('content');


class PKdashboardViewOverview extends JViewLegacy
{
    /**
     * Model state
     *
     * @var    jregistry
     */
    protected $state;

    /**
     * Application params
     *
     * @var    jregistry
     */
    protected $params;


    /**
     * Toolbar html
     *
     * @var    string
     */
    protected $toolbar;

    /**
     * The current project record
     *
     * @var    string
     */
    protected $item;


    /**
     * Execute and display a template script.
     *
     * @param     string    $tpl    The name of the template file to parse
     *
     * @return    mixed             A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $app = JFactory::getApplication();

        $this->state   = $this->get('State');
        $this->item    = $this->get('Item');
        $this->params  = $app->getParams();
        $this->toolbar = $this->getToolbar();

        // Set active project
        PKApplicationHelper::setProjectId($this->state->get($this->get('Name') . '.id'));

        // Check viewing access
        if ($this->item && $this->item->id > 0) {
            $user      = JFactory::getUser();
            $restrict  = (!$user->authorise('core.admin', 'com_pkprojects') && !$user->authorise('core.manage', 'com_pkprojects'));

            if ($restrict) {
                $auth_levels   = PKUserHelper::getAccessLevels();
                $auth_projects = PKUserHelper::getProjects();

                if (!in_array($this->item->access, $auth_levels) && !in_array($this->item->id, $auth_projects)) {
                     $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                     return;
                }
            }

            // Import plugins
    		JPluginHelper::importPlugin('content');

            $context    = 'com_pkdashboard.overview';
            $dispatcher	= JDispatcher::getInstance();

            // Trigger events
    		$results = $dispatcher->trigger('onContentPrepare', array ($context, &$this->item, &$this->params, 0));

    		$this->item->event = new stdClass();
    		$results = $dispatcher->trigger('onContentAfterTitle', array($context, &$this->item, &$this->params, 0));
    		$this->item->event->afterDisplayTitle = trim(implode("\n", $results));

    		$results = $dispatcher->trigger('onContentBeforeDisplay', array($context, &$this->item, &$this->params, 0));
    		$this->item->event->beforeDisplayContent = trim(implode("\n", $results));

    		$results = $dispatcher->trigger('onContentAfterDisplay', array($context, &$this->item, &$this->params, 0));
    		$this->item->event->afterDisplayContent = trim(implode("\n", $results));
        }

        // Prepare doc
        $this->prepareDocument();

        // Display
        parent::display($tpl);
    }


    /**
     * Method to prepare the document
     *
     * @return    void
     */
    protected function prepareDocument()
    {
        $app           = JFactory::getApplication();
        $menus         = $app->getMenu();
        $this->pathway = $app->getPathway();
        $title         = null;

        // Because the application sets a default page title, we need to get it from the menu item itself
        $this->menu = $menus->getActive();

        if ($this->menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $this->menu->title));
        }
        else {
            $this->params->def('page_heading', JText::_('COM_PKDASHBOARD_SUBMENU_OVERVIEW'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        }
        elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        }
        elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }


    protected function getToolbar()
    {
        if ((int) $this->params->get('show_toolbar', 1) == 0 || !$this->item || $this->item->id == null) {
            return '';
        }

        $app          = JFactory::getApplication();
        $user         = JFactory::getUser();
        $can_edit     = PKUserHelper::authProject('core.edit.project', $this->item->id);
        $can_edit_own = (PKUserHelper::authProject('core.edit.own.project', $this->item->id) && $this->item->created_by == $user->id);


        $url_back = $app->input->get('return', null, 'default', 'base64');

        if (empty($url_back) || !JUri::isInternal(base64_decode($url_back))) {
            $url_back = null;
        }

        PKToolbar::menu('main');
            // Back button
            if ($url_back) {
                PKToolbar::btnURL(base64_decode($url_back), JText::_('PKGLOBAL_RETURN'), array('icon' => 'chevron-left'));
            }

            // Edit button
            if ($can_edit || $can_edit_own) {
                $slug       = $this->item->id . ':' . $this->item->alias;
                $url_return = base64_encode('index.php?option=com_pkdashboard&view=overview&Itemid=' . PKRouteHelper::getMenuItemId('active'));
                $item_form  = PKRouteHelper::getMenuItemId('com_pkprojects', 'form');
                $url_edit   = JRoute::_('index.php?option=com_pkprojects&task=form.edit&id=' . $slug . '&Itemid=' . $item_form . '&return=' . $url_return);

                PKToolbar::btnURL($url_edit, JText::_('JGLOBAL_EDIT'), array('icon' => 'edit'));
            }
        PKToolbar::menu();

        return PKToolbar::render(true);
    }
}
