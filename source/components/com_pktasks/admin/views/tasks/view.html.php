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


class PKtasksViewTasks extends JViewLegacy
{
     /**
     * List of currently active filters
     *
     * @var    array
     */
    public $activeFilters;

    /**
     * Items loaded by the model
     *
     * @var    array
     */
    protected $items;

    /**
     * Pagination instance
     *
     * @var    jpagination
     */
    protected $pagination;

    /**
     * Model state
     *
     * @var    jregistry
     */
    protected $state;

    /**
     * Author filter options
     *
     * @var    array
     */
    protected $author_options;

    /**
     * Project filter options
     *
     * @var    array
     */
    protected $project_options;

    /**
     * Milestone filter options
     *
     * @var    array
     */
    protected $milestone_options;

    /**
     * Access level filter options
     *
     * @var    array
     */
    protected $access_options;

    /**
     * Assignee filter options
     *
     * @var    array
     */
    protected $assignee_options;

    /**
     * Priority filter options
     *
     * @var    array
     */
    protected $priority_options;

    /**
     * Progress filter options
     *
     * @var    array
     */
    protected $progress_options;

    /**
     * Tag filter options
     *
     * @var    array
     */
    protected $tag_options;

    /**
     * List sorting options
     *
     * @var    array
     */
    protected $sort_options;

    /**
     * Sidebar HTML output
     *
     * @var    string
     */
    protected $sidebar = '';


    /**
     * Execute and display a template script.
     *
     * @param     string    $tpl    The name of the template file to parse
     *
     * @return    mixed             A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->author_options   = $this->get('AuthorOptions');
        $this->project_options  = $this->get('ProjectOptions');
        $this->access_options   = $this->get('AccessOptions');
        $this->assignee_options = $this->get('AssigneeOptions');
        $this->priority_options = $this->get('PriorityOptions');
        $this->progress_options = $this->get('ProgressOptions');
        $this->tag_options      = $this->get('TagOptions');
        $this->sort_options     = $this->get('SortOptions');

        // Check for errors
        $errors = $this->get('Errors');

        if (count($errors)) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $model = $this->getModel();

        if (is_numeric($this->state->get('filter.project_id'))) {
            $filter = array('project_id' => $this->state->get('filter.project_id'));

            $this->milestone_options = $model->getMilestoneOptions($filter);
        }
        else {
            // If no project is selected, milestone options will be empty because there may be
            // too many options.
            $this->milestone_options = array();
        }


        if ($this->getLayout() !== 'modal') {
            PKtasksHelper::addSubmenu('tasks');

            $this->addToolbar();
            $this->addSidebar();
            $this->sidebar = JHtmlSidebar::render();
        }

        parent::display($tpl);
    }


    /**
     * Add the page title and toolbar.
     *
     * @return    void
     */
    protected function addToolbar()
    {
        $user       = JFactory::getUser();
        $project_id = (int) $this->state->get('filter.project_id');

        JHtml::_('bootstrap.modal', 'collapseModal');
        JToolbarHelper::title(JText::_('COM_PKTASKS_TASKS_TITLE'));

        if (PKUserHelper::authProject('task.create', $project_id)) {
            JToolbarHelper::addNew('task.add');
            JToolbarHelper::custom('tasks.copy_dialog', 'copy', 'copy', JText::_('JLIB_HTML_BATCH_COPY'));
        }

        if (!$this->state->get('restrict.published')) {
            JToolbarHelper::publish('tasks.publish', 'JTOOLBAR_PUBLISH', true);
            JToolbarHelper::unpublish('tasks.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            JToolbarHelper::archiveList('tasks.archive');

            if ($this->state->get('filter.published') == -2) {
    			JToolbarHelper::deleteList('', 'tasks.delete', 'JTOOLBAR_EMPTY_TRASH');
    		}
    		else {
    			JToolbarHelper::trash('tasks.trash');
    		}
        }

        JToolbarHelper::checkin('tasks.checkin');

        if ($user->authorise('core.admin', 'com_pktasks') || $user->authorise('core.options', 'com_pktasks')) {
            JToolbarHelper::preferences('com_pktasks');
        }
    }


    /**
     * Adds sidebar filters.
     *
     * @return    void
     */
    protected function addSidebar()
    {
        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        JHtmlSidebar::setAction('index.php?option=com_pktasks&view=tasks');

        // Trigger BeforeDisplayFilter event. Params: Context, location, model
        $dispatcher->trigger('onProjectknifeBeforeDisplayFilter', array('com_pktasks.tasks', 'admin', &$this));

        // Project filter
        JHtmlSidebar::addFilter(
            JText::_('COM_PKPROJECTS_OPTION_SELECT_PROJECT'),
            'filter_project_id',
            JHtml::_('select.options', $this->project_options, 'value', 'text', $this->state->get('filter.project_id'))
        );

        // Milestone filter
        $no_ms = new stdClass();
        $no_ms->value = '0';
        $no_ms->text  = '* ' . JText::_('COM_PKMILESTONES_OPTION_NO_MILESTONE') . ' *';

        JHtmlSidebar::addFilter(
            '- ' . JText::_('COM_PKMILESTONES_OPTION_SELECT_MILESTONE') . ' -',
            'filter_milestone_id',
            JHtml::_('select.options', array_merge(array($no_ms), $this->milestone_options), 'value', 'text', $this->state->get('filter.milestone_id'))
        );

        JHtmlSidebar::addFilter(
            '- ' . JText::_('PKGLOBAL_SELECT_TAG') . ' -',
            'filter_tag_id',
            JHtml::_('select.options', $this->tag_options, 'value', 'text', $this->state->get('filter.tag_id'))
        );

        // Publishing state filter
        if (!$this->state->get('restrict.published')) {
            JHtmlSidebar::addFilter(
                JText::_('JOPTION_SELECT_PUBLISHED'),
                'filter_published',
                JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
            );
        }

        // Assignee filter
        $unassigned = new stdClass();
        $unassigned->value = 'unassigned';
        $unassigned->text  = '* ' . JText::_('COM_PKTASKS_UNASSIGNED') . ' *';

        $me = new stdClass();
        $me->value = 'me';
        $me->text  = '* ' . JText::_('COM_PKTASKS_ASSIGNED_TO_ME') . ' *';

        $notme = new stdClass();
        $notme->value = 'notme';
        $notme->text  = '* ' . JText::_('COM_PKTASKS_NOT_ASSIGNED_TO_ME') . ' *';

        JHtmlSidebar::addFilter(
            '- ' . JText::_('COM_PKTASKS_SELECT_ASSIGNEE') . ' -',
            'filter_assignee_id',
            JHtml::_('select.options', array_merge(array($me, $notme, $unassigned), $this->assignee_options), 'value', 'text', $this->state->get('filter.assignee_id'))
        );

        // Priority filter
        JHtmlSidebar::addFilter(
            '- ' . JText::_('COM_PKTASKS_SELECT_PRIORITY') . ' -',
            'filter_priority',
            JHtml::_('select.options', $this->priority_options, 'value', 'text', $this->state->get('filter.priority'))
        );

        // Progress filter
        JHtmlSidebar::addFilter(
            '- ' . JText::_('PKGLOBAL_SELECT_PROGRESS') . ' -',
            'filter_progress',
            JHtml::_('select.options', $this->progress_options, 'value', 'text', $this->state->get('filter.progress'))
        );

        // Author filter
        $me = new stdClass();
        $me->value = 'me';
        $me->text  = '* ' . JText::_('PKGLOBAL_CREATED_BY_ME') . ' *';

        $notme = new stdClass();
        $notme->value = 'notme';
        $notme->text  = '* ' . JText::_('PKGLOBAL_NOT_CREATED_BY_ME') . ' *';

        JHtmlSidebar::addFilter(
            JText::_('JOPTION_SELECT_AUTHOR'),
            'filter_author_id',
            JHtml::_('select.options', array_merge(array($me, $notme), $this->author_options), 'value', 'text', $this->state->get('filter.author_id'))
        );

        // Access level filter
        JHtmlSidebar::addFilter(
            JText::_('JOPTION_SELECT_ACCESS'),
            'filter_access',
            JHtml::_('select.options', $this->access_options, 'value', 'text', $this->state->get('filter.access'))
        );


        // Trigger AfterDisplayFilter event. Params: Context, location, model
        $dispatcher->trigger('onProjectknifeAfterDisplayFilter', array('com_pktasks.tasks', 'admin', &$this));
    }
}
