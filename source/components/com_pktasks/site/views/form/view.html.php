<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKTasksViewForm extends JViewLegacy
{
    /**
     * Instance of JForm
     *
     * @var    object
     */
    protected $form;

    /**
     * Item record object
     *
     * @var    object
     */
    protected $item;

    /**
     * Model state
     *
     * @var    object
     */
    protected $state;

    /**
     * Toolbar html
     *
     * @var    string
     */
    protected $toolbar;

    /**
     * Return URL string
     *
     * @var    string
     */
    protected $return;


    /**
     * Execute and display a template script.
     *
     * @param     string    $tpl    The name of the template file to parse.
     *
     * @return    mixed             A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $this->form    = $this->get('Form');
        $this->item    = $this->get('Item');
        $this->state   = $this->get('State');
        $this->toolbar = $this->getToolbar();
        $this->return  = $this->get('ReturnPage');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }


        // Check viewing access
        if ($this->item->id > 0 && !PKUserHelper::isSuperAdmin()) {
            $user     = JFactory::getUser();
            $levels   = $user->getAuthorisedViewLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($this->item->access, $levels) && !in_array($this->item->project_id, $projects)) {
                JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                return;
            }
        }


        // Check create/edit permission
        if ($this->item->id == 0) {
            if ($this->item->project_id) {
                if (!PKUserHelper::authProject('task.create', $this->item->project_id)) {
                    JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                    return;
                }
            }
            else {
                if (!PKUserHelper::authProject('task.create', 'any')) {
                    JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                    return;
                }
            }
        }
        elseif (!PKUserHelper::authProject('task.edit', $this->item->project_id)) {
            if (!PKUserHelper::authProject('task.edit.own', $this->item->project_id)) {
                JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                return;
            }

            $user = JFactory::getUser();

            if ($user->id != $this->item->created_by || $user->id <= 0) {
                JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
                return;
            }
        }

        parent::display($tpl);
    }


    /**
     * Generates the toolbar for the top of the view
     *
     * @return    string    Toolbar with buttons
     */
    protected function getToolbar()
    {
        PKToolbar::menu('main');
            PKToolbar::btnTask($this->getName() . '.save', JText::_('JSAVE'), false, array('icon' => 'ok'));
            PKToolbar::btnTask($this->getName() . '.save2new', JText::_('PKGLOBAL_SAVE_AND_NEW'), false, array('icon' => 'plus'));
            PKToolbar::btnTask($this->getName() . '.cancel', JText::_('JCANCEL'), false, array('icon' => 'cancel'));
        PKToolbar::menu();

        return PKToolbar::render(true);
    }
}
