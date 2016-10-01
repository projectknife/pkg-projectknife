<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;

JLoader::register('PKprojectsTableProject', JPATH_ADMINISTRATOR . '/components/com_pkprojects/tables/project.php');


class PKprojectsModelProject extends PKModelAdmin
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
        $context = ($app->isSite() ? 'form' : 'project');
        $data    = $app->getUserState('com_pkprojects.edit.' . $context . '.data', array());

        if (empty($data)) {
            $data = $this->getItem();

            // Prime some default values.
            if ($this->getState($context . '.id') == 0) {
                $filters      = (array) $app->getUserState('com_pkprojects.projects.filter');
                $filter_catid = isset($filters['category_id']) ? $filters['category_id'] : null;

                $data->set('category_id', $app->input->getInt('category_id',  $filter_catid));
                $data->set('access',      0);
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

        $this->preprocessData('com_pkprojects.project', $data);

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
        if (PKUserHelper::authProject('core.delete', $record->id)) {
            return true;
        }

        if (PKUserHelper::authProject('core.delete.own')) {
            $user = JFactory::getUser();

            if ($user->id > 0 && $user->id == $record->created_by) {
                return true;
            }
        }

        return false;
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
        if (PKUserHelper::authProject('core.edit.state', $record->id)) {
            return true;
        }

        if (PKUserHelper::authProject('core.edit.own.state')) {
            $user = JFactory::getUser();

            if ($user->id > 0 && $user->id == $record->created_by) {
                return true;
            }
        }

        return false;
    }


    /**
     * Method to change the title & alias.
     *
     * @param     string     $title    The title.
     * @param     string     $alias    The alias.
     * @param     integer    $id       The item id
     * @return    array                Contains the modified title and alias.
     */
    protected function uniqueTitleAlias($title, $alias, $id)
    {
        $id = (int) $id;

        // Sanitize alias
        if (trim($alias) === '') {
            if (JFactory::getConfig()->get('unicodeslugs') == 1) {
                $alias = JFilterOutput::stringURLUnicodeSlug($title);
            }
            else {
                $alias = JFilterOutput::stringURLSafe($title);
            }

            if (trim(str_replace('-', '', $alias)) === '') {
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

        // Count same existing aliases
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(id)')
              ->from('#__pk_projects')
              ->where('alias = ' . $this->_db->quote($alias))
              ->where('id != ' . $id);

        $this->_db->setQuery($query);
        $count = (int) $this->_db->loadResult();

        if ($id > 0 && $count === 0) {
            // No duplicate found for existing item.
            return array($title, $alias);
        }
        elseif ($id === 0 && $count === 0) {
            // No duplicate found for new item.
            return array($title, $alias);
        }
        else {
            // Found duplicate. Increment title and alias
            $query->clear()
                  ->select('COUNT(id)')
                  ->from('#__pk_projects')
                  ->where('alias = ' . $this->_db->quote($alias))
                  ->where('id != ' . $id);

            $this->_db->setQuery($query);

            while ($this->_db->loadResult())
            {
                $title = JString::increment($title);
                $alias = JString::increment($alias, 'dash');

                $query->clear()
                      ->select('COUNT(id)')
                      ->from('#__pk_projects')
                      ->where('alias = ' . $this->_db->quote($alias))
                      ->where('id != ' . $id);

                $this->_db->setQuery($query);
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
        $condition = array();
        $condition[] = 'category_id = ' . (int) $table->catid;

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
            $table->reorder('category_id = ' . (int) $table->catid);
        }
    }


    /**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        // Get tags
        if (is_object($item) && !empty($item->id)) {
            $item->tags = new JHelperTags();
            $item->tags->getTagIds($item->id, 'com_pkprojects.project');
        }

        return $item;
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
            JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_pkprojects/models/forms');
        }

        $form = $this->loadForm('com_pkprojects.project', 'project', array('control' => 'jform', 'load_data' => $do_load));

        if (empty($form)) {
            return false;
        }

        $input  = JFactory::getApplication()->input;
        $params = JComponentHelper::getParams('com_pkprojects');

        // Get item id
        $id = $input->getUint('id', $this->getState('project.id', 0));


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
        if (!PKUserHelper::authProject('core.edit.state', $id)) {
            $can_edit_state = false;

            if ($id) {
                // Check if owner
                if (PKUserHelper::authProject('core.edit.own.state')) {
                    $user  = JFactory::getUser();
                    $query = $this->_db->getQuery(true);

                    $query->select('created_by')
                          ->from('#__pk_projects')
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
    public function getTable($name = 'Project', $prefix = 'PKprojectsTable', $options = array())
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
     * Method to prepare the user input before saving it
     *
     * @param     array    $data      The data to save
     * @param     bool     $is_new    Indicated whether this is a new item or not
     *
     * @return    void
     */
    protected function prepareSaveData(&$data, $is_new)
    {
        $params = JComponentHelper::getParams('com_pkprojects');
        $table  = $this->getTable();
        $key    = $table->getKeyName();
        $pk     = (int) array_key_exists($key, $data) ? $data[$key] : $this->getState($this->getName() . '.id');

        if ($pk > 0) {
            $table->load($pk);
            $data['id'] = $pk;
        }

        $data['title']       = trim(isset($data['title'])         ? $data['title']       : $table->title);
        $data['alias']       = isset($data['alias'])              ? $data['alias']       : $table->alias;
        $data['category_id'] = (int) (isset($data['category_id']) ? $data['category_id'] : $table->category_id);
        $data['start_date']  = (isset($data['start_date'])        ? $data['start_date']  : $table->start_date);
        $data['due_date']    = (isset($data['due_date'])          ? $data['due_date']    : $table->due_date);

        // Handle empty title
        if (empty($data['title'])) {
            $data['title'] = JText::_('COM_PKPROJECTS_NEW_PROJECT_TITLE');
        }

        // Auto-Alias
        if ((int) $params->get('auto_alias', 1) === 1) {
            $data['alias'] = '';
        }

        // Generate unique title and alias
        list($data['title'], $data['alias']) = $this->uniqueTitleAlias($data['title'], $data['alias'], $pk);

        // Handle viewing access
        $category_access = 0;
        $access_inherit  = 1;

        if ($data['category_id'] > 0) {
            $query = $this->_db->getQuery(true);

            $query->select('access')
                  ->from('#__categories')
                  ->where('id = ' . $data['category_id']);

            $this->_db->setQuery($query);
            $category_access = (int) $this->_db->loadResult();
        }

        if (!$category_access) {
            // Fall back to global access if no category is selected.
            $category_access = (int) JFactory::getConfig()->get('access');
            $access_inherit  = 0;
        }

        if ($params->get('auto_access', '1') == '1') {
            // Always inherit
            $data['access'] = $category_access;
            $data['access_inherit'] = $access_inherit;
        }
        else {
            if (array_key_exists('access', $data)) {
                $data['access'] = (int) $data['access'];

                if ($data['access'] === 0 || $data['access'] === $category_access) {
                    $data['access'] = $category_access;
                    $data['access_inherit'] = $access_inherit;
                }
                else {
                    $data['access_inherit'] = 0;
                }
            }
        }

        // Default state is published
        if ($is_new && !isset($data['published'])) {
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
                      ->where('project_id = ' . (int) $table->id)
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
                      ->where('project_id = ' . (int) $table->id)
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
     * Copies a list of projects.
     *
     * @param     integer    $pks        The projects to copy.
     * @param     array      $options    Config options
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

        if (!isset($options['category_id'])) {
            $options['category_id'] = '';
        }

        if (!isset($options['access'])) {
            $options['access'] = '';
        }

        if (!isset($options['include'])) {
            $options['include'] = array();
        }

        // Load asset rules
        $query = $this->_db->getQuery(true);

        $query->select('a.id, a.rules')
              ->from('#__assets AS a')
              ->join('inner', '#__pk_projects AS p ON p.asset_id = a.id')
              ->where('p.id IN(' . implode(', ', $pks) . ')')
              ->group('a.id, a.rules');

        $this->_db->setQuery($query);
        $rules = $this->_db->loadAssocList('id', 'rules');

        // Copy items
        $count = count($pks);
        $table = $this->getTable();
        $state = $this->getName() . '.id';
        $data  = array();
        $ref   = array();

        for ($i = 0; $i != $count; $i++)
        {
            $id = $pks[$i];

            $table->reset();

            if (!$table->load($id)) {
                continue;
            }

            $data = (array) $table;

            $asset_id   = $data['asset_id'];
            $data['id'] = 0;


            if (isset($rules[$asset_id])) {
                if ($rules[$asset_id] !== '') {
                    $data['rules'] = json_decode($rules[$asset_id], true);
                }
                else {
                    $data['rules'] = array();
                }
            }
            else {
                $data['rules'] = array();
            }

            unset(
                $data['asset_id'], $data['created'],
                $data['created_by'], $data['checked_out'],
                $data['checked_out_time']
            );

            if (is_numeric($options['category_id'])) {
                $data['category_id'] = (int) $options['category_id'];
            }

            if (is_numeric($options['access'])) {
                $data['access'] = (int) $options['access'];
            }

            if (!$this->save($data)) {
                continue;
            }

            $ref[$id] = (int) $this->getState($state);
        }


        if (count($ref)) {
            // Trigger onProjectknifeAfterCopy event
            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin('projectknife');

            $dispatcher->trigger('onProjectknifeAfterCopy', array('com_pkprojects.projects', $ref, $options));
        }

        return true;
    }


    /**
     * Re-calculates the progress of the given projects
     *
     * @param     array      $pks    The project ids
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
        $query->select('project_id, (SUM(progress)/COUNT(*)) AS prog')
              ->from('#__pk_tasks')
              ->where('project_id IN(' . implode(', ', $pks) . ')')
              ->where('published > 0')
              ->group('project_id');

        $this->_db->setQuery($query);
        $new_progress = $this->_db->loadAssocList('project_id', 'prog');

        // Get current progress
        $query->clear();
        $query->select('id, progress')
              ->from('#__pk_projects')
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
                      ->update('#__pk_projects')
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

            $dispatcher->trigger('onProjectknifeAfterProgress', array('com_pkprojects.projects', $changes));
        }

        return true;
    }


    /**
     * Automatically updates the schedule of the given projects
     *
     * @param     array      $pks    The project ids
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

        $query->select('id, start_date, due_date, duration')
              ->from('#__pk_projects')
              ->where('id IN(' . implode(', ', $pks) . ')')
              ->order('id ASC');

        $this->_db->setQuery($query);
        $dates = $this->_db->loadAssocList('id');

        $count      = count($pks);
        $id         = 0;
        $start_task = null;
        $due_task   = null;

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
                  ->where('project_id = ' . $id)
                  ->where('published > 0')
                  ->order('start_date ASC');

            $this->_db->setQuery($query, 0, 1);
            $start_task = $this->_db->loadObject();

            // Load task max due date
            $query->clear()
                  ->select('id, due_date')
                  ->from('#__pk_tasks')
                  ->where('project_id = ' . $id)
                  ->where('published > 0')
                  ->order('due_date DESC');

            $this->_db->setQuery($query, 0, 1);
            $due_task = $this->_db->loadObject();

            // Update the project schedule
            $query->clear()
                  ->update('#__pk_projects');

            // Update start date
            if (empty($start_task) || $start_task->id == null) {
                $query->set('start_date = ' . $this->_db->quote($dates[$id]['start_date']))
                      ->set('start_date_task_id = 0');
            }
            else {
                $query->set('start_date = ' . $this->_db->quote($start_task->start_date))
                      ->set('start_date_task_id = ' . (int) $start_task->id);
            }

            // Update due date
            if (empty($due_task) || $due_task->id == null) {
                $query->set('due_date = ' . $this->_db->quote($dates[$id]['due_date']))
                      ->set('due_date_task_id = 0');
            }
            else {
                $query->set('due_date = ' . $this->_db->quote($due_task->due_date))
                      ->set('due_date_task_id = ' . (int) $due_task->id);
            }

            $query->where('id = ' . (int) $id);

            $this->_db->setQuery($query);
            $this->_db->execute();
        }

        return true;
    }
}
