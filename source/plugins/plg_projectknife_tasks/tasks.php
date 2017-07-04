<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_milestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


JLoader::register('PKtasksModelTask', JPATH_ADMINISTRATOR . '/components/com_pktasks/models/task.php');
JLoader::register('PKtasksTableTask', JPATH_ADMINISTRATOR . '/components/com_pktasks/tables/task.php');


class plgProjectknifeTasks extends JPlugin
{
    /**
     * Indicates whether to trigger the change access event or not
     *
     * @var    boolean
     */
    protected $trigger_change_access;

    /**
     * The previous access level of an item
     *
     * @var    integer
     */
    protected $old_access;


    /**
     * Constructor
     *
     * @param    object    $subject    The object to observe
     * @param    array     $config     An optional associative array of configuration settings.
     */
    public function __construct(&$subject, $config = array())
    {
        // Call parent contructor first
        parent::__construct($subject, $config);

        $this->trigger_change_access = false;
        $this->old_access = 0;
    }


    /**
     * Registers task form fields
     *
     * @param     object     $form
     * @param     array      $data
     *
     * @return    boolean
     */
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm)) {
            return true;
        }

        $this->loadLanguage();
        $form->addFieldPath(__DIR__ . '/fields');

        return true;
    }


    /**
     * "onContentChangeState" event handler
     *
     * @param     string     $context
     * @param     array      $pks
     * @param     integer    $state
     *
     * @return    boolean
     */
    public function onContentChangeState($context, $pks, $state)
    {
        switch ($context)
        {
            case 'com_pkprojects.project':
            case 'com_pkprojects.form':
                return $this->onContentChangeStateProject($context, $pks, $state);
                break;

            case 'com_pkmilestones.milestone':
            case 'com_pkmilestones.form':
                return $this->onContentChangeStateMilestone($context, $pks, $state);
                break;
        }

        return true;
    }


    /**
     * Updates the task state when the parent project state has changed
     *
     * @param     string     $context
     * @param     array      $pks
     * @param     integer    $state
     *
     * @return    boolean
     */
    protected function onContentChangeStateProject($context, $pks, $state)
    {
        if ($state == 1) {
            return true;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->update('#__pk_tasks')
              ->set('published = ' . intval($state))
              ->where('project_id IN(' . implode(', ', $pks) . ')');

        if ($state == 0) {
            $query->where('published NOT IN(-2, 0, 2)');
        }
        else {
            $query->where('published <> -2');
        }

        $db->setQuery($query);
        $db->execute();
    }


    /**
     * Updates the task state when the parent project milestone has changed
     *
     * @param     string     $context
     * @param     array      $pks
     * @param     integer    $state
     *
     * @return    boolean
     */
    protected function onContentChangeStateMilestone($context, $pks, $state)
    {
        if ($state == 1) {
            return true;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->update('#__pk_tasks')
              ->set('published = ' . intval($state))
              ->where('milestone_id IN(' . implode(', ', $pks) . ')');

        if ($state == 0) {
            $query->where('published NOT IN(-2, 0, 2)');
        }
        else {
            $query->where('published <> -2');
        }

        $db->setQuery($query);
        $db->execute();
    }


    /**
     * "onContentAfterDelete" event handler
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     *
     * @return    boolean
     */
    public function onContentAfterDelete($context, $table)
    {
        switch ($context)
        {
            case 'com_pkprojects.project':
            case 'com_pkprojects.form':
                $this->onContentAfterDeleteProject($context, $table);
                break;

            case 'com_pkmilestones.milestone':
            case 'com_pkmilestones.form':
                $this->onContentAfterDeleteMilestone($context, $table);
                break;

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                $this->onContentAfterDeleteTask($context, $table);
                break;
        }

        return true;
    }


    /**
     * Deletes all tasks of the deleted project
     *
     * @param     string    $context    The model context
     * @param     object    $table      The table object instance
     *
     * @return    void
     */
    protected function onContentAfterDeleteProject($context, $table)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__pk_tasks')
              ->where('project_id = ' . (int) $table->id)
              ->where('milestone_id = 0');

        $db->setQuery($query);
        $pks = $db->loadColumn();

        if (count($pks)) {
            $model = JModelLegacy::getInstance('Task', 'PKtasksModel', $config = array('ignore_request' => true));
            $model->delete($pks);
        }
    }


    /**
     * Deletes all tasks of the deleted milestone
     *
     * @param     string    $context    The model context
     * @param     object    $table      The table object instance
     *
     * @return    void
     */
    protected function onContentAfterDeleteMilestone($context, $table)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__pk_tasks')
              ->where('milestone_id = ' . (int) $table->id);

        $db->setQuery($query);
        $pks = $db->loadColumn();

        if (count($pks)) {
            $model = JModelLegacy::getInstance('Task', 'PKtasksModel', $config = array('ignore_request' => true));
            $model->delete($pks);
        }
    }


    /**
     * Deletes task meta data to clean up
     *
     * @param     string    $context    The model context
     * @param     object    $table      The table object instance
     *
     * @return    void
     */
    protected function onContentAfterDeleteTask($context, $table)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);


        // Remove all dependencies
        $query->delete('#__pk_task_dependencies')
              ->where('predecessor_id = ' . (int) $table->id);

        $db->setQuery($query);
        $db->execute();

        $query->clear();
        $query->delete('#__pk_task_dependencies')
              ->where('successor_id = ' . (int) $table->id);

        $db->setQuery($query);
        $db->execute();


        // Remove all assigned users
        $query->clear();
        $query->delete('#__pk_task_assignees')
              ->where('task_id = ' . (int) $table->id);

        $db->setQuery($query);
        $db->execute();
    }


    /**
     * "onContentBeforeSave" event handler
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    public function onContentBeforeSave($context, $table, $is_new)
    {
        switch ($context)
        {
            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentBeforeSaveTask($context, $table, $is_new);
                break;
        }

        return true;
    }


    /**
     * Checks if the access level has changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentBeforeSaveTask($context, $table, $is_new)
    {
        if (!$is_new) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('access')
                  ->from('#__pk_tasks')
                  ->where('id = ' . (int) $table->id);

            $db->setQuery($query);
            $this->old_access = (int) $db->loadResult();

            if ($this->old_access) {
                $this->trigger_change_access = true;
            }
        }

        return true;
    }


    /**
     * "onContentAfterSave" event handler
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    public function onContentAfterSave($context, $table, $is_new)
    {
        switch ($context)
        {
            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentAfterSaveTask($context, $table, $is_new);
                break;
        }

        return true;
    }


    /**
     * Triggers the "onProjectknifeAfterChangeAccess" event
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    public function onContentAfterSaveTask($context, $table, $is_new)
    {
        if ($this->trigger_change_access && $this->old_access !== (int) $table->access) {
            // Trigger onContentAfterChangeAccess event
            $dispatcher = JDispatcher::getInstance();
            $pks        = array((int) $table->id);
            $access     = (int) $table->access;

            $dispatcher->trigger('onProjectknifeAfterChangeAccess', array($context, $pks, $access));

            // Reset
            $this->trigger_change_access = false;
            $this->old_access = 0;
        }

        return true;
    }


    /**
     * "onProjectknifeAfterChangeAccess" event handler
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The affected item id's
     * @param     integer    $access     The new access level
     *
     * @return    boolean
     */
    public function onProjectknifeAfterChangeAccess($context, $pks, $access)
    {
        switch ($context)
        {
            case 'com_pkprojects.project':
            case 'com_pkprojects.form':
                return $this->onProjectknifeAfterChangeAccessProject($context, $pks, $access);
                break;

            case 'com_pkmilestones.milestone':
            case 'com_pkmilestones.form':
                return $this->onProjectknifeAfterChangeAccessMilestone($context, $pks, $access);
                break;
        }

        return true;
    }


    /**
     * Handles project access level inheritance
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The affected item id's
     * @param     integer    $access     The new access level
     *
     * @return    boolean
     */
    protected function onProjectknifeAfterChangeAccessProject($context, $pks, $access)
    {
        $access = (int) $access;
        $count  = count($pks);

        if ($count === 0) return true;

        JArrayHelper::toInteger($pks);

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__pk_tasks')
              ->where('access != ' . $access);

        // Get all tasks that inherit their access from the given projects.
        if ($count === 1) {
            $query->where('project_id = ' . (int) $pks[0]);
        }
        else {
            $query->where('project_id IN(' . implode(', ', $pks) . ')');
        }

        $query->where('milestone_id = 0')
              ->where('access_inherit = 1');

        $db->setQuery($query);

        $tasks = $db->loadColumn();
        $count = count($tasks);

        if ($count === 0) return true;

        // Update affected tasks
        $query->clear()
              ->update('#__pk_tasks')
              ->set('access = ' . $access);

        if ($count === 1) {
            $query->where('id = ' . $tasks[0]);
        }
        else {
            $query->where('id IN(' . implode(', ', $tasks) . ')');
        }

        $db->setQuery($query);
        $db->execute();


        // Trigger the event again
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onProjectknifeAfterChangeAccess', array('com_pktasks.task', $tasks, $access));

        return true;
    }


    /**
     * Handles milestone access level inheritance
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The affected item id's
     * @param     integer    $access     The new access level
     *
     * @return    boolean
     */
    protected function onProjectknifeAfterChangeAccessMilestone($context, $pks, $access)
    {
        $access = (int) $access;
        $count  = count($pks);

        if ($count === 0) return true;

        JArrayHelper::toInteger($pks);

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__pk_tasks')
              ->where('access != ' . $access);

        // Get all tasks that inherit their access from the given milestones.
        if ($count === 1) {
            $query->where('milestone_id = ' . (int) $pks[0]);
        }
        else {
            $query->where('milestone_id IN(' . implode(', ', $pks) . ')');
        }

        $query->where('access_inherit = 1');
        $db->setQuery($query);

        $tasks = $db->loadColumn();
        $count = count($tasks);

        if ($count === 0) return true;

        // Update affected tasks
        $query->clear()
              ->update('#__pk_tasks')
              ->set('access = ' . $access);

        if ($count === 1) {
            $query->where('id = ' . $tasks[0]);
        }
        else {
            $query->where('id IN(' . implode(', ', $tasks) . ')');
        }

        $db->setQuery($query);
        $db->execute();


        // Trigger the event again
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onProjectknifeAfterChangeAccess', array('com_pktasks.task', $tasks, $access));

        return true;
    }


    /**
     * Returns a list of options for copying.
     *
     * @param     string    $context    The copy context
     *
     * @return    array
     */
    public function onProjectknifeCopyOptions($context)
    {
        $supported = array('com_pkprojects.projects', 'com_pkprojects.form', 'com_pkmilestones.milestones', 'com_pkmilestones.form');

        if (in_array($context, $supported)) {
            $opt        = new stdClass();
            $opt->value = 'com_pktasks.tasks';
            $opt->text  = JText::_('COM_PKTASKS_SUBMENU_TASKS');

            return array($opt);
        }
    }


    /**
     * Triggered after one or more items have been copied
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The item id's that have been copied
     * @param     array      $options    The copy options
     *
     * @return    boolean
     */
    public function onProjectknifeAfterCopy($context, $pks, $options)
    {
        switch ($context)
        {
            case 'com_pkprojects.projects':
            case 'com_pkprojects.list':
                return $this->onProjectknifeAfterCopyProject($context, $pks, $options);
                break;

            case 'com_pkmilestones.milestones':
            case 'com_pkmilestones.list':
                return $this->onProjectknifeAfterCopyMilestone($context, $pks, $options);
                break;
        }

        return true;
    }


    /**
     * Triggered after one or more projects have been copied
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The item id's that have been copied
     * @param     array      $options    The copy options
     *
     * @return    boolean
     */
    protected function onProjectknifeAfterCopyProject($context, $pks, $options)
    {
        $options['include'] = array_key_exists('include', $options) ? $options['include'] : array();

        if (!array_key_exists('com_pktasks.tasks', $options['include'])) {
            return true;
        }
        elseif ($options['include']['com_pktasks.tasks'] == 0) {
            return true;
        }

        if (array_key_exists('catid', $options)) {
            unset($options['catid']);
        }

        $db      = JFactory::getDbo();
        $model   = PKtasksModelTask::getInstance('Task', 'PKtasksModel');
        $query   = $db->getQuery(true);

        $options['ignore_permissions'] = true;

        // Copy tasks to new projects
        foreach ($pks AS $old_id => $new_id)
        {
            $query->clear()
                  ->select('id')
                  ->from('#__pk_tasks')
                  ->where('project_id = ' . (int) $old_id)
                  ->where('milestone_id = 0');

            $db->setQuery($query);
            $items = $db->loadColumn();

            if (empty($items)) {
                continue;
            }

            $options['project_id'] = (int) $new_id;

            $model->copy($items, $options);
        }

        return true;
    }


    /**
     * Triggered after one or more milestones have been copied
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The item id's that have been copied
     * @param     array      $options    The copy options
     *
     * @return    boolean
     */
    protected function onProjectknifeAfterCopyMilestone($context, $pks, $options)
    {
        $options['include'] = array_key_exists('include', $options) ? $options['include'] : array();

        if (!array_key_exists('com_pktasks.tasks', $options['include'])) {
            return true;
        }
        elseif ($options['include']['com_pktasks.tasks'] == 0) {
            return true;
        }

        if (array_key_exists('catid', $options)) {
            unset($options['catid']);
        }

        $db      = JFactory::getDbo();
        $model   = PKtasksModelTask::getInstance('Task', 'PKtasksModel');
        $query   = $db->getQuery(true);

        $options['ignore_permissions'] = true;

        // Copy tasks to new milestones
        $new_ids = array_values($pks);

        $query->select('id, project_id')
              ->from('#__pk_milestones')
              ->where('id IN(' . implode(', ', $new_ids) . ')');

        $db->setQuery($query);
        $projects = $db->loadAssocList('id', 'project_id');

        foreach ($pks AS $old_id => $new_id)
        {
            $query->clear()
                  ->select('id')
                  ->from('#__pk_tasks')
                  ->where('milestone_id = ' . (int) $old_id);

            $db->setQuery($query);
            $items = $db->loadColumn();

            if (empty($items)) {
                continue;
            }

            $str_id = strval($new_id);

            if (!array_key_exists($str_id, $projects)) {
                continue;
            }

            $options['project_id']   = (int) $projects[$str_id];
            $options['milestone_id'] = (int) $new_id;

            $model->copy($items, $options);
        }

        return true;
    }


    /**
     * Injects filter options into the mod_pkfilters module
     *
     * @param     string    $context
     * @param     string    $location
     * @param     array     $filters
     *
     * @return    void
     */
    public function onProjectknifeBeforeDisplayFilter($context, $location, &$filters)
    {
        if ($location != 'site' || $context != 'com_pktasks.list') {
            return;
        }


        // Get filter options from model
        $model = JModelLegacy::getInstance('List', 'PKtasksModel');
        $state = $model->getState();

        // Project filter
        $options = array_merge(
            array(JHtml::_('select.option', '',  JText::_('COM_PKPROJECTS_OPTION_SELECT_PROJECT'))),
            $model->getProjectOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_project_id',
            null,
            'value',
            'text',
            $state->get('filter.project_id')
        );

        // Milestone filter
        $ms_options = array();
        $filter_project = (int) $state->get('filter.project_id');

        if ($filter_project) {
            $filter     = array('project_id' => $filter_project);
            $ms_options = $model->getMilestoneOptions($filter);
        }

        $options = array_merge(
            array(
                JHtml::_('select.option', '',  JText::_('COM_PKMILESTONES_OPTION_SELECT_MILESTONE')),
                JHtml::_('select.option', '0',  '*' . JText::_('COM_PKMILESTONES_OPTION_NO_MILESTONE') . '*')
            ),
            $ms_options
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_milestone_id',
            null,
            'value',
            'text',
            $state->get('filter.milestone_id')
        );

        // Tag filter
        $options = array_merge(
            array(JHtml::_('select.option', '',  '- ' . JText::_('PKGLOBAL_SELECT_TAG') . ' -')),
            $model->getTagOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_tag_id',
            null,
            'value',
            'text',
            $state->get('filter.tag_id')
        );

        // Assignee filter
        $options = array_merge(
            array(
                JHtml::_('select.option', '',  '- ' . JText::_('COM_PKTASKS_SELECT_ASSIGNEE') . ' -'),
                JHtml::_('select.option', 'unassigned',  '*' . JText::_('COM_PKTASKS_UNASSIGNED') . '*'),
                JHtml::_('select.option', 'me',  '*' . JText::_('COM_PKTASKS_ASSIGNED_TO_ME') . '*'),
                JHtml::_('select.option', 'notme',  '*' . JText::_('COM_PKTASKS_NOT_ASSIGNED_TO_ME') . '*'),
            ),
            $model->getAssigneeOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_assignee_id',
            null,
            'value',
            'text',
            $state->get('filter.assignee_id')
        );

        // Priority filter
        $options = array_merge(
            array(
                JHtml::_('select.option', '',  '- ' . JText::_('COM_PKTASKS_SELECT_PRIORITY') . ' -')
            ),
            $model->getPriorityOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_priority',
            null,
            'value',
            'text',
            $state->get('filter.priority')
        );

        // Publishing state filter
        if (PKUserHelper::authProject('task.edit.state', $filter_project) || PKUserHelper::authProject('task.edit.own.state', $filter_project)) {
            $options = array_merge(
                array(JHtml::_('select.option', '',  JText::_('JOPTION_SELECT_PUBLISHED'))),
                JHtml::_('jgrid.publishedOptions')
            );

            $filters[] = JHtml::_('select.genericlist',
                $options,
                'mod_filter_published',
                null,
                'value',
                'text',
                $state->get('filter.published'),
                false,
                true
            );
        }

        // Progress filter
        $options = array_merge(
            array(JHtml::_('select.option', '',  '- ' . JText::_('PKGLOBAL_SELECT_PROGRESS') . ' -')),
            $model->getProgressOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_progress',
            null,
            'value',
            'text',
            $state->get('filter.progress')
        );

        // Author filter
        $options = array_merge(
            array(
                JHtml::_('select.option', '',      JText::_('JOPTION_SELECT_AUTHOR')),
                JHtml::_('select.option', 'me',    '* ' . JText::_('PKGLOBAL_CREATED_BY_ME') . ' *'),
                JHtml::_('select.option', 'notme', '* ' . JText::_('PKGLOBAL_NOT_CREATED_BY_ME') . ' *')
            ),
            $model->getAuthorOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_author_id',
            null,
            'value',
            'text',
            $state->get('filter.author_id')
        );

        // Access filter
        $options = array_merge(
            array(JHtml::_('select.option', '',  JText::_('JOPTION_SELECT_ACCESS'))),
            $model->getAccessOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_access',
            null,
            'value',
            'text',
            $state->get('filter.access')
        );
    }


    /**
     * Injects hidden filter fields into the frontend form
     *
     * @param     string    $context
     * @param     array     $filters
     *
     * @return    void
     */
    public function onProjectknifeDisplayHiddenFilter($context, &$filters)
    {
        if ($context != 'com_pktasks.list') {
            return;
        }

        // Get filter options from model
        $model = JModelLegacy::getInstance('List', 'PKtasksModel');
        $state = $model->getState();

        // Project filter
        $filters[] = '<input type="hidden" name="filter_project_id" id="filter_project_id" value="' . $state->get('filter.project_id')  . '"/>';

        // Milestone filter
        $filters[] = '<input type="hidden" name="filter_milestone_id" id="filter_milestone_id" value="' . $state->get('filter.milestone_id')  . '"/>';

        // Tag filter
        $filters[] = '<input type="hidden" name="filter_tag_id" id="filter_tag_id" value="' . $state->get('filter.tag_id')  . '"/>';

        // Pub state filter
        $filters[] = '<input type="hidden" name="filter_published" id="filter_published" value="' . $state->get('filter.published')  . '"/>';

        // Progress filter
        $filters[] = '<input type="hidden" name="filter_progress" id="filter_progress" value="' . $state->get('filter.progress')  . '"/>';

        // Author filter
        $filters[] = '<input type="hidden" name="filter_author_id" id="filter_author_id" value="' . $state->get('filter.author_id')  . '"/>';

        // Access filter
        $filters[] = '<input type="hidden" name="filter_access" id="filter_access" value="' . $state->get('filter.access')  . '"/>';

        // Assignee filter
        $filters[] = '<input type="hidden" name="filter_assignee_id" id="filter_assignee_id" value="' . $state->get('filter.assignee_id')  . '"/>';

        // Priority filter
        $filters[] = '<input type="hidden" name="filter_priority" id="filter_priority" value="' . $state->get('filter.priority')  . '"/>';
    }


    /**
     * Adds dasboard buttons
     *
     * @param    array      $buttons
     * @param    integer    $project_id
     *
     * @return   void
     */
    public function onProjectknifeDisplayDashboardButtons(&$buttons, $project_id = 0)
    {
        if (!PKUserHelper::authProject('task.create', $project_id)) {
            return;
        }


        $btn = new stdClass();
        $btn->title = JText::_('COM_PKTASKS_ADD_TASK');
        $btn->link  = 'index.php?option=com_pktasks&task=';
        $btn->icon  = JHtml::image('com_pktasks/dashboard_button.png', '', null, true);

        if (JFactory::getApplication()->isSite()) {
            $itemid = PKRouteHelper::getMenuItemId('com_pktasks', 'form');

            $btn->link .= "form.add";

            if ($itemid) {
                $btn->link .= '&Itemid=' . $itemid;
            }
        }
        else {
            $btn->link .= "task.add";
        }


        $buttons[] = $btn;
    }
}
