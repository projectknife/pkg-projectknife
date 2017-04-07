<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


JPluginHelper::importPlugin('content');


class PKtasksViewItem extends JViewLegacy
{
    /**
     * Item loaded by the model
     *
     * @var    array
     */
    protected $item;

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
     * Execute and display a template script.
     *
     * @param     string    $tpl    The name of the template file to parse
     *
     * @return    mixed             A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $app = JFactory::getApplication();

        $this->item    = $this->get('Item');
        $this->state   = $this->get('State');
        $this->params  = $app->getParams();
        $this->toolbar = $this->getToolbar();


        // Check for errors
        $errors = $this->get('Errors');

        if (count($errors)) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }


        // Check viewing access
        if (!PKUserHelper::isSuperAdmin()) {
            $user     = JFactory::getUser();
            $levels   = $user->getAuthorisedViewLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($this->item->access, $levels) && !in_array($this->item->project_id, $projects)) {
                JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                return;
            }
        }


        // Set active project
        PKApplicationHelper::setProjectId($this->item->project_id);


        // Prepare doc
        $this->prepareDocument();

        $this->item->text = '';

        // Process the content plugins.
		JPluginHelper::importPlugin('content');
        $dispatcher	= JDispatcher::getInstance();

        $offset  = 0;
		$results = $dispatcher->trigger('onContentPrepare', array ('com_pktasks.item', &$this->item, &$this->params, $offset));

		$this->item->event = new stdClass();
		$results = $dispatcher->trigger('onContentAfterTitle', array('com_pktasks.item', &$this->item, &$this->params, $offset));
		$this->item->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = $dispatcher->trigger('onContentBeforeDisplay', array('com_pktasks.item', &$this->item, &$this->params, $offset));
		$this->item->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = $dispatcher->trigger('onContentAfterDisplay', array('com_pktasks.item', &$this->item, &$this->params, $offset));
		$this->item->event->afterDisplayContent = trim(implode("\n", $results));


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

        // Set the page heading
        if ($this->item && $this->item->id >= 0) {
            $this->params->def('page_heading', $this->item->title);
        }
        else {
            $this->menu = $menus->getActive();

            if ($this->menu) {
                $this->params->def('page_heading', $this->params->get('page_title', $this->menu->title));
            }
            else {
                $this->params->def('page_heading', JText::_($this->defaultPageTitle));
            }
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
        $app          = JFactory::getApplication();
        $user         = JFactory::getUser();
        $can_edit     = PKUserHelper::authProject('core.edit.task', $this->item->project_id);
        $can_edit_own = (PKUserHelper::authProject('core.edit.own.task', $this->item->project_id) && $this->item->created_by == $user->id);
        $url_back     = $app->input->get('return', null, 'default', 'base64');

        if (empty($url_back) || !JUri::isInternal(base64_decode($url_back))) {
            $url_back = null;
        }

        // Main Menu
        PKToolbar::menu('main');
            if ($url_back) {
                PKToolbar::btnURL(base64_decode($url_back), JText::_('PKGLOBAL_RETURN'), array('icon' => 'chevron-left'));
            }

            // Edit button
            if ($can_edit || $can_edit_own) {
                $slug       = $this->item->id . ':' . $this->item->alias;
                $url_return = base64_encode('index.php?option=com_pktasks&view=item&id=' . $slug . '&Itemid=' . PKRouteHelper::getMenuItemId('active'));
                $item_form  = PKRouteHelper::getMenuItemId('com_pktasks', 'form');
                $url_edit   = JRoute::_('index.php?option=com_pktasks&task=form.edit&id=' . $slug . '&Itemid=' . $item_form . '&return=' . $url_return);

                PKToolbar::btnURL($url_edit, JText::_('JACTION_EDIT'), array('icon' => 'pencil'));
            }

        PKToolbar::menu();

        return PKToolbar::render(true);
    }
}
