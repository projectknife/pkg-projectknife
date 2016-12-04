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


class PKTasksModelTask extends PKModelAdmin
{
    protected $progress_changed;
    protected $priority_changed;
    protected $old_progress;
    protected $old_priority;


    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app     = JFactory::getApplication();
        $context = ($app->isSite() ? 'form' : 'task');
        $data    = $app->getUserState('com_pktasks.edit.' . $context . '.data', array());

        if (empty($data)) {
            $data = $this->getItem();

            // Prime some default values.
            if ($this->getState($context . '.id') == 0) {
                $filters          = (array) $app->getUserState('com_pktasks.tasks.filter');
                $filter_project   = isset($filters['project_id'])   ? intval($filters['project_id'])   : intval($app->getUserState('projectknife.project_id'));
                $filter_milestone = isset($filters['milestone_id']) ? intval($filters['milestone_id']) : 0;

                $data->set('project_id',   $app->input->getInt('project_id',   (int) $filter_project));
                $data->set('milestone_id', $app->input->getInt('milestone_id', (int) $filter_milestone));
                $data->set('access', 0);

                // Set default start and due date to today
                $date = JFactory::getDate('now', 'UTC')->format('Y-m-d', true, false);

                $data->set('start_date', $app->input->getInt('start_date', $date));
                $data->set('due_date',   $app->input->getInt('due_date',   $date));
            }
        }

        $this->preprocessData('com_pktasks.task', $data);

        return $data;
    }


    /**
     * Method to test whether a record can be deleted.
     *
     * @param     object     $record    A record object.
     *
     * @return    boolean               True if allowed to delete the record.
     */
    protected function canDelete($record)
    {
        if (PKUserHelper::authProject('task.delete', $record->project_id)) {
            return true;
        }

        $delete_own = PKUserHelper::authProject('task.delete.own', $record->project_id);
        $user       = JFactory::getUser();

        return ($delete_own && $user->id > 0 && $user->id == $record->created_by);
    }


    /**
     * Method to test whether a record state can be changed.
     *
     * @param     object     $record    A record object.
     *
     * @return    boolean               True if allowed to change the state of the record.
     */
    protected function canEditState($record)
    {
        if (PKUserHelper::authProject('task.edit.state', $record->project_id)) {
            return true;
        }

        $edit_own = PKUserHelper::authProject('task.edit.own.state', $record->project_id);
        $user     = JFactory::getUser();

        return ($edit_own && $user->id > 0 && $user->id == $record->created_by);
    }


    /**
     * Method to test whether a record progress can be changed.
     *
     * @param     object     $record    A record object.
     *
     * @return    boolean               True if allowed to change the state of the record.
     */
    protected function canEditProgress($record)
    {
        if (PKUserHelper::authProject('task.edit.progress', $record->project_id)) {
            return true;
        }

        $user = JFactory::getUser();

        if (PKUserHelper::authProject('task.edit.own.progress', $record->project_id)) {
            if ($edit_own && $user->id > 0 && $user->id == $record->created_by) {
                return true;
            }
        }

        if (PKUserHelper::authProject('task.edit.assigned.progress', $record->project_id)) {
            if (in_array($user->id, $this->getAssignees($record->id))) {
                return true;
            }
        }

        return false;
    }


    /**
     * Method to change the title & alias.
     *
     * @param     string     $title         The title.
     * @param     string     $alias         The alias.
     * @param     integer    $project_id    The project id.
     * @param     integer    $id            The item id
     * @return    array                     Contains the modified title and alias.
     */
    protected function uniqueTitleAlias($title, $alias, $project_id, $id)
    {
        // Sanitize alias
        if (empty($alias)) {
            if (JFactory::getConfig()->get('unicodeslugs') == 1) {
                $alias = JFilterOutput::stringURLUnicodeSlug($title);
            }
            else {
                $alias = JFilterOutput::stringURLSafe($title);
            }

            if (trim(str_replace('-', '', $alias)) == '') {
                $alias = JFilterOutput::stringURLSafe(JFactory::getDate()->format('Y-m-d-H-i-s'));
            }
        }
        else {
            if (JFactory::getConfig()->get('unicodeslugs') == 1) {
                $alias = JFilterOutput::stringURLUnicodeSlug($alias);
            }
            else {
                $alias = JFilterOutput::stringURLSafe($alias);
            }
        }

        $project_id = (int) $project_id;


        // Count same existing aliases
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(id)')
              ->from('#__pk_tasks')
              ->where('project_id = ' . $project_id)
              ->where('alias = ' . $this->_db->quote($alias))
              ->where('id != ' . intval($id));

        try {
            $this->_db->setQuery($query);
            $count = (int) $this->_db->loadResult();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return array();
        }

        if ($id > 0 && $count == 0) {
            return array($title, $alias);
        }
        elseif ($id == 0 && $count == 0) {
            return array($title, $alias);
        }
        else {
            // Increment title and alias
            $query->clear()
                  ->select('COUNT(id)')
                  ->from('#__pk_tasks')
                  ->where('project_id = ' . $project_id)
                  ->where('alias = ' . $this->_db->quote($alias))
                  ->where('id != ' . intval($id));

            try {
                $this->_db->setQuery($query);

                while ($this->_db->loadResult())
                {
                    $title = JString::increment($title);
                    $alias = JString::increment($alias, 'dash');

                    $query->clear()
                          ->select('COUNT(id)')
                          ->from('#__pk_tasks')
                          ->where('project_id = ' . $project_id)
                          ->where('alias = ' . $this->_db->quote($alias))
                          ->where('id != ' . intval($id));

                    $this->_db->setQuery($query);
                }
            }
            catch (RuntimeException $e) {
                $this->setError($e->getMessage());
                return array();
            }
        }

        return array($title, $alias);
    }


    /**
     * Saves users assigned to a task, coming from the form
     *
     * @param     integer    $pk       The task id
     * @param     array      $users    The user data
     *
     * @return    boolean
     */
    protected function saveAssignees($pk, $users)
    {
        // Sanitize ids.
        $users = array_unique($users);
        JArrayHelper::toInteger($users);

        // Remove any values of zero.
        if (array_search(0, $users, true)) {
            unset($users[array_search(0, $users, true)]);
        }


        $query = $this->_db->getQuery(true);

        // Load currently assigned users
        $query->select('user_id')
              ->from('#__pk_task_assignees')
              ->where('task_id = ' . $pk);

        try {
            $this->_db->setQuery($query);
            $current = $this->_db->loadColumn();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }


        $unassign = array();
        $assign   = array();

        // Unassign users which are not present in $users
        foreach ($current AS $uid)
        {
            if (!in_array($uid, $users)) {
                $unassign[] = $uid;
            }
        }

        if (count($unassign)) {
            $this->unassign($pk, $unassign);
        }

        // Assign users which are not present in $current
        foreach ($users AS $uid)
        {
            if (!in_array($uid, $current)) {
                $assign[] = $uid;
            }
        }

        if (count($assign)) {
            $this->assign($pk, $assign);
        }

        return true;
    }


    /**
     * A protected method to get a set of ordering conditions.
     *
     * @param     object    $table    A record object.
     *
     * @return    array               An array of conditions to add to add to ordering queries.
     */
    protected function getReorderConditions($table)
    {
        $condition = array();
        $condition[] = 'project_id = ' . (int) $table->project_id;
        $condition[] = 'milestone_id = ' . (int) $table->milestone_id;

        return $condition;
    }


    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param     jtable    $table    A JTable object.
     *
     * @return    void
     */
    protected function prepareTable($table)
    {
        // Reorder the item
        if (empty($table->id)) {
            $table->reorder('project_id = ' . (int) $table->project_id . ' AND milestone_id = ' . (int) $table->milestone_id);
        }
    }


    /**
     * Method to get the record form.
     *
     * @param     array      $data       Data for the form.
     * @param     boolean    $do_load    True if the form is to load its own data (default case), false if not.
     *
     * @return    mixed                  A JForm object on success, false on failure
     */
    public function getForm($data = array(), $do_load = true)
    {
        $is_site = JFactory::getApplication()->isSite();

        // Register backend form path when in frontend
        if ($is_site) {
            JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_pktasks/models/forms');
        }

        $form = $this->loadForm('com_pktasks.task', 'task', array('control' => 'jform', 'load_data' => $do_load));

        if (empty($form)) {
            return false;
        }

        $input  = JFactory::getApplication()->input;
        $params = JComponentHelper::getParams('com_pktasks');

        // Get item id
        $id  = $input->getUint('id', $this->getState('task.id', 0));
        $pid = (isset($data['project_id']) ? intval($data['project_id']) : PKApplicationHelper::getProjectId());

        if ($params->get('auto_access', '1') == '1') {
            $form->setFieldAttribute('access', 'type', 'hidden');
            $form->setFieldAttribute('access', 'filter', 'unset');
        }

        if ($params->get('auto_alias', '1') == '1') {
            $form->setFieldAttribute('alias', 'type', 'hidden');
            $form->setFieldAttribute('alias', 'filter', 'unset');
        }

        // Disable some fields in the frontend form
        if ($is_site) {
            $form->setFieldAttribute('created_by', 'type', 'hidden');
        }

        // Check "edit state" permission
        if (!PKUserHelper::authProject('task.edit.state', $pid)) {
            $user = JFactory::getUser();

            $can_edit_state = false;

            if ($id && $pid) {
                // Check if owner
                if (PKUserHelper::authProject('task.edit.own.state', $pid)) {
                    $query = $this->_db->getQuery(true);

                    $query->select('created_by')
                          ->from('#__pk_tasks')
                          ->where('id = ' . $id);

                    $this->_db->setQuery($query);
                    $project_author = (int) $this->_db->loadResult();

                    if ($user->id > 0 && $user->id == $project_author) {
                        $can_edit_state = true;
                    }
                }
            }

            if (!$can_edit_state) {
                $form->setFieldAttribute('published', 'type', 'hidden');
                $form->setFieldAttribute('published', 'filter', 'unset');
            }
        }

        // Check edit progress permission
        if (!PKUserHelper::authProject('task.edit.progress', $pid)) {
            $can_edit_progress = false;

            if ($id && $pid) {
                // Check if owner
                if (PKUserHelper::authProject('task.edit.own.progress', $pid)) {
                    $user  = JFactory::getUser();
                    $query = $this->_db->getQuery(true);

                    $query->select('created_by')
                          ->from('#__pk_tasks')
                          ->where('id = ' . $id);

                    $this->_db->setQuery($query);
                    $project_author = (int) $this->_db->loadResult();

                    if ($user->id > 0 && $user->id == $project_author) {
                        $can_edit_progress = true;
                    }
                }

                // Check if assigned
                if (!$can_edit_progress && PKUserHelper::authProject('task.edit.assigned.progress', $pid)) {
                    $can_edit_progress = in_array($user->id, $this->getAssignees($id));
                }
            }

            if (!$can_edit_progress) {
                $form->setFieldAttribute('progress', 'type', 'hidden');
                $form->setFieldAttribute('progress', 'filter', 'unset');
            }
        }

        return $form;
    }


    /**
     * Method to get a table object, load it if necessary.
     *
     * @param     string    $name       The table name. Optional.
     * @param     string    $prefix     The class prefix. Optional.
     * @param     array     $options    Configuration array for model. Optional.
     *
     * @return    jtable                A JTable object
     */
    public function getTable($name = 'Task', $prefix = 'PKtasksTable', $options = array())
    {
        if (empty($name)) {
            $name = $this->getName();
        }

        if ($table = $this->_createTable($name, $prefix, $options)) {
            return $table;
        }

        throw new Exception(JText::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name), 0);
    }


    /**
     * Method to get a single record.
     *
     * @param     integer    $pk    The id of the primary key.
     *
     * @return    mixed             Object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if (!$item) {
            return $item;
        }

        // Get additional data
        if (is_object($item) && !empty($item->id)) {
            $item->tags = new JHelperTags;
            $item->tags->getTagIds($item->id, 'com_pktasks.task');

            // Get author name
            $sys_params = PKPluginHelper::getParams('system', 'projectknife');
            $db         = JFactory::getDbo();
            $query      = $db->getQuery(true);

            switch ($sys_params->get('user_display_name'))
            {
                case '1':
                    $query->select('name');
                    break;

                default:
                    $query->select('username');
                    break;
            }

            $query->from('#__users')
                  ->where('id = ' . $item->created_by);

            $db->setQuery($query);
            $item->author_name = $db->loadResult();

            // Get project title
            $query->clear()
                  ->select('title')
                  ->from('#__pk_projects')
                  ->where('id = ' . (int) $item->project_id);

            $db->setQuery($query);
            $item->project_title = $db->loadResult();

            // Get milestone title
            $query->clear()
                  ->select('title')
                  ->from('#__pk_milestones')
                  ->where('id = ' . (int) $item->milestone_id);

            $db->setQuery($query);
            $item->milestone_title = $db->loadResult();
        }

        // Load assigned users
        $item->assignees = $this->getAssignees($pk);

        return $item;
    }


    /**
     * Method to get the users assigned to a given task
     *
     * @param     integer    $pk    The task id
     *
     * @return    array
     */
    public function getAssignees($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        if (!$pk) return array();

        $query = $this->_db->getQuery(true);

        $query->select('user_id')
              ->from('#__pk_task_assignees')
              ->where('task_id = ' . $pk);

        $this->_db->setQuery($query);
        return $this->_db->loadColumn();
    }


    /**
     * Method to prepare the user input before saving it
     *
     * @param     array    $data      The data to save
     * @param     bool     $is_new    Indicated whether this is a new item or not
     *
     * @return    void
     */
    public function prepareSaveData(&$data, $is_new)
    {
        $params = JComponentHelper::getParams('com_pktasks');
        $table  = $this->getTable();
        $key    = $table->getKeyName();
        $pk     = (int) array_key_exists($key, $data) ? $data[$key] : $this->getState($this->getName() . '.id');

        $this->progress_changed = false;
        $this->priority_changed = false;
        $this->old_progress     = 0;
        $this->old_priority     = 0;

        if ($pk > 0) {
            $table->load($pk);

            // Detect progress change
            if (isset($data['progress'])) {
                $this->progress_changed = ($table->progress != $data['progress']);
                $this->old_progress     = $table->progress;
                $this->priority_changed = ($table->priority != $data['priority']);
                $this->old_priority     = $table->priority;
            }
        }

        $data['title']        = trim(array_key_exists('title', $data) ? $data['title'] : $table->title);
        $data['alias']        = array_key_exists('alias', $data) ? $data['alias'] : $table->alias;
        $data['project_id']   = (int) array_key_exists('project_id', $data) ? $data['project_id'] : $table->project_id;
        $data['milestone_id'] = (int) array_key_exists('milestone_id', $data) ? $data['milestone_id'] : $table->milestone_id;

        // Auto-title
        if (empty($data['title'])) {
            $data['title'] = JText::_('COM_PKTASKS_NEW_TASK_TITLE');
        }

        // Auto-Alias
        if ((int) $params->get('auto_alias', '1') === 1) {
            $data['alias'] = '';
        }

        // Generate unqiue title and alias
        list($data['title'], $data['alias']) = $this->uniqueTitleAlias($data['title'], $data['alias'], $data['project_id'], $pk);


        // Handle viewing access
        if ($data['milestone_id'] > 0 ) {
            $parent_table  = '#__pk_milestones';
            $parent_id     = $data['milestone_id'];
        }
        else {
            $parent_table  = '#__pk_projects';
            $parent_id     = $data['project_id'];
        }

        $query = $this->_db->getQuery(true);

        $query->select('access')
              ->from($parent_table)
              ->where('id = ' . $parent_id);

        $this->_db->setQuery($query);
        $parent_access = (int) $this->_db->loadResult();

        if ($params->get('auto_access', '1') == '1') {
            // Always inherit
            $data['access'] = $parent_access;
            $data['access_inherit'] = 1;
        }
        else {
            if (array_key_exists('access', $data)) {
                $data['access'] = (int) $data['access'];

                if ($data['access'] === 0 || $data['access'] === $parent_access) {
                    $data['access'] = $parent_access;
                    $data['access_inherit'] = 1;
                }
                else {
                    $data['access_inherit'] = 0;
                }
            }
        }


        // Set completed and completed by data
        if ($this->progress_changed && $data['progress'] == 100) {
            $user = JFactory::getUser();
            $date = JDate::getInstance();

            $data['completed_by'] = $user->id;
            $data['completed']    = $date->toSql();
        }
        else {
            $data['completed_by'] = 0;
            $data['completed']    = $this->_db->getNullDate();
        }

        parent::prepareSaveData($data, $is_new);
    }


    /**
     * Method to save the form data.
     *
     * @param     array      $data    The form data.
     *
     * @return    boolean             True on success, False on error.
     */
    public function save($data)
    {
        // Save item
        if (!parent::save($data)) {
            return false;
        }

        // Save assigned users
        if (isset($data['assignees'])) {
            $this->saveAssignees($this->getState($this->getName() . '.id'), $data['assignees']);
        }

        // Trigger progress change event
        if ($this->progress_changed) {
            // Load Projectknife plugins
            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin('projectknife');

            $progress = (int) $data['progress'];
            $items    = array($this->getState($this->getName() . '.id') => array((int) $this->old_progress, $progress));

            $dispatcher->trigger('onProjectknifeAfterProgress', array('com_pktasks.tasks', $items));
        }

        // Trigger priority change event
        if ($this->priority_changed) {
            // Load Projectknife plugins
            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin('projectknife');

            $priority = (int) $data['priority'];
            $items    = array($this->getState($this->getName() . '.id') => array((int) $this->old_priority, $priority));

            $dispatcher->trigger('onProjectknifeAfterPriority', array('com_pktasks.tasks', $items));
        }

        // Reset vars
        $this->progress_changed = false;
        $this->priority_changed = false;
        $this->old_progress     = 0;
        $this->old_priority     = 0;

        return true;
    }


    /**
     * Method to copy a list of tasks
     *
     * @param     array      $pks        The tasks to copy
     * @param     array      $options    Copy settings
     *
     * @return    boolean                True on success.
     */
    public function copy($pks, $options = array())
    {
        // Sanitize ids.
        $pks = array_unique($pks);
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        if (array_search(0, $pks, true)) {
            unset($pks[array_search(0, $pks, true)]);
        }

        if (empty($pks)) {
            $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
            return false;
        }

        // Set default options
        $options = (array) $options;

        $options['project_id']   = (array_key_exists('project_id', $options)   ? $options['project_id']      : '');
        $options['milestone_id'] = (array_key_exists('milestone_id', $options) ? $options['milestone_id']    : '');
        $options['access']       = (array_key_exists('access', $options)       ? $options['access']          : '');
        $options['include']      = (array_key_exists('include', $options)      ? (array) $options['include'] : array());

        $options['ignore_permissions'] = (array_key_exists('ignore_permissions', $options) ? (bool) $options['ignore_permissions'] : PKUserHelper::isSuperAdmin());

        // Validate permissions
        if (!$options['ignore_permissions']) {
            $count = count($pks);

            for($i = 0; $i != $count; $i++)
            {
                if (!PKUserHelper::authorise('core.edit.task', $pks[$i]) || !PKUserHelper::authorise('core.create.task', $pks[$i])) {
                    unset($pks[$i]);
                }
            }

            if (!$count) {
                $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
                return false;
            }
        }

        // Copy items
        $count = count($pks);
        $table = $this->getTable();
        $data  = array();
        $ref   = array();
        $id_state = $this->getName() . '.id';

        for ($i = 0; $i != $count; $i++)
        {
            $id = $pks[$i];

            $table->reset();

            if (!$table->load($id)) {
                continue;
            }

            $data = (array) $table;
            $data['id'] = 0;

            if (is_numeric($options['project_id'])) {
                $data['project_id'] = (int) $options['project_id'];
            }

            if (is_numeric($options['milestone_id'])) {
                $data['milestone_id'] = (int) $options['milestone_id'];
            }

            if (is_numeric($options['access'])) {
                $data['access'] = (int) $options['access'];
            }

            $data['users'] = $this->getAssignees($id);

            if (!$this->save($data)) {
                continue;
            }

            $ref[$id] = (int) $this->getState($id_state);
        }

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterCopy', array('com_pktasks.tasks', $ref, $options));

        return true;
    }


    /**
     * Set the progress of the given task id's
     *
     * @param     array      $pks         Task id's
     * @param     integer    $progress    The new progress
     *
     * @return    boolean
     */
    public function progress($pks, $progress = 0)
    {
        // Sanitize ids.
        $pks = array_unique($pks);
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        if (array_search(0, $pks, true)) {
            unset($pks[array_search(0, $pks, true)]);
        }

        if (empty($pks)) {
            $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
            return false;
        }

        $query = $this->_db->getQuery(true);

        // Load current progress values
        $query->select('id, progress')
              ->from('#__pk_tasks')
              ->where ('id IN(' . implode(', ', $pks) . ')')
              ->group('id, progress');

        try {
            $this->_db->setQuery($query);
            $items = $this->_db->loadAssocList('id', 'progress');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }


        // Remove items with matching progress value
        foreach ($items AS $id => &$p)
        {
            if ($p == $progress) {
                unset($items[$id]);
            }
            else {
                $items[$id] = array($p, $progress);
            }
        }

        if (!count($items)) {
            $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
            return true;
        }

        $pks = array_keys($items);

        if ($progress == 100) {
            $user = JFactory::getUser();
            $date = JDate::getInstance();

            $completed_by = $user->id;
            $completed    = $date->toSql();
        }
        else {
            $completed_by = 0;
            $completed    = $this->_db->toSql();
        }

        // Update progress
        $query->clear()
              ->update('#__pk_tasks')
              ->set('progress = ' . (int) $progress)
              ->set('completed_by = ' . $completed_by)
              ->set('completed = ' . $this->_db->quote($completed))
              ->where('id IN(' . implode(', ', $pks) . ')');

        try {
            $this->_db->setQuery($query);
            $this->_db->execute();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterProgress', array('com_pktasks.tasks', $items));

        return true;
    }


    /**
     * Set the priority of the given task id's
     *
     * @param     array      $pks         Task id's
     * @param     integer    $priority    The new priority
     *
     * @return    boolean
     */
    public function priority($pks, $priority = 0)
    {
        // Sanitize ids.
        $pks = array_unique($pks);
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        if (array_search(0, $pks, true)) {
            unset($pks[array_search(0, $pks, true)]);
        }

        if (empty($pks)) {
            $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
            return false;
        }

        $query = $this->_db->getQuery(true);

        // Load current priority values
        $query->select('id, priority')
              ->from('#__pk_tasks')
              ->where ('id IN(' . implode(', ', $pks) . ')')
              ->group('id, priority');

        try {
            $this->_db->setQuery($query);
            $items = $this->_db->loadAssocList('id', 'priority');
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }


        // Remove items with matching priority value
        foreach ($items AS $id => &$p)
        {
            if ($p == $priority) {
                unset($items[$id]);
            }
            else {
                $items[$id] = array($p, $priority);
            }
        }

        if (!count($items)) {
            $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
            return true;
        }

        $pks = array_keys($items);

        // Update progress
        $query->clear()
              ->update('#__pk_tasks')
              ->set('priority = ' . (int) $priority)
              ->where('id IN(' . implode(', ', $pks) . ')');

        try {
            $this->_db->setQuery($query);
            $this->_db->execute();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterPriority', array('com_pktasks.tasks', $items));

        return true;
    }


    /**
     * Assigns users to a given task
     *
     * @param     integer    $pk       The task id
     * @param     array      $users    The users
     *
     * @return    boolean
     */
    public function assign($pk, $users)
    {
        // Sanitize user ids.
        $users = array_unique($users);
        JArrayHelper::toInteger($users);

        // Remove any values of zero.
        if (array_search(0, $users, true)) {
            unset($users[array_search(0, $users, true)]);
        }

        if (empty($users)) {
            return true;
        }

        $query = $this->_db->getQuery(true);

        foreach ($users AS $uid)
        {
            $query->clear()
                  ->insert('#__pk_task_assignees')
                  ->values('null, ' . $pk . ', ' . $uid);

            try {
                $this->_db->setQuery($query);
                $this->_db->execute();
            }
            catch (RuntimeException $e) {
                $this->setError($e->getMessage());
                return false;
            }
        }

        // Update user count in main task record
        $query->clear()
              ->select('COUNT(*)')
              ->from('#__pk_task_assignees')
              ->where('task_id = ' . $pk);

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadResult();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }


        $query->clear()
              ->update('#__pk_tasks')
              ->set('assignee_count = ' . $count)
              ->where('id = ' . $pk);

        try {
            $this->_db->setQuery($query);
            $this->_db->execute();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterAssign', array('com_pktasks.task', $pk, $users));

        return true;
    }


    /**
     * Assigns users to a given task
     *
     * @param     integer    $pk       The task id
     * @param     array      $users    The users
     *
     * @return    boolean
     */
    public function unassign($pk, $users)
    {
        // Sanitize user ids.
        $users = array_unique($users);
        JArrayHelper::toInteger($users);

        // Remove any values of zero.
        if (array_search(0, $users, true)) {
            unset($users[array_search(0, $users, true)]);
        }

        if (empty($users)) {
            return true;
        }

        $query = $this->_db->getQuery(true);

        $query->delete('#__pk_task_assignees')
              ->where('task_id = ' . $pk)
              ->where('user_id IN(' . implode(', ', $users) . ')');

        try {
            $this->_db->setQuery($query);
            $this->_db->execute();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }


        // Update user count in main task record
        $query->clear()
              ->select('COUNT(*)')
              ->from('#__pk_task_assignees')
              ->where('task_id = ' . $pk);

        try {
            $this->_db->setQuery($query);
            $count = $this->_db->loadResult();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }


        $query->clear()
              ->update('#__pk_tasks')
              ->set('assignee_count = ' . $count)
              ->where('id = ' . $pk);

        try {
            $this->_db->setQuery($query);
            $this->_db->execute();
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterUnassign', array('com_pktasks.task', $pk, $users));

        return true;
    }
}
