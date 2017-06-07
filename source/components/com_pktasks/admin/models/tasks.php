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


class PKtasksModelTasks extends PKModelList
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
            'created_by', 'a.created_by',
            'ordering', 'a.ordering',
            'published', 'a.published',
            'a.project_id', 'project_id', 'project_title',
            'a.milestone_id', 'milestone_id', 'milestone_title',
            'author_id',
            'a.progress', 'progress',
            'a.priority', 'priority',
            'a.due_date', 'due_date'
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
        $params = JComponentHelper::getParams('com_pktasks');

        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $project = $this->getUserStateFromRequest('projectknife.project_id', 'filter_project_id');
        $this->setState('filter.project_id', $project);

        $milestone = $app->getUserStateFromRequest($this->context . '.filter.milestone_id', 'filter_milestone_id');
        $this->setState('filter.milestone_id', $milestone);

        $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $author_id = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
        $this->setState('filter.author_id', $author_id);

        $assignee_id = $app->getUserStateFromRequest($this->context . '.filter.assignee_id', 'filter_assignee_id');
        $this->setState('filter.assignee_id', $assignee_id);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $priority = $this->getUserStateFromRequest($this->context . '.filter.priority', 'filter_priority', '');
        $this->setState('filter.priority', $priority);

        $progress = $this->getUserStateFromRequest($this->context . '.filter.progress', 'filter_progress', '');
        $this->setState('filter.progress', $progress);

        $tag_id = $this->getUserStateFromRequest($this->context . '.filter.tag_id', 'filter_tag_id', '');
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
        $ordering  = ($ordering === null  ? $params->get('sort_by', 'a.start_date') : $ordering);
        $direction = ($direction === null ? $params->get('order_by', 'asc')     : $direction);

        // Reset milestone filter if the selected option does not belong to the current project
        if (is_numeric($milestone) && is_numeric($project)) {
            $milestone = (int) $milestone;
            $project   = (int) $project;
            $query     = $this->_db->getQuery(true);

            $query->select('project_id')
                  ->from('#__pk_milestones')
                  ->where('id = ' . $milestone);

            $this->_db->setQuery($query);

            if ((int) $this->_db->loadResult() != $project) {
                $app->setUserState($this->context . '.filter.milestone_id', '');
                $this->setState('filter.milestone_id', '');
            }
        }
        elseif (empty($project)) {
            $app->setUserState($this->context . '.filter.milestone_id', '');
            $this->setState('filter.milestone_id', '');
        }

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
        $id .= ':' . $this->getState('filter.project_id');
        $id .= ':' . $this->getState('filter.milestone_id');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.author_id');
        $id .= ':' . $this->getState('filter.assignee_id');
        $id .= ':' . $this->getState('filter.priority');
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
        $access      = $this->getState('filter.access');
        $project     = $this->getState('filter.project_id');
        $milestone   = $this->getState('filter.milestone_id');
        $published   = $this->getState('filter.published');
        $author_id   = $this->getState('filter.author_id');
        $assignee_id = $this->getState('filter.assignee_id');
        $priority    = $this->getState('filter.priority');
        $progress    = $this->getState('filter.progress');
        $tag_id      = $this->getState('filter.tag_id');
        $search      = $this->getState('filter.search');

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
                'a.id, a.project_id, a.milestone_id, a.title, a.alias, a.published, a.access, a.access_inherit, a.assignee_count, '
                . 'a.priority, a.progress, a.ordering, a.created, a.created_by, a.modified, a.modified_by, a.start_date, '
                . 'a.due_date, a.checked_out, a.checked_out_time'
            )
        );

        $query->from('#__pk_tasks AS a');

        // Join over the users for the checked out user.
        $query->select('uc.' . $display_name_field . ' AS editor')
              ->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

        // Join over the asset groups.
        $query->select('ag.title AS access_level')
              ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

        // Join over the users for the author.
        $query->select('ua.' . $display_name_field . ' AS author_name')
              ->join('LEFT', '#__users AS ua ON ua.id = a.created_by');

        // Join over the projects for the title
        $query->select('p.title AS project_title, p.alias AS project_alias')
              ->join('LEFT', '#__pk_projects AS p ON p.id = a.project_id');

        // Join over the milestones for the title
        $query->select('m.title AS milestone_title')
              ->join('LEFT', '#__pk_milestones AS m ON m.id = a.milestone_id');

        // Viewing restriction
        if ($this->getState('restrict.access', true)) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(a.access IN(' . implode(', ', $levels) . ') OR a.project_id IN(' . implode(', ', $projects). '))');
        }

        // Filter by access level.
        if (is_numeric($access) && intval($access) > 0) {
            $query->where('a.access = ' . (int) $access);
        }

        // Filter by project
        if (is_numeric($project) && intval($project) > 0) {
            $query->where('a.project_id = ' . (int) $project);
        }

        // Filter by milestone
        if (is_numeric($milestone) && intval($milestone) > 0) {
            $query->where('a.milestone_id = ' . (int) $milestone);
        }

        // Filter by published state
        if (is_numeric($published)) {
            $query->where('a.published = ' . (int) $published);
        }
        else {
            $query->where('(a.published = 0 OR a.published = 1)');
        }

        // Filter by priority
        if (is_numeric($priority)) {
            $query->where('a.priority = ' . (int) $priority);
        }

        // Filter by progress
        switch ($progress)
        {
            case 'to-do':
                $query->where('a.progress != 100');
                break;

            case 'overdue':
                $date = new JDate();
                $query->where('(a.progress != 100 AND a.due_date < ' . $this->_db->quote($date->toSql()) . ')');
                break;

            case 'completed':
                $query->where('a.progress = 100');
                break;

            case 'completed-me':
                $user = JFactory::getUser();

                $query->where('a.progress = 100')
                      ->where('a.completed_by = ' . (int) $user->id);
                break;

            case 'completed-notme':
                $user = JFactory::getUser();

                $query->where('a.progress = 100')
                      ->where('a.completed_by != ' . (int) $user->id);
                break;
        }

        // Filter by author
        if (is_numeric($author_id) && intval($author_id) > 0) {
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


        // Filter by assignee
        if (!empty($assignee_id)) {
            if (is_numeric($assignee_id) && intval($assignee_id) > 0) {
                $query->join('inner', '#__pk_task_assignees AS ta ON (ta.task_id = a.id AND ta.user_id = ' . (int) $assignee_id . ')');
            }
            else if (strcmp($assignee_id, 'me') === 0) {
                $user = JFactory::getUser();
                $query->join('inner', '#__pk_task_assignees AS ta ON (ta.task_id = a.id AND ta.user_id = ' . (int) $user->id . ')');
            }
            else if (strcmp($assignee_id, 'notme') === 0) {
                $user   = JFactory::getUser();
                $query2 = $this->_db->getQuery(true);

                $query2->select('task_id')
                      ->from('#__pk_task_assignees')
                      ->where('user_id = ' . (int) $user->id);

                $query->where('a.id NOT IN(' . $query2 . ')');
            }
            else if (strcmp($assignee_id, 'unassigned') === 0) {
                $query->where('a.assignee_count = 0');
            }
        }

        // Filter by tag
        if (is_numeric($tag_id) && intval($tag_id) > 0) {
            $query2 = $this->_db->getQuery(true);

            $query2->select('type_id')
                   ->from('#__content_types')
                   ->where('type_alias = ' . $this->_db->quote('com_pktasks.task'));

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
        $params = JComponentHelper::getParams('com_pktasks');

        $order_pri_col = $this->state->get('list.ordering',      $params->get('sort_by', 'a.start_date'));
        $order_pri_dir = $this->state->get('list.direction',     $params->get('order_by', 'asc'));
        $order_sec_col = $this->state->get('list.ordering_sec',  $params->get('sort_by_sec', 'a.due_date'));
        $order_sec_dir = $this->state->get('list.direction_sec', $params->get('order_by_sec', 'asc'));
        $order_sec     = '';

        if ($order_sec_col != $order_pri_col) {
            $order_sec = ', ' . $order_sec_col . ' ' . $order_sec_dir;
        }

        $query->order($this->_db->escape($order_pri_col . ' ' . $order_pri_dir . $order_sec));

        return $query;
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

        $total_tags         = $this->getTagsCount($pks);
        $total_predecessors = $this->getPredecessorCount($pks);

        for ($i = 0; $i < $count; $i++)
        {
            $id = $items[$i]->id;

            $items[$i]->tags_count   = 0;
            $items[$i]->tags         = null;
            $items[$i]->assignees    = array();

            $items[$i]->predecessors      = array();
            $items[$i]->predecessor_count = 0;
            $items[$i]->can_progress      = true;

            // Create slug
            $items[$i]->slug = $items[$i]->id . ':' . $items[$i]->alias;

            // Create project slug
            $items[$i]->project_slug = $items[$i]->project_id . ':' . $items[$i]->project_alias;

            // Add tag details
            if (isset($total_tags[$id])) {
                $items[$i]->tags_count = $total_tags[$id];

                // Load the actual tags
                $items[$i]->tags = new JHelperTags();
                $items[$i]->tags->getItemTags('com_pktasks.task', $id);
            }

            // Add predecessor dependencies
            if (isset($total_predecessors[$id])) {
                $items[$i]->predecessor_count = $total_predecessors[$id];
                $items[$i]->predecessors      = $this->getPredecessors($id);

                // Determine whether the task can be progressed
                foreach ($items[$i]->predecessors AS $predecessor)
                {
                    // Ignore tasks that are not published.
                    if ($predecessor->published != '1') {
                        continue;
                    }

                    // If at least 1 precedeeding task is not complete, this task cannot be completed either.
                    if ($predecessor->progress != '100') {
                        $items[$i]->can_progress = false;
                        break;
                    }
                }
            }

            // Add assignee details
            if ($items[$i]->assignee_count) {
                $items[$i]->assignees = $this->getAssignees($id);
            }
        }
    }


    /**
     * Returns the total number of tags for the given tasks
     *
     * @param     array    $pks      The task ids
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
              ->where('type_alias = ' . $this->_db->quote('com_pktasks.task'));

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
            $count = (array) $this->_db->loadAssocList('content_item_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of predecessors for the given tasks
     *
     * @param     array    $pks      The task ids
     *
     * @return    array    $count    The number of predecessors
     */
    public function getPredecessorCount($pks)
    {
        JArrayHelper::toInteger($pks);

        if (!count($pks)) {
            return array();
        }

        $query = $this->_db->getQuery(true);

        $query->select('successor_id, COUNT(predecessor_id) AS total')
              ->from('#__pk_task_dependencies')
              ->where('successor_id IN(' . implode(', ', $pks) . ')')
              ->group('successor_id');

        try {
            $this->_db->setQuery($query);
            $count = (array) $this->_db->loadAssocList('successor_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        return $count;
    }


    /**
     * Returns the predecessors for a given task
     *
     * @param     array    $pk      The task ids
     *
     * @return    array    $tasks   A list of predecessors
     */
    public function getPredecessors($pk)
    {
        $query = $this->_db->getQuery(true);

        $query->select('a.id, a.title, a.alias, a.published, a.progress, a.access')
              ->from('#__pk_tasks AS a')
              ->join('INNER', '#__pk_task_dependencies AS d ON d.predecessor_id = a.id')
              ->where('successor_id = ' . (int) $pk);

        try {
            $this->_db->setQuery($query);
            $tasks = $this->_db->loadObjectList();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        return $tasks;
    }


    /**
     * Returns the assignees of a task
     *
     * @param   integer $id The task id
     *
     * @return    array
     */
    protected function getAssignees($id)
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

        $query->select('u.id, u.' . $display_name_field . ' as assignee_name')
              ->from('#__users AS u')
              ->join('INNER', '#__pk_task_assignees AS a ON a.user_id = u.id')
              ->where('a.task_id = ' . (int) $id)
              ->order('a.id ASC');

        try {
            $this->_db->setQuery($query);
            $items = (array) $this->_db->loadObjectList();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        return $items;
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
              ->join('INNER', '#__pk_tasks AS t ON t.created_by = u.id')
              ->group('u.id, u.' . $display_name_field)
              ->order('u.' . $display_name_field . ' ASC');

        // Restrict user visibility
        if ($this->getState('restrict.access', true)) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(t.access IN(' . implode(', ', $levels) . ') OR t.project_id IN(' . implode(', ', $projects) . '))');
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
     * Build a list of assignee filter options
     *
     * @return    array
     */
    public function getAssigneeOptions()
    {
        $query = $this->_db->getQuery(true);

        $query->select('u.id AS value, u.name AS text')
              ->from('#__users AS u')
              ->join('INNER', '#__pk_task_assignees AS ta ON ta.user_id = u.id')
              ->join('INNER', '#__pk_tasks AS t ON t.id = ta.task_id')
              ->group('u.id, u.name')
              ->order('u.name ASC');

        // Restrict user visibility
        if ($this->getState('restrict.access', true)) {
            $levels   = $this->getState('auth.levels', array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(t.access IN(' . implode(', ', $levels) . ') OR t.project_id IN(' . implode(', ', $projects) . '))');
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
     * Build a list of priority filter options
     *
     * @return    array
     */
    public function getPriorityOptions()
    {
        $options = array(
            JHtml::_('select.option', 0, JText::_('COM_PKTASKS_PRIORITY_NORMAL')),
            JHtml::_('select.option', 1, JText::_('COM_PKTASKS_PRIORITY_HIGH'))
        );

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
            JHtml::_('select.option', 'completed', JText::_('PKGLOBAL_COMPLETED')),
            JHtml::_('select.option', 'completed-me', JText::_('PKGLOBAL_COMPLETED_BY_ME')),
            JHtml::_('select.option', 'completed-notme', JText::_('PKGLOBAL_COMPLETED_NOT_BY_ME'))
        );

        return $options;
    }


    /**
     * Build a list of project filter options
     *
     * @return    array
     */
    public function getProjectOptions()
    {
        $query = $this->_db->getQuery(true);

        $query->select('p.id AS value, p.title AS text')
              ->from('#__pk_projects AS p')
              ->join('INNER', '#__pk_tasks AS t ON t.project_id = p.id')
              ->group('p.id, p.title')
              ->order('p.title');

        // Restrict project visibility
        if ($this->getState('restrict.access', true)) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(p.access IN(' . implode(', ', $levels) . ') OR p.id IN(' . implode(', ', $projects) . '))');
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
     * Build a list of milestone filter options
     *
     * @return    array
     */
    public function getMilestoneOptions($filters = array())
    {
        // Default filters
        $filters = (array) $filters;

        if (!isset($filters['project_id'])) {
            $filters['project_id'] = 'any';
        }

        if (!isset($filters['task_id'])) {
            $filters['task_id'] = 'any';
        }

        $query = $this->_db->getQuery(true);

        if ($filters['project_id'] == 'any') {
            $txt_field = 'CONCAT(p.title, "/", m.title) AS text';
        }
        else {
            $txt_field = 'm.title AS text';
        }

        $query->select('m.id AS value, ' . $txt_field)
              ->from('#__pk_milestones AS m')
              ->group('m.id, m.title')
              ->order('text');

        // Filter by task
        if ($filters['task_id'] === 'any') {
            $query->join('INNER', '#__pk_tasks AS t ON t.milestone_id = m.id');
        }
        elseif (is_array($filters['task_id']) && count($filters['task_id'])) {
            JArrayHelper::toInteger($filters['task_id']);

            $query->join('INNER', '#__pk_tasks AS t ON t.milestone_id = m.id')
                  ->where('t.id IN(' . implode(',', $filters['task_id']) . ')');
        }

        // Filter by project
        if (is_numeric($filters['project_id'])) {
            $query->where('m.project_id = ' . (int) $filters['project_id']);
        }
        elseif (is_array($filters['project_id']) && count($filters['project_id'])) {
            JArrayHelper::toInteger($filters['project_id']);

            $query->where('m.project_id IN(' . implode(',', $filters['project_id']) . ')');
        }
        elseif ($filters['project_id'] == 'any') {
            $query->join('INNER', '#__pk_projects AS p ON p.id = m.project_id');
        }

        // Restrict user visibility
        if ($this->getState('restrict.access', true)) {
            $levels   = $this->getState('auth.levels', array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(m.access IN(' . implode(', ', $levels) . ') OR m.project_id IN(' . implode(', ', $projects) . '))');
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
     * Build a list of access level filter options
     *
     * @return    array
     */
    public function getAccessOptions()
    {
        $items = JHtml::_('access.assetgroups');

        // Filter out inaccessible access levels
        if ($this->getState('restrict.access', true)) {
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
              ->where('type_alias = ' . $this->_db->quote('com_pktasks.task'));

        try {
            $this->_db->setQuery($query);
            $type_id = (int) $this->_db->loadResult();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

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
        if ($this->getState('restrict.access', true)) {
            $query->where('a.access IN(' . implode(', ', $this->getState('auth.levels', array(0))) . ')');
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
     * Returns a list of sorting options
     *
     * @return array
     */
    public function getSortOptions()
    {
        $fields = array(
            'a.ordering'      => JText::_('JGRID_HEADING_ORDERING'),
            'project_title'   => JText::_('COM_PKPROJECTS_PROJECT'),
            'milestone_title' => JText::_('COM_PKMILESTONES_MILESTONE'),
            'a.title'         => JText::_('JGLOBAL_TITLE'),
            'a.published'     => JText::_('PKGLOBAL_PUBLISHING_STATE'),
            'a.created'       => JText::_('JDATE'),
            'a.start_date'    => JText::_('PKGLOBAL_START_DATE'),
            'a.due_date'      => JText::_('PKGLOBAL_DUE_DATE'),
            'author_name'     => JText::_('JAUTHOR'),
            'access_level'    => JText::_('JGRID_HEADING_ACCESS'),
            'a.progress'      => JText::_('PKGLOBAL_PROGRESS'),
            'a.id'            => JText::_('JGRID_HEADING_ID')
        );

        asort($fields);

        return $fields;
    }
}
