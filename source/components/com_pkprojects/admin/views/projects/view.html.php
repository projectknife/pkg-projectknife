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


class PKprojectsViewProjects extends JViewLegacy
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
     * @var    object
     */
    protected $pagination;

    /**
     * Model state
     *
     * @var    object
     */
    protected $state;

    /**
     * Author filter options
     *
     * @var    array
     */
    protected $author_options;

    /**
     * Category filter options
     *
     * @var    array
     */
    protected $category_options;

    /**
     * Progress filter options
     *
     * @var    array
     */
    protected $progress_options;

    /**
     * Access level filter options
     *
     * @var    array
     */
    protected $access_options;

    /**
     * Tag filter options
     *
     * @var    array
     */
    protected $tag_options;

    /**
     * List sorting options
     *
     * @var array
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
        $this->category_options = $this->get('CategoryOptions');
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
            PKprojectsHelper::addSubmenu('projects');

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
        JHtml::_('bootstrap.modal', 'collapseModal');

        // Page title
        JToolbarHelper::title(JText::_('COM_PKPROJECTS_PROJECTS_TITLE'));

        // Create and copy action
        if (PKUserHelper::authProject('core.create')) {
            JToolbarHelper::addNew('project.add');
            JToolbarHelper::custom('projects.copy_dialog', 'copy', 'copy', JText::_('JLIB_HTML_BATCH_COPY'));
        }

        // Publishing actions
        if (PKUserHelper::authProject('core.edit.state') || PKUserHelper::authProject('core.edit.state.own')) {
            JToolbarHelper::publish('projects.publish', 'JTOOLBAR_PUBLISH', true);
            JToolbarHelper::unpublish('projects.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            JToolbarHelper::archiveList('projects.archive');
        }


        if ($this->state->get('filter.published') == -2) {
            if (PKUserHelper::authProject('core.delete') || PKUserHelper::authProject('core.delete.own')) {
                JToolbarHelper::deleteList('', 'projects.delete', 'JTOOLBAR_EMPTY_TRASH');
            }
        }
        else {
            if (PKUserHelper::authProject('core.edit.state') || PKUserHelper::authProject('core.edit.state.own')) {
                JToolbarHelper::trash('projects.trash');
            }
        }

        // Check-in actions
        JToolbarHelper::checkin('projects.checkin');

        // Configuration
        if (PKUserHelper::authProject('core.admin') || PKUserHelper::authProject('core.options')) {
            JToolbarHelper::preferences('com_pkprojects');
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

        JHtmlSidebar::setAction('index.php?option=com_pkprojects&view=projects');

        // Trigger BeforeDisplayFilter event. Params: Context, location, model
        $dispatcher->trigger('onProjectknifeBeforeDisplayFilter', array('com_pkprojects.projects', 'admin', &$this));

        // Category filter
        $no_cat = JHtml::_('select.option', '0', '* ' . JText::_('PKGLOBAL_UNCATEGORISED') . ' *');

        JHtmlSidebar::addFilter(
            JText::_('JOPTION_SELECT_CATEGORY'),
            'filter_category_id',
            JHtml::_('select.options', array_merge(array($no_cat), $this->category_options), 'value', 'text', $this->state->get('filter.category_id'))
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
        $me    = JHtml::_('select.option', 'me',    '* ' . JText::_('PKGLOBAL_CREATED_BY_ME') . ' *');
        $notme = JHtml::_('select.option', 'notme', '* ' . JText::_('PKGLOBAL_NOT_CREATED_BY_ME') . ' *');


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
        $dispatcher->trigger('onProjectknifeAfterDisplayFilter', array('com_pkprojects.projects', 'admin', &$this));
    }
}
