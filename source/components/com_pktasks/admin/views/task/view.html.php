<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();


class PKtasksViewTask extends JViewLegacy
{
    protected $form;

    protected $item;

    protected $state;


    /**
     * Execute and display a template script.
     *
     * @param     string    $tpl    The name of the template file to parse.
     *
     * @return    mixed             A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $this->addToolbar();

        parent::display($tpl);
    }


    /**
     * Add the page title and toolbar.
     *
     * @return    void
     */
    protected function addToolbar()
    {
        $user = JFactory::getUser();
        $uid  = $user->get('id');
        $new  = ($this->item->id == 0);

        $checked_out = !($this->item->checked_out == 0 || $this->item->checked_out == $uid);

        JFactory::getApplication()->input->set('hidemainmenu', true);

        JToolbarHelper::title(
            JText::_('COM_PKTASKS_PAGE_' . ($checked_out ? 'VIEW_TASK' : ($new ? 'ADD_TASK' : 'EDIT_TASK'))),
            'pencil-2 article-add'
        );

        JToolbarHelper::apply('task.apply');
        JToolbarHelper::save('task.save');
        JToolbarHelper::save2new('task.save2new');
        JToolbarHelper::cancel('task.cancel');
    }
}
