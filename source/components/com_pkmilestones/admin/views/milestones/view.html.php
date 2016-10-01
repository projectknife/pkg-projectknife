<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKmilestonesViewMilestones extends JViewLegacy
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
     * Access filter options
     *
     * @var    array
     */
    protected $access_options;

    /**
     * Progress filter options
     *
     * @var    array
     */
    protected $progress_options;

    /**
     * Project filter options
     *
     * @var    array
     */
    protected $project_options;

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
        $this->progress_options = $this->get('ProgressOptions');
        $this->tag_options      = $this->get('TagOptions');
        $this->sort_options     = $this->get('SortOptions');

        // Check for errors
        $errors = $this->get('Errors');

        if (count($errors)) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }


        if ($this->getLayout() !== 'modal') {
            PKmilestonesHelper::addSubmenu('milestones');

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
        JToolbarHelper::title(JText::_('COM_PKMILESTONES_MILESTONES_TITLE'));

        if (PKUserHelper::authProject('milestone.create', $project_id)) {
            JToolbarHelper::addNew('milestone.add');
            JToolbarHelper::custom('milestones.copy_dialog', 'copy', 'copy', JText::_('JLIB_HTML_BATCH_COPY'));
        }

        if (!$this->state->get('restrict.published')) {
            JToolbarHelper::publish('milestones.publish', 'JTOOLBAR_PUBLISH', true);
            JToolbarHelper::unpublish('milestones.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            JToolbarHelper::archiveList('milestones.archive');

            if ($this->state->get('filter.published') == -2) {
    			JToolbarHelper::deleteList('', 'milestones.delete', 'JTOOLBAR_EMPTY_TRASH');
    		}
    		else {
    			JToolbarHelper::trash('milestones.trash');
    		}
        }

        JToolbarHelper::checkin('milestones.checkin');

        if ($user->authorise('core.admin', 'com_pkmilestones') || $user->authorise('core.options', 'com_pkmilestones')) {
            JToolbarHelper::preferences('com_pkmilestones');
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

        JHtmlSidebar::setAction('index.php?option=com_pkmilestones&view=milestones');

        // Trigger BeforeDisplayFilter event. Params: Context, location, model
        $dispatcher->trigger('onProjectknifeBeforeDisplayFilter', array('com_pkmilestones.milestones', 'admin', &$this));

        // Project filter
        JHtmlSidebar::addFilter(
            JText::_('COM_PKPROJECTS_OPTION_SELECT_PROJECT'),
            'filter_project_id',
            JHtml::_('select.options', $this->project_options, 'value', 'text', $this->state->get('filter.project_id'))
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

        // Access filter
        JHtmlSidebar::addFilter(
            JText::_('JOPTION_SELECT_ACCESS'),
            'filter_access',
            JHtml::_('select.options', $this->access_options, 'value', 'text', $this->state->get('filter.access'))
        );

        // Trigger AfterDisplayFilter event. Params: Context, location, model
        $dispatcher->trigger('onProjectknifeAfterDisplayFilter', array('com_pkmilestones.milestones', 'admin', &$this));
    }
}
