<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKMilestonesModelMilestone extends PKModelAdmin
{
    /**
     * Method to get the data that should be injected in the form.
     *
     * @return    mixed    The data for the form.
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app     = JFactory::getApplication();
        $context = ($app->isSite() ? 'form' : 'milestone');
        $data    = $app->getUserState('com_pkmilestones.edit.' . $context . '.data', array());

        if (empty($data)) {
            $data = $this->getItem();

            // Prime some default values.
            if ($this->getState($context . '.id') == 0) {
                $filters        = (array) $app->getUserState('com_pkmilestones.milestones.filter');
                $filter_project = isset($filters['project_id']) ? $filters['project_id'] : $app->getUserState('projectknife.project_id');

                $data->set('project_id', $app->input->getInt('project_id', (int) $filter_project));
                $data->set('access', 0);
            }
            else {
                if ($data->get('start_date_inherit')) {
                    $data->set('start_date', '');
                }

                if ($data->get('due_date_inherit')) {
                    $data->set('due_date', '');
                }
            }
        }

        $this->preprocessData('com_pkmilestones.milestone', $data);

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
        if (PKUserHelper::authProject('milestone.delete', $record->project_id)) {
            return true;
        }

        $delete_own = PKUserHelper::authProject('milestone.delete.own', $record->project_id);
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
        if (PKUserHelper::authProject('milestone.edit.state', $record->project_id)) {
            return true;
        }

        $edit_own = PKUserHelper::authProject('milestone.edit.own.state', $record->project_id);
        $user     = JFactory::getUser();

        return ($edit_own && $user->id > 0 && $user->id == $record->created_by);
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
              ->from('#__pk_milestones')
              ->where('project_id = ' . $project_id)
              ->where('alias = ' . $this->_db->quote($alias))
              ->where('id != ' . intval($id));

        try {
            $this->_db->setQuery($query);
            $count = (int) $this->_db->loadResult();
        }
        catch (RuntimeException $e) {
            throw new RuntimeException('Could not generate unique alias due to a db error', 500, $e);
        }


        if ($id > 0 && $count == 0) {
            // No duplicate found for existing item.
            return array($title, $alias);
        }
        elseif ($id == 0 && $count == 0) {
            // No duplicate found for new item.
            return array($title, $alias);
        }
        else {
            // Found duplicate. Increment title and alias
            $query->clear()
                  ->select('COUNT(id)')
                  ->from('#__pk_milestones')
                  ->where('project_id = ' . $project_id)
                  ->where('alias = ' . $this->_db->quote($alias))
                  ->where('id != ' . intval($id));

            $this->_db->setQuery($query);

            try {
                while ($this->_db->loadResult())
                {
                    $title = JString::increment($title);
                    $alias = JString::increment($alias, 'dash');

                    $query->clear()
                          ->select('COUNT(id)')
                          ->from('#__pk_milestones')
                          ->where('project_id = ' . $project_id)
                          ->where('alias = ' . $this->_db->quote($alias))
                          ->where('id != ' . intval($id));

                    $this->_db->setQuery($query);
                }
            }
            catch (RuntimeException $e) {
                throw new RuntimeException('Could not generate unique alias due to a db error', 500, $e);
            }
        }

        return array($title, $alias);
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
        $condition   = array();
        $condition[] = 'project_id = ' . (int) $table->project_id;

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
            $table->reorder('project_id = ' . (int) $table->project_id);
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
            JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_pkmilestones/models/forms');
        }

        $form = $this->loadForm('com_pkmilestones.milestone', 'milestone', array('control' => 'jform', 'load_data' => $do_load));

        if (empty($form)) {
            return false;
        }

        $input  = JFactory::getApplication()->input;
        $params = JComponentHelper::getParams('com_pkmilestones');

        // Get item id
        $id  = $input->getUint('id', $this->getState('milestone.id', 0));
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
        if (!PKUserHelper::authProject('milestone.edit.state', $pid)) {
            $can_edit_state = false;

            if ($pid) {
                // Check if owner
                if (PKUserHelper::authProject('milestone.edit.own.state', $pid)) {
                    if ($id) {
                        $user  = JFactory::getUser();
                        $query = $this->_db->getQuery(true);

                        $query->select('created_by')
                              ->from('#__pk_milestones')
                              ->where('id = ' . $id);

                        $this->_db->setQuery($query);
                        $project_author = (int) $this->_db->loadResult();

                        if ($user->id > 0 && $user->id == $project_author) {
                            $can_edit_state = true;
                        }
                    }
                    else {
                        // This is a new item - Allow change state bc the user will be the owner upon creation.
                        $can_edit_state = true;
                    }
                }
            }
            elseif (!$id && PKUserHelper::authProject('milestone.edit.own.state', 'any')) {
                // This is a new item, and no project is selected. Allow edit state if the user is allowed on any projects.
                $can_edit_state = true;
            }

            if (!$can_edit_state) {
                $form->setFieldAttribute('published', 'type', 'hidden');
                $form->setFieldAttribute('published', 'filter', 'unset');
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
    public function getTable($name = 'Milestone', $prefix = 'PKmilestonesTable', $options = array())
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
            $item->tags = new JHelperTags();
            $item->tags->getTagIds($item->id, 'com_pkmilestones.milestone');

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


            // Get task count
            $item->tasks_count     = $this->getTasksCount($item->id);
            $item->tasks_completed = 0;

            if ($item->tasks_count) {
                $item->tasks_completed = $this->getTasksCompletedCount($item->id);
            }
        }

        return $item;
    }


    /**
     * Returns the total number of active tasks for the given milestone
     *
     * @param     integer    $pk       The milestone id
     *
     * @return    integer    $count    The number of tasks
     */
    public function getTasksCount($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        if (!$pk) {
            return 0;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->clear()
              ->select('COUNT(*)')
              ->from('#__pk_tasks')
              ->where('milestone_id = ' . intval($pk))
              ->where('published > 0');

        $db->setQuery($query);
        $count = (int) $db->loadResult();

        return $count;
    }


    /**
     * Returns the total number of completed tasks for the given milestone
     *
     * @param     array    $pk       The milestone id
     *
     * @return    array    $count    The number of tasks
     */
    public function getTasksCompletedCount($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        if (!$pk) {
            return 0;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->clear()
              ->select('COUNT(*)')
              ->from('#__pk_tasks')
              ->where('milestone_id = ' . intval($pk))
              ->where('published > 0')
              ->where('progress = 100');

        $db->setQuery($query);
        $count = (int) $db->loadResult();

        return $count;
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
        $params = JComponentHelper::getParams('com_pkmilestones');
        $table  = $this->getTable();
        $key    = $table->getKeyName();
        $pk     = (int) array_key_exists($key, $data) ? $data[$key] : $this->getState($this->getName() . '.id');

        if ($pk > 0) {
            $table->load($pk);
            $data['id'] = $pk;
        }

        $data['title']      = trim(isset($data['title'])       ? $data['title']      : $table->title);
        $data['alias']      = isset($data['alias'])            ? $data['alias']      : $table->alias;
        $data['project_id'] = (int) isset($data['project_id']) ? $data['project_id'] : $table->project_id;
        $data['start_date'] = (isset($data['start_date'])      ? $data['start_date'] : $table->start_date);
        $data['due_date']   = (isset($data['due_date'])        ? $data['due_date']   : $table->due_date);

        if (empty($data['title'])) {
            $data['title'] = JText::_('COM_PKMILESTONES_NEW_MILESTONE_TITLE');
        }

        if ((int) $params->get('auto_alias', '1') === 1) {
            // Auto-Alias
            $data['alias'] = '';
        }

        // Generate unique title and alias
        list($data['title'], $data['alias']) = $this->uniqueTitleAlias($data['title'], $data['alias'], $data['project_id'], $pk);


        // Handle viewing access
        $query = $this->_db->getQuery(true);

        $query->select('access')
              ->from('#__pk_projects')
              ->where('id = ' . $data['project_id']);

        $this->_db->setQuery($query);
        $project_access = (int) $this->_db->loadResult();

        if ($params->get('auto_access', '1') == '1') {
            // Always inherit
            $data['access'] = $project_access;
            $data['access_inherit'] = 1;
        }
        else {
            if (array_key_exists('access', $data)) {
                $data['access'] = (int) $data['access'];

                if ($data['access'] === 0 || $data['access'] === $project_access) {
                    $data['access'] = $project_access;
                    $data['access_inherit'] = 1;
                }
                else {
                    $data['access_inherit'] = 0;
                }
            }
        }

        // Set default publishing state to 1 for new items
        if ($is_new && !array_key_exists('published', $data)) {
            $data['published'] = 1;
        }

        // Handle start and due date
        $null_date = $this->_db->getNullDate();
        $date      = JDate::getInstance();
        $now_date  = $date->toSql();


        // Inherit start date?
        if (empty($data['start_date']) || $data['start_date'] == $null_date) {
            $data['start_date_inherit'] = 1;

            if ($is_new) {
                $data['start_date'] = $now_date;
            }
            else {
                $query = $this->_db->getQuery(true);

                // Load earliest starting task
                $query->clear()
                      ->select('id, start_date')
                      ->from('#__pk_tasks')
                      ->where('milestone_id = ' . (int) $table->id)
                      ->where('published > 0')
                      ->order('start_date ASC');

                $this->_db->setQuery($query, 0, 1);
                $start_task = $this->_db->loadObject();

                $data['start_date']         = $start_task->start_date;
                $data['start_date_task_id'] = $start_task->id;
            }
        }


        // Inherit due date?
        if (empty($data['due_date']) || $data['due_date'] == $null_date) {
            $data['due_date_inherit'] = 1;

            if ($is_new) {
                $data['due_date'] = $now_date;
            }
            else {
                $query = $this->_db->getQuery(true);

                // Load earliest starting task
                $query->clear()
                      ->select('id, due_date')
                      ->from('#__pk_tasks')
                      ->where('milestone_id = ' . (int) $table->id)
                      ->where('published > 0')
                      ->order('due_date DESC');

                $this->_db->setQuery($query, 0, 1);
                $due_task = $this->_db->loadObject();

                $data['due_date']         = $due_task->due_date;
                $data['due_date_task_id'] = $due_task->id;
            }
        }


        $start_time = strtotime($data['start_date']);
        $due_time   = strtotime($data['due_date']);

        // Make sure the due date comes after the start date
        if ($start_time > $due_time) {
            $data['due_date'] = $data['start_date'];
            $due_time         = $start_time;
        }

        // Calculate the duration
        $data['duration'] = 1;
        $delta = $due_time - $start_time;

        if ($delta > 0) {
            $data['duration'] += ceil($delta / 86400) - 1;
        }

        parent::prepareSaveData($data, $is_new);
    }

    /**
     * Copies one or more milestones
     *
     * @param     array      $pks        the milestones to copy
     * @param     array      $options    The copy settings
     *
     * @return    boolean
     */
    public function copy($pks, $options = array())
    {
        // Sanitize ids.
        $pks = array_unique($pks);
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        $k = array_search(0, $pks, true);

        while ($k !== false)
        {
            unset($pks[$k]);
            $k = array_search(0, $pks, true);
        }

        if (empty($pks)) {
            $this->setError(JText::_('JGLOBAL_NO_ITEM_SELECTED'));
            return false;
        }

        // Set default options
        $options = (array) $options;

        if (!isset($options['project_id'])) {
            $options['project_id'] = '';
        }

        if (!isset($options['access'])) {
            $options['access'] = '';
        }

        if (!isset($options['include'])) {
            $options['include'] = array();
        }

        // Copy items
        $count = count($pks);
        $table = $this->getTable();
        $data  = array();
        $ref   = array();
        $state = $this->getName() . '.id';

        for ($i = 0; $i != $count; $i++)
        {
            $id = $pks[$i];

            $table->reset();

            if (!$table->load($id)) {
                continue;
            }

            $data = (array) $table;
            $data['id'] = 0;

            unset(
                $data['created'],
                $data['created_by'], $data['checked_out'],
                $data['checked_out_time']
            );

            if (is_numeric($options['project_id'])) {
                $data['project_id'] = (int) $options['project_id'];
            }

            if (is_numeric($options['access'])) {
                $data['access'] = (int) $options['access'];
            }

            if (!$this->save($data)) {
                continue;
            }

            $ref[$id] = (int) $this->getState($state);
        }

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterCopy', array('com_pkmilestones.milestones', $ref, $options));

        return true;
    }


    /**
     * Re-calculates the progress of the given milestones
     *
     * @param     array      $pks    The milestone ids
     *
     * @return    boolean
     */
    public function progress($pks)
    {
        // Sanitize ids.
        $pks = array_unique($pks);
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        $k = array_search(0, $pks, true);

        while ($k !== false)
        {
            unset($pks[$k]);
            $k = array_search(0, $pks, true);
        }

        if (empty($pks)) {
            return false;
        }

        $query = $this->_db->getQuery(true);

        // Calculate new progress
        $query->select('milestone_id, (SUM(progress)/COUNT(*)) AS prog')
              ->from('#__pk_tasks')
              ->where('milestone_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->group('milestone_id');

        $this->_db->setQuery($query);
        $new_progress = $this->_db->loadAssocList('milestone_id', 'prog');

        // Get current progress
        $query->clear();
        $query->select('id, progress')
              ->from('#__pk_milestones')
              ->where('id IN(' . implode(', ', $pks) . ')');

        $this->_db->setQuery($query);
        $old_progress = $this->_db->loadAssocList('id', 'progress');


        // Update progress and track change
        $changes = array();

        foreach ($pks AS $pk)
        {
            if (!isset($old_progress[$pk])) {
                $old_progress[$pk] = 0;
            }

            if (!isset($new_progress[$pk])) {
                $new_progress[$pk] = 0;
            }

            $new_progress[$pk] = floor((int) $new_progress[$pk]);

            if ((int) $old_progress[$pk] != $new_progress[$pk]) {
                $changes[$pk] = array((int) $old_progress[$pk], $new_progress[$pk]);

                $query->clear()
                      ->update('#__pk_milestones')
                      ->set('progress = ' . $new_progress[$pk])
                      ->where('id = ' . $pk);

                $this->_db->setQuery($query);
                $this->_db->execute();
            }
        }


        // Trigger onProjectknifeAfterProgress event
        if (count($changes)) {
            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin('projectknife');

            $dispatcher->trigger('onProjectknifeAfterProgress', array('com_pkmilestones.milestones', $changes));
        }

        return true;
    }


    /**
     * Sets the schedule of the given milestones
     *
     * @param     array      $pks    The milestone ids
     *
     * @return    boolean
     */
    public function schedule($pks)
    {
        // Sanitize ids.
        $pks = array_unique($pks);
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        $k = array_search(0, $pks, true);

        while ($k !== false)
        {
            unset($pks[$k]);
            $k = array_search(0, $pks, true);
        }

        if (empty($pks)) {
            return false;
        }


        $query = $this->_db->getQuery(true);

        $query->select('id, start_date, due_date')
              ->from('#__pk_milestones')
              ->where('id IN(' . implode(', ', $pks) . ')')
              ->order('id ASC');

        $this->_db->setQuery($query);
        $dates = $this->_db->loadAssocList('id');

        $count      = count($pks);
        $id         = 0;
        $start_task = null;
        $due_task   = null;
        $start_time = 0;
        $due_time   = 0;

        for ($i = 0; $i < $count; $i++)
        {
            $id = $pks[$i];

            if (!isset($dates[$id])) {
                continue;
            }

            // Load task min start date
            $query->clear()
                  ->select('id, start_date')
                  ->from('#__pk_tasks')
                  ->where('milestone_id = ' . $id)
                  ->where('published > 0')
                  ->order('start_date ASC');

            $this->_db->setQuery($query, 0, 1);
            $start_task = $this->_db->loadObject();

            // Load task max due date
            $query->clear()
                  ->select('id, due_date')
                  ->from('#__pk_tasks')
                  ->where('milestone_id = ' . $id)
                  ->where('published > 0')
                  ->order('due_date DESC');

            $this->_db->setQuery($query, 0, 1);
            $due_task = $this->_db->loadObject();

            // Update the milestone schedule
            $query->clear()
                  ->update('#__pk_milestones');

            // Update start date
            if (empty($start_task) || $start_task->id == null) {
                $start_time = strtotime($dates[$id]['start_date']);

                $query->set('start_date = ' . $this->_db->quote($dates[$id]['start_date']))
                      ->set('start_date_task_id = 0');
            }
            else {
                $start_time = strtotime($start_task->start_date);

                $query->set('start_date = ' . $this->_db->quote($start_task->start_date))
                      ->set('start_date_task_id = ' . (int) $start_task->id);
            }

            // Update due date
            if (empty($due_task) || $due_task->id == null) {
                $due_time = strtotime($dates[$i]['due_date']);

                $query->set('due_date = ' . $this->_db->quote($dates[$id]['due_date']))
                      ->set('due_date_task_id = 0');
            }
            else {
                $due_time = strtotime($due_task->due_date);

                $query->set('due_date = ' . $this->_db->quote($due_task->due_date))
                      ->set('due_date_task_id = ' . (int) $due_task->id);
            }

            // Update the duration
            $duration = 1;
            $delta    = $due_time - $start_time;

            if ($delta > 0) {
                $duration += ceil($delta / 86400) - 1;

                $query->set('duration = ' . $duration);
            }

            $query->where('id = ' . (int) $id);

            $this->_db->setQuery($query);
            $this->_db->execute();
        }

        return true;
    }
}
