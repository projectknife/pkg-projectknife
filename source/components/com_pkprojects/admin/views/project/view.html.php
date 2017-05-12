<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKprojectsViewProject extends JViewLegacy
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
            JText::_('COM_PKPROJECTS_PAGE_' . ($checked_out ? 'VIEW_PROJECT' : ($new ? 'ADD_PROJECT' : 'EDIT_PROJECT'))),
            'pencil-2 article-add'
        );

        JToolbarHelper::apply('project.apply');
        JToolbarHelper::save('project.save');
        JToolbarHelper::save2new('project.save2new');
        JToolbarHelper::cancel('project.cancel');
    }
}
