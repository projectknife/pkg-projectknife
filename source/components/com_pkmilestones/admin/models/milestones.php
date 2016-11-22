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


class PKMilestonesModelMilestones extends PKModelList
{
    /**
     * Constructor.
     *
     * @param    array    $config    An optional associative array of configuration settings.
     */
    public function __construct($config = array())
    {
        if (!empty($config['filter_fields'])) {
            return parent::__construct($config);
        }

        $config['filter_fields'] = array(
            'id', 'a.id',
            'title', 'a.title',
            'alias', 'a.alias',
            'checked_out', 'a.checked_out',
            'checked_out_time', 'a.checked_out_time',
            'access', 'a.access', 'access_level',
            'created', 'a.created',
            'start_date', 'a.start_date',
            'due_date', 'a.due_date',
            'created_by', 'a.created_by',
            'ordering', 'a.ordering',
            'published', 'a.published',
            'a.progress', 'progress',
            'a.project_id', 'project_id', 'project_title',
            'author_id',
            'duration', 'a.duration'
        );

        parent::__construct($config);
    }


    /**
     * Method to auto-populate the model state.
     * Note: Calling getState in this method will result in recursion.
     *
     * @param     string    $ordering     An optional ordering field.
     * @param     string    $direction    An optional direction (asc|desc).
     *
     * @return    void
     */
    protected function populateState($ordering = null, $direction = null)
    {
        $app    = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_pkmilestones');

        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $project = $this->getUserStateFromRequest('projectknife.project_id', 'filter_project_id');
        $this->setState('filter.project_id', $project);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $author_id = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
        $this->setState('filter.author_id', $author_id);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $progress = $this->getUserStateFromRequest($this->context . '.filter.progress', 'filter_progress', '');
        $this->setState('filter.progress', $progress);

        $default = $params->get('filter_tag_id', '');
        $tag_id  = $this->getUserStateFromRequest($this->context . '.filter.tag_id', 'filter_tag_id', $default);
        $this->setState('filter.tag_id', $tag_id);

        // Get secondary order and dir (cannot use "list" context bc joomla freaks out for some reason...)
        $ordering_sec  = $this->getUserStateFromRequest($this->context . '.list_sec.ordering', 'filter_order_sec', '');
        $direction_sec = $this->getUserStateFromRequest($this->context . '.list_sec.direction', 'filter_order_sec_Dir', '');

        // Validate secondary order
        if (!in_array($ordering_sec, $this->filter_fields)) {
            $ordering_sec = 'a.title';
        }

        if (!in_array(strtoupper($direction_sec), array('ASC', 'DESC'))) {
            $direction_sec = 'ASC';
        }

        $this->setState('list.ordering_sec',  $ordering_sec);
        $this->setState('list.direction_sec', $direction_sec);

        // List state information.
        $ordering  = ($ordering === null  ? $params->get('sort_by', 'a.due_date') : $ordering);
        $direction = ($direction === null ? $params->get('order_by', 'asc')     : $direction);

        // List state information.
        parent::populateState($ordering, $direction);
    }


    /**
     * Method to get a store id based on model configuration state.
     *
     * @param     string    $id    A prefix for the store id.
     *
     * @return    string           A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.project');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.author_id');
        $id .= ':' . $this->getState('filter.progress');
        $id .= ':' . $this->getState('filter.tag_id');
        $id .= ':' . $this->getState('list.ordering_sec');
        $id .= ':' . $this->getState('list.direction_sec');

        return parent::getStoreId($id);
    }


    /**
     * Build an SQL query to load the list data.
     *
     * @return    jdatabasequery
     */
    protected function getListQuery()
    {
        $query = $this->_db->getQuery(true);

        // Get filters
        $access    = $this->getState('filter.access');
        $project   = $this->getState('filter.project_id');
        $published = $this->getState('filter.published');
        $author_id = $this->getState('filter.author_id');
        $tag_id    = $this->getState('filter.tag_id');
        $progress  = $this->getState('filter.progress');
        $search    = $this->getState('filter.search');

        // Get system plugin settings
        $sys_params = PKPluginHelper::getParams('system', 'projectknife');

        switch ($sys_params->get('user_display_name'))
        {
            case '1':
                $display_name_field = 'name';
                break;

            default:
                $display_name_field = 'username';
                break;
        }

        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.project_id, a.title, a.description, a.alias, a.published, a.access, a.access_inherit, a.ordering, a.progress, '
                . 'a.created, a.created_by, a.modified, a.modified_by, a.start_date, a.due_date, a.checked_out, a.checked_out_time, '
                . 'a.start_date_inherit, a.due_date_inherit, a.start_date_task_id, a.due_date_task_id, a.duration'
            )
        );

        $query->from('#__pk_milestones AS a');

        // Join over the users for the checked out user.
        $query->select('uc.' . $display_name_field . ' AS editor')
              ->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

        // Join over the asset groups.
        $query->select('ag.title AS access_level')
              ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

        // Join over the users for the author.
        $query->select('ua.' . $display_name_field . ' AS author_name')
              ->join('LEFT', '#__users AS ua ON ua.id = a.created_by');

        // Join over the projects for the title and alias
        $query->select('p.title AS project_title, p.alias AS project_alias')
              ->join('LEFT', '#__pk_projects AS p ON p.id = a.project_id');

        // Join over the tasks for the actual start date task title
        $query->select('tsd.title AS start_date_task_title')
              ->join('LEFT', '#__pk_tasks AS tsd ON tsd.id = a.start_date_task_id');

        // Join over the tasks for the actual due date task title
        $query->select('tdd.title AS due_date_task_title')
              ->join('LEFT', '#__pk_tasks AS tdd ON tdd.id = a.due_date_task_id');

        // Filter by project
        if (is_numeric($project)) {
            $query->where('a.project_id = ' . (int) $project);
        }

        // Viewing restriction
        if ($this->getState('restrict.access')) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(a.access IN(' . implode(', ', $levels) . ') OR a.project_id IN(' . implode(', ', $projects) . '))');
        }

        // Filter by access level.
        if ($access) {
            $query->where('a.access = ' . (int) $access);
        }

        // Filter by published state
        if (is_numeric($published)) {
            $query->where('a.published = ' . (int) $published);
        }
        elseif ($published === '') {
            if (JFactory::getApplication()->isSite()) {
                $query->where('a.published = 1');
            }
            else {
                $query->where('(a.published = 0 OR a.published = 1)');
            }
        }

        // Filter by author
        if (is_numeric($author_id)) {
            $type = $this->getState('filter.author_id.include', true) ? ' = ' : ' <> ';
            $query->where('a.created_by' . $type . (int) $author_id);
        }
        else if (strcmp($author_id, 'me') === 0) {
            $user = JFactory::getUser();
            $query->where('a.created_by = ' . (int) $user->id);
        }
        else if (strcmp($author_id, 'notme') === 0) {
            $user = JFactory::getUser();
            $query->where('a.created_by != ' . (int) $user->id);
        }

        // Filter by progress
        switch ($progress)
        {
            case 'to-do':
                $query->where('a.progress != 100');
                break;

            case 'completed':
                $query->where('a.progress = 100');
                break;

            case 'overdue':
                $date = new JDate();
                $query->where('(a.progress != 100 AND a.due_date < ' . $this->_db->quote($date->toSql()) . ')');
                break;
        }

        // Filter by tag
        if (is_numeric($tag_id)) {
            $query2 = $this->_db->getQuery(true);

            $query2->select('type_id')
                   ->from('#__content_types')
                   ->where('type_alias = ' . $this->_db->quote('com_pkmilestones.milestone'));

            $this->_db->setQuery($query2);
            $type_id = (int) $this->_db->loadResult();

            $query->join('LEFT', '#__contentitem_tag_map AS ta ON ta.content_item_id = a.id')
                  ->where('ta.tag_id = ' . intval($tag_id))
                  ->where('ta.type_id = ' . $type_id);
        }

        // Search
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            }
            elseif (stripos($search, 'author:') === 0) {
                $search = $this->_db->quote('%' . $this->_db->escape(substr($search, 7), true) . '%');
                $query->where('ua.' . $display_name_field . ' LIKE ' . $search);
            }
            else {
                $search = $this->_db->quote('%' . str_replace(' ', '%', $this->_db->escape(trim($search), true) . '%'));
                $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
            }
        }

        // Order
        $params = JComponentHelper::getParams('com_pkmilestones');

        $order_pri_col = $this->state->get('list.ordering', $params->get('sort_by', 'a.due_date'));
        $order_pri_dir = $this->state->get('list.direction', $params->get('order_by', 'asc'));
        $order_sec_col = $this->state->get('list.ordering_sec', $params->get('sort_by_sec', 'a.title'));
        $order_sec_dir = $this->state->get('list.direction_sec', $params->get('order_by_sec', 'asc'));
        $order_sec     = '';

        if ($order_sec_col != $order_pri_col) {
            $order_sec = ', ' . $order_sec_col . ' ' . $order_sec_dir;
        }

        $query->order($this->_db->escape($order_pri_col . ' ' . $order_pri_dir . $order_sec));

        return $query;
    }


    /**
     * Build a list of author filter options
     *
     * @return    array
     */
    public function getAuthorOptions()
    {
        // Get system plugin settings
        $sys_params = PKPluginHelper::getParams('system', 'projectknife');

        switch ($sys_params->get('user_display_name'))
        {
            case '1':
                $display_name_field = 'name';
                break;

            default:
                $display_name_field = 'username';
                break;
        }

        $query = $this->_db->getQuery(true);

        $query->select('u.id AS value, u.' . $display_name_field . ' AS text')
              ->from('#__users AS u')
              ->join('INNER', '#__pk_milestones AS c ON c.created_by = u.id')
              ->group('u.id, u.' . $display_name_field)
              ->order('u.' . $display_name_field . ' ASC');

        // Restrict user visibility
        if ($this->getState('restrict.access')) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(c.access IN(' . implode(', ', $levels) . ') OR c.project_id IN(' . implode(', ', $projects) . '))');
        }

        try {
            $this->_db->setQuery($query);
            $options = $this->_db->loadObjectList();
        }
        catch (RuntimeException $e) {
            $this->setErrror($e->getMessage());
            return array();
        }

        return $options;
    }


    /**
     * Build a list of progress filter options
     *
     * @return    array
     */
    public function getProgressOptions()
    {
        $options = array(
            JHtml::_('select.option', 'to-do',     JText::_('PKGLOBAL_TODO')),
            JHtml::_('select.option', 'overdue',   JText::_('PKGLOBAL_OVERDUE')),
            JHtml::_('select.option', 'completed', JText::_('PKGLOBAL_COMPLETED'))
        );

        return $options;
    }


    /**
     * Build a list of project filter options
     *
     * @return    array
     */
    public function getProjectOptions($filters = array())
    {
        // Set default filters
        $filters = (array) $filters;

        if (!isset($filters['milestone'])) {
            $filters['milestone'] = '';
        }

        $query = $this->_db->getQuery(true);

        $query->select('p.id AS value, p.title AS text')
              ->from('#__pk_projects AS p');

        if ($filters['milestone'] != 'any') {
            $query->join('INNER', '#__pk_milestones AS m ON m.project_id = p.id');
        }

        // Restrict project visibility
        if ($this->getState('restrict.access')) {
            $levels   = $this->getState('auth.levels', array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(p.access IN(' . implode(', ', $levels) . ') OR p.id IN(' . implode(', ', $projects) . '))');
        }

        $query->group('p.id, p.title')
              ->order('p.title ASC');

        try {
            $this->_db->setQuery($query);
            $options = $this->_db->loadObjectList();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }


        // Check if the current project filter is in the list
        $filter_project = (int) $this->getState('filter.project_id');

        if ($filters['milestone'] != 'any' && $filter_project > 0) {
            $found = false;
            $count = count($options);

            for($i = 0; $i < $count; $i++)
            {
                if ($options[$i]->value == $filter_project) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Add to the list
                $query->clear()
                      ->select('title')
                      ->from('#__pk_projects')
                      ->where('id = ' . $filter_project);

                try {
                    $this->_db->setQuery($query);
                    $title = $this->_db->loadResult();
                }
                catch (RuntimeException $e) {
                    $this->setError($e->getMessage());
                    return array();
                }

                $opt        = new stdClass();
                $opt->value = $filter_project;
                $opt->text  = $title;

                $options[] = $opt;
            }
        }

        return $options;
    }


    /**
     * Build a list of access level filter options
     *
     * @return    array
     */
    public function getAccessOptions()
    {
        $items = JHtml::_('access.assetgroups');

        // Filter out inaccessible access levels
        if ($this->getState('restrict.access')) {
            $levels = $this->getState('auth.levels', array(0));
            $count  = count($items);

            for ($i = 0; $i != $count; $i++)
            {
                if (!in_array($items[$i]->value, $levels)) {
                    unset($items[$i]);
                }
            }
        }

        return $items;
    }


    /**
     * Build a list of tag filter options
     *
     * @return    array
     */
    public function getTagOptions()
    {
        $query = $this->_db->getQuery(true);

        // Get the content type id
        $query->select('type_id')
              ->from('#__content_types')
              ->where('type_alias = ' . $this->_db->quote('com_pkmilestones.milestone'));

        $this->_db->setQuery($query);
        $type_id = (int) $this->_db->loadResult();

        // Select tags
        $query->clear()
              ->select('a.id AS value, a.title AS text')
              ->from('#__tags AS a')
              ->join('INNER', '#__contentitem_tag_map AS m ON m.tag_id = a.id')
              ->where('m.type_id = ' . $type_id)
              ->where('a.published = 1')
              ->group('a.id, a.title')
              ->order('a.title ASC');

        // Restrict user visibility
        if ($this->getState('restrict.access')) {
            $query->where('a.access IN(' . implode(', ', $this->getState('auth.levels')) . ')');
        }

        try {
            $this->_db->setQuery($query);
            $options = $this->_db->loadObjectList();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        return $options;
    }


    /**
     * Returns a list of sort options
     *
     * @return    array
     */
    public function getSortOptions()
    {
        $options = array(
            'a.ordering'          => JText::_('JGRID_HEADING_ORDERING'),
            'project_title'       => JText::_('COM_PKPROJECTS_PROJECT'),
            'a.title'             => JText::_('JGLOBAL_TITLE'),
            'a.published'         => JText::_('PKGLOBAL_PUBLISHING_STATE'),
            'a.created'           => JText::_('JDATE'),
            'a.start_date'        => JText::_('PKGLOBAL_START_DATE'),
            'a.due_date'          => JText::_('PKGLOBAL_DUE_DATE'),
            'author_name'         => JText::_('JAUTHOR'),
            'access_level'        => JText::_('JGRID_HEADING_ACCESS'),
            'a.id'                => JText::_('JGRID_HEADING_ID'),
            'a.progress'          => JText::_('PKGLOBAL_PROGRESS')
        );

        asort($options);

        return $options;
    }


    /**
     * Returns the total number of active tasks for the given milestones
     *
     * @param     array    $pks      The milestone ids
     *
     * @return    array    $count    The number of tasks
     */
    public function getTasksCount($pks)
    {
        JArrayHelper::toInteger($pks);

        if (!count($pks)) {
            return array();
        }

        $query = $this->_db->getQuery(true);

        $query->select('milestone_id, COUNT(id) AS total')
              ->from('#__pk_tasks')
              ->where('milestone_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->group('milestone_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('milestone_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of completed tasks for the given milestones
     *
     * @param     array    $pks      The milestone ids
     *
     * @return    array    $count    The number of tasks
     */
    public function getTasksCompletedCount($pks)
    {
        JArrayHelper::toInteger($pks);

        if (!count($pks)) {
            return array();
        }

        $query = $this->_db->getQuery(true);

        $query->select('milestone_id, COUNT(id) AS total')
              ->from('#__pk_tasks')
              ->where('milestone_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->where('progress = 100')
              ->group('milestone_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('milestone_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of tags for the given milestones
     *
     * @param     array    $pks      The milestone ids
     *
     * @return    array    $count    The number of tags
     */
    public function getTagsCount($pks)
    {
        JArrayHelper::toInteger($pks);

        if (!count($pks)) {
            return array();
        }

        $query = $this->_db->getQuery(true);

        $query->select('type_id')
              ->from('#__content_types')
              ->where('type_alias = ' . $this->_db->quote('com_pkmilestones.milestone'));

        try {
            $this->_db->setQuery($query);
            $type_id = (int) $this->_db->loadResult();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }


        $query->clear();
        $query->select('content_item_id, COUNT(tag_id) AS total')
              ->from('#__contentitem_tag_map')
              ->where('type_id = ' . $type_id)
              ->where('content_item_id IN(' . implode(', ', $pks) . ')')
              ->group('content_item_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('content_item_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Injects additional data into a list of items
     *
     * @param     array    $items
     *
     * @return    void
     */
    protected function prepareItems(&$items)
    {
        $pks   = JArrayHelper::getColumn($items, 'id');
        $count = count($pks);
        $id    = 0;

        $total_tasks     = $this->getTasksCount($pks);
        $completed_tasks = $this->getTasksCompletedCount($pks);
        $total_tags      = $this->getTagsCount($pks);

        for ($i = 0; $i < $count; $i++)
        {
            $id = $items[$i]->id;

            $items[$i]->tasks_count     = 0;
            $items[$i]->tasks_completed = 0;
            $items[$i]->tags_count      = 0;
            $items[$i]->tags            = null;

            // Create slug
            $items[$i]->slug = $items[$i]->id . ':' . $items[$i]->alias;

            // Create project slug
            $items[$i]->project_slug = $items[$i]->project_id . ':' . $items[$i]->project_alias;

            // Inject task count
            if (isset($total_tasks[$id])) {
                $items[$i]->tasks_count = $total_tasks[$id];
            }

            // Inject completed task count
            if (isset($completed_tasks[$id])) {
                $items[$i]->tasks_completed = $completed_tasks[$id];
            }

            // Inject tag count
            if (isset($total_tags[$id])) {
                $items[$i]->tags_count = $total_tags[$id];

                // Load the actual tags
                $items[$i]->tags = new JHelperTags();
                $items[$i]->tags->getItemTags('com_pkmilestones.milestone', $id);
            }
        }
    }
}
