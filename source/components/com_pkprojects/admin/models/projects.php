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


class PKprojectsModelProjects extends PKModelList
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
            'category_id', 'a.category_id', 'category_title',
            'access', 'a.access', 'access_level',
            'created', 'a.created',
            'start_date', 'a.start_date',
            'due_date', 'a.due_date',
            'created_by', 'a.created_by',
            'ordering', 'a.ordering',
            'published', 'a.published',
            'author_id', 'a.progress',
            'category_id', 'level',
            'duration', 'a.duration',
            'author_name'
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
        $params = JComponentHelper::getParams('com_pkprojects');
        $itemid = 0;

        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        // Frontend: Merge app params with menu item params
        if ($app->isSite()) {
            $menu = $app->getMenu()->getActive();

            if ($menu) {
                $menu_params = new JRegistry();

                $menu_params->loadString($menu->params);
                $params->merge($menu_params);

                $itemid = $menu->id;
            }
            else {
                $itemid = $app->input->get('Itemid', 0, 'int');
            }
        }

        // Adjust the context to support different menu items.
        if ($itemid) {
            $this->context .= '.' . $itemid;
        }

        // Get user filter: search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        // Get user filter: access
        $default = $params->get('filter_access', '');
        $access  = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', $default);
        $this->setState('filter.access', $access);

        // Get user filter: author id
        $default   = $params->get('filter_author_id', '');
        $author_id = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id', $default);
        $this->setState('filter.author_id', $author_id);

        // Get user filter: publishing state
        $default   = $params->get('filter_published', '');
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', $default);
        $this->setState('filter.published', $published);

        // Get user filter: category id
        $default     = $params->get('filter_category_id', '');
        $category_id = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', $default);
        $this->setState('filter.category_id', $category_id);

        // Get user filter: tag id
        $default = $params->get('filter_tag_id', '');
        $tag_id  = $this->getUserStateFromRequest($this->context . '.filter.tag_id', 'filter_tag_id', $default);
        $this->setState('filter.tag_id', $tag_id);

        // Get user filter: category nesting level
        $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
        $this->setState('filter.level', $level);

        // Get user filter: progress
        $progress = $this->getUserStateFromRequest($this->context . '.filter.progress', 'filter_progress', '');
        $this->setState('filter.progress', $progress);

        // Get secondary order and dir (cannot use "list" context bc joomla freaks out for some reason...)
        $default       = $params->get('sort_by_sec', 'a.progress');
        $ordering_sec  = $this->getUserStateFromRequest($this->context . '.list_sec.ordering', 'filter_order_sec', $default);

        $default       = $params->get('order_by_sec', 'asc');
        $direction_sec = $this->getUserStateFromRequest($this->context . '.list_sec.direction', 'filter_order_sec_Dir', $default);

        // Validate secondary order
        if (!in_array($ordering_sec, $this->filter_fields)) {
            $ordering_sec = 'a.progress';
        }

        if (!in_array(strtoupper($direction_sec), array('ASC', 'DESC'))) {
            $direction_sec = 'ASC';
        }

        $this->setState('list.ordering_sec',  $ordering_sec);
        $this->setState('list.direction_sec', $direction_sec);

        // List state information.
        $ordering  = ($ordering === null  ? $params->get('sort_by', 'a.due_date') : $ordering);
        $direction = ($direction === null ? $params->get('order_by', 'asc')     : $direction);

        // Set list limit
        $cfg   = JFactory::getConfig();
        $limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $params->get('display_num', $cfg->get('list_limit')), 'uint');
        $this->setState('list.limit', $limit);
        $app->set('list_limit', $limit);

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
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.author_id');
        $id .= ':' . $this->getState('filter.tag_id');
        $id .= ':' . $this->getState('filter.progress');
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
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        // Get filters
        $access    = $this->getState('filter.access');
        $published = $this->getState('filter.published');
        $cat_id    = $this->getState('filter.category_id');
        $level     = $this->getState('filter.level');
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

        // Restrict category
        if ((is_numeric($cat_id) && $cat_id > 0) && $this->getState('restrict.access')) {
            $levels = $this->getState('auth.levels', array(0));

            if (!in_array($cat_id, $levels)) {
                $cat_id = '';
            }
        }

        // Prepare query
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.title, a.alias, a.published, a.access, a.access_inherit, a.category_id, a.ordering, a.progress, a.created, '
                . 'a.created_by, a.modified, a.modified_by, a.checked_out, a.checked_out_time, a.start_date, a.start_date_inherit, '
                . 'a.start_date_task_id, a.due_date, a.due_date_inherit, a.due_date_task_id, a.duration'
            )
        );

        $query->from('#__pk_projects AS a');

        // Join over the users for the checked out user.
        $query->select('uc.' . $display_name_field . ' AS editor')
              ->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

        // Join over the asset groups.
        $query->select('ag.title AS access_level')
              ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

        // Join over the categories.
        $query->select('c.title AS category_title')
              ->join('LEFT', '#__categories AS c ON c.id = a.category_id');

        // Join over the users for the author.
        $query->select('ua.' . $display_name_field . ' AS author_name')
              ->join('LEFT', '#__users AS ua ON ua.id = a.created_by');

        // Join over the tasks for the start date task title
        $query->select('tsd.title AS start_date_task_title')
              ->join('LEFT', '#__pk_tasks AS tsd ON tsd.id = a.start_date_task_id');

        // Join over the tasks for the due date task title
        $query->select('tdd.title AS due_date_task_title')
              ->join('LEFT', '#__pk_tasks AS tdd ON tdd.id = a.due_date_task_id');

        // Viewing restriction
        if ($this->getState('restrict.access')) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(a.access IN(' . implode(', ', $levels) . ') OR a.id IN(' . implode(',', $projects) . '))');
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
                $query->where('(a.published = 1)');
            }
            else {
                $query->where('(a.published = 0 OR a.published = 1)');
            }
        }

        // Filter by a single or group of categories.
        $base_level = 1;

        if (is_numeric($cat_id)) {
            if ($cat_id == 0) {
                $query->where('a.category_id = 0');
            }
            else {
                $cat_tbl = JTable::getInstance('Category', 'JTable');
                $cat_tbl->load($cat_id);

                $rgt = $cat_tbl->rgt;
                $lft = $cat_tbl->lft;

                $base_level = (int) $cat_tbl->level;

                $query->where('c.lft >= ' . (int) $lft)
                    ->where('c.rgt <= ' . (int) $rgt);
            }
        }

        // Filter on the category level.
        if ($level) {
            $query->where('c.level <= ' . ((int) $level + (int) $base_level - 1));
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
                $query->where('(a.progress != 100 AND a.due_date < ' . $db->quote($date->toSql()) . ')');
                break;
        }

        // Filter by tag
        if (is_numeric($tag_id)) {
            $query2 = $this->_db->getQuery(true);

            $query2->select('type_id')
                   ->from('#__content_types')
                   ->where('type_alias = ' . $db->quote('com_pkprojects.project'));

            $db->setQuery($query2);
            $type_id = (int) $db->loadResult();

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
                $search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
                $query->where('(ua.' . $display_name_field . ' LIKE ' . $search . ')');
            }
            else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
            }
        }

        // Sort and Order
        $params        = JComponentHelper::getParams('com_pkprojects');
        $order_pri_col = $this->state->get('list.ordering',      $params->get('sort_by', 'a.due_date'));
        $order_pri_dir = $this->state->get('list.direction',     $params->get('order_by', 'asc'));
        $order_sec_col = $this->state->get('list.ordering_sec',  $params->get('sort_by_sec', 'a.progress'));
        $order_sec_dir = $this->state->get('list.direction_sec', $params->get('order_by_sec', 'asc'));
        $order_sec     = '';

        if ($order_sec_col != $order_pri_col) {
            $order_sec = ', ' . $order_sec_col . ' ' . $order_sec_dir;
        }

        $query->order($db->escape($order_pri_col . ' ' . $order_pri_dir . $order_sec));

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

        $total_tasks          = $this->getTasksCount($pks);
        $completed_tasks      = $this->getTasksCompletedCount($pks);
        $total_milestones     = $this->getMilestonesCount($pks);
        $completed_milestones = $this->getMilestonesCompletedCount($pks);
        $total_tags           = $this->getTagsCount($pks);

        for ($i = 0; $i < $count; $i++)
        {
            $id = $items[$i]->id;

            $items[$i]->tasks_count          = 0;
            $items[$i]->tasks_completed      = 0;
            $items[$i]->milestones_count     = 0;
            $items[$i]->milestones_completed = 0;
            $items[$i]->tags_count           = 0;
            $items[$i]->tags                 = null;

            // Create slug
            $items[$i]->slug = $items[$i]->id . ':' . $items[$i]->alias;

            // Inject task count
            if (isset($total_tasks[$id])) {
                $items[$i]->tasks_count = $total_tasks[$id];
            }

            // Inject completed task count
            if (isset($completed_tasks[$id])) {
                $items[$i]->tasks_completed = $completed_tasks[$id];
            }

            // Inject milestones count
            if (isset($total_milestones[$id])) {
                $items[$i]->milestones_count = $total_milestones[$id];
            }

            // Inject completed milestones count
            if (isset($completed_milestones[$id])) {
                $items[$i]->milestones_completed = $completed_milestones[$id];
            }

            // Inject tag count
            if (isset($total_tags[$id])) {
                $items[$i]->tags_count = $total_tags[$id];

                // Load the actual tags
                $items[$i]->tags = new JHelperTags();
                $items[$i]->tags->getItemTags('com_pkprojects.project', $id);
            }
        }
    }


    /**
     * Returns the total number of tags for the given projects
     *
     * @param     array    $pks      The project ids
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
              ->where('type_alias = ' . $this->_db->quote('com_pkprojects.project'));

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

        if (!is_array($count) || !count($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of active tasks for the given projects
     *
     * @param     array    $pks      The project ids
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

        $query->select('project_id, COUNT(id) AS total')
              ->from('#__pk_tasks')
              ->where('project_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->group('project_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('project_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count) || !count($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of completed tasks for the given projects
     *
     * @param     array    $pks      The project ids
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

        $query->select('project_id, COUNT(id) AS total')
              ->from('#__pk_tasks')
              ->where('project_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->where('progress = 100')
              ->group('project_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('project_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count) || !count($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of active milestones for the given projects
     *
     * @param     array    $pks      The project ids
     *
     * @return    array    $count    The number of milestones
     */
    public function getMilestonesCount($pks)
    {
        JArrayHelper::toInteger($pks);

        if (!count($pks)) {
            return array();
        }

        $query = $this->_db->getQuery(true);

        $query->select('project_id, COUNT(id) AS total')
              ->from('#__pk_milestones')
              ->where('project_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->group('project_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('project_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count) || !count($count)) {
            return array();
        }

        return $count;
    }


    /**
     * Returns the total number of completed milestones for the given projects
     *
     * @param     array    $pks      The project ids
     *
     * @return    array    $count    The number of milestones
     */
    public function getMilestonesCompletedCount($pks)
    {
        JArrayHelper::toInteger($pks);

        if (!count($pks)) {
            return array();
        }

        $query = $this->_db->getQuery(true);

        $query->select('project_id, COUNT(id) AS total')
              ->from('#__pk_milestones')
              ->where('project_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->where('progress = 100')
              ->group('project_id');

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadAssocList('project_id', 'total');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if (!is_array($count) || !count($count)) {
            return array();
        }

        return $count;
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
              ->join('INNER', '#__pk_projects AS c ON c.created_by = u.id')
              ->group('u.id, u.' . $display_name_field)
              ->order('u.' . $display_name_field . ' ASC');

        // Restrict user visibility
        if ($this->getState('restrict.access')) {
            $levels   = $this->getState('auth.levels',   array(0));
            $projects = $this->getState('auth.projects', array(0));

            $query->where('(c.access IN(' . implode(', ', $levels) . ') OR c.id IN(' . implode(', ', $projects) . '))');
        }

        try {
            $this->_db->setQuery($query);
            $items = $this->_db->loadObjectList();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        return $items;
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
        );

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

            foreach ($items AS $i => $item)
            {
                if (!in_array($items[$i]->value, $levels)) {
                    unset($items[$i]);
                }
            }
        }

        return $items;
    }


    /**
     * Build a list of category filter options
     *
     * @return    array
     */
    public function getCategoryOptions()
    {
        $items = JHtml::_('category.options', 'com_pkprojects');

        // Filter out inaccessible categories
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
              ->where('type_alias = ' . $this->_db->quote('com_pkprojects.project'));

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
        if ($this->getState('restrict.access')) {
            $levels = $this->getState('auth.levels', array(0));

            $query->where('a.access IN(' . implode(', ', $levels) . ')');
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
            'a.ordering'           => JText::_('JGRID_HEADING_ORDERING'),
            'category_title'       => JText::_('JCATEGORY'),
            'a.title'              => JText::_('JGLOBAL_TITLE'),
            'a.published'          => JText::_('PKGLOBAL_PUBLISHING_STATE'),
            'a.progress'           => JText::_('PKGLOBAL_PROGRESS'),
            'a.created'            => JText::_('JDATE'),
            'a.start_date'         => JText::_('PKGLOBAL_START_DATE'),
            'a.due_date'           => JText::_('PKGLOBAL_DUE_DATE'),
            'author_name'          => JText::_('JAUTHOR'),
            'access_level'         => JText::_('JGRID_HEADING_ACCESS'),
            'a.id'                 => JText::_('JGRID_HEADING_ID')
        );

        asort($options);

        return $options;
    }
}
