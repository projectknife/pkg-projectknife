<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_projects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();


JLoader::register('JFormRulePKUserRules', JPATH_SITE . '/plugins/projectknife/projects/rules/pkuserrules.php');
JLoader::register('PKprojectsModelProject', JPATH_ADMINISTRATOR . '/components/com_pkprojects/models/project.php');


class plgProjectknifeProjects extends JPlugin
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
     * List of selected project members before saving
     *
     * @var    array
     */
    protected $new_members;

    /**
     * The previous project of a task
     *
     * @var    integer
     */
    protected $old_task_project;

    /**
     * The previous progress of a task
     *
     * @var    integer
     */
    protected $old_task_progress;

    /**
     * The previous pub state of a task
     *
     * @var    integer
     */
    protected $old_task_published;


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
     * Re-calculates the progress of the given projects
     *
     * @param     array      $pks    The project ids
     *
     * @return    boolean
     */
    protected function progress($pks)
    {
        $model = JModelLegacy::getInstance('Project', 'PKprojectsModel', $config = array('ignore_request' => true));

        return $model->progress($pks);
    }


    /**
     * Sets the actual schedule of the given projects
     *
     * @param     array      $pks    The project ids
     *
     * @return    boolean
     */
    protected function schedule($pks)
    {
        $model = JModelLegacy::getInstance('Project', 'PKprojectsModel', $config = array('ignore_request' => true));

        return $model->schedule($pks);
    }


    /**
     * Returns a json encoded list of users. Used by the pkuserrules field.
     *
     * @return    string    The json encoded list of users.
     */
    public function onAjaxSearchProjectUser()
    {
        $input = JFactory::getApplication()->input;
        $like  = trim($input->get('like'));

        if (empty($like)) {
            // Search term must not be empty!
            return json_encode(array());
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id AS value, a.username AS text')
              ->from('#__users AS a')
              ->where('(a.username LIKE ' . $db->quote('%' . $like . '%') . ' OR a.name LIKE ' . $db->quote('%' . $like . '%') . ')')
              ->order('a.username ASC');

        $db->setQuery($query);
        $items = $db->loadObjectList();

        return json_encode($items);
    }


    /**
     * Method to prepare the form and form data
     *
     * @param     object    $form    The form object instance
     * @param     array     $data    The form data
     *
     * @return    bool               True on success, False on error.
     */
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm)) {
            return true;
        }

        // Load the plugin language
        $this->loadLanguage();

        // Register custom form fields
        $form->addFieldPath(__DIR__ . '/fields');

        return true;
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
                $this->onContentAfterDeleteProject($context, $table);
                break;

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                $this->onContentAfterDeleteTask($context, $table);
                break;
        }

        return true;
    }


    /**
     * Resets the active project if it was deleted
     *
     * @param     string    $context    The model context
     * @param     object    $table      The table object instance
     *
     * @return    void
     */
    protected function onContentAfterDeleteProject($context, $table)
    {
        if ($table->id == PKApplicationHelper::getProjectId()) {
            PKApplicationHelper::setProjectId(0);
        }
    }


    /**
     * Updates the progress and schedule of a project when a task was deleted
     *
     * @param     string    $context    The model context
     * @param     object    $table      The table object instance
     *
     * @return    void
     */
    protected function onContentAfterDeleteTask($context, $table)
    {
        $this->progress(array($table->project_id));
        $this->schedule(array($table->project_id));
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
            case 'com_pkprojects.project':
            case 'com_pkprojects.form':
                return $this->onContentBeforeSaveProject($context, $table, $is_new);
                break;

            case 'com_categories.category':
                return $this->onContentBeforeSaveCategory($context, $table, $is_new);
                break;

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentBeforeSaveTask($context, $table, $is_new);
                break;
        }

        return true;
    }


    /**
     * Checks if the project access level has changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentBeforeSaveProject($context, $table, $is_new)
    {
        if ($is_new) {
            return true;
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('access')
              ->from('#__pk_projects')
              ->where('id = ' . (int) $table->id);

        $db->setQuery($query);
        $this->old_access = (int) $db->loadResult();

        if ($this->old_access) {
            $this->trigger_change_access = true;
        }

        return true;
    }


    /**
     * Checks if the category access level has changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentBeforeSaveCategory($context, $table, $is_new)
    {
        if ($is_new || $table->extension != 'com_pkprojects') {
            // Do nothing if new or not a project category.
            return true;
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('access')
              ->from('#__categories')
              ->where('id = ' . (int) $table->id);

        $db->setQuery($query);
        $this->old_access = (int) $db->loadResult();

        if ($this->old_access) {
            $this->trigger_change_access = true;
        }

        return true;
    }


    /**
     * Checks if the task has changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentBeforeSaveTask($context, $table, $is_new)
    {
        // Save progress and project id for later
        if (!$is_new) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('project_id, progress, published')
                  ->from('#__pk_tasks')
                  ->where('id = ' . (int) $table->id);

            $db->setQuery($query);
            $result = $db->loadObject();

            $this->old_task_project   = (int) $result->project_id;
            $this->old_task_progress  = (int) $result->progress;
            $this->old_task_published = (int) $result->published;
        }
        else {
            $this->old_task_progress  = 0;
            $this->old_task_project   = 0;
            $this->old_task_published = 1;
        }
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
    public function onContentAfterSave($context, $table, $is_new)
    {
        switch ($context)
        {
            case 'com_pkprojects.project':
            case 'com_pkprojects.form':
                return $this->onContentAfterSaveProject($context, $table, $is_new);
                break;

            case 'com_categories.category':
                return $this->onContentAfterSaveCategory($context, $table, $is_new);
                break;

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentAfterSaveTask($context, $table, $is_new);
                break;
        }

        return true;
    }


    /**
     * Handles project changes
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentAfterSaveProject($context, $table, $is_new)
    {
        // Update project member list
        if (is_array($this->new_members)) {
            $db         = JFactory::getDbo();
            $query      = $db->getQuery(true);
            $form_users = $this->new_members;

            JArrayHelper::toInteger($form_users);

            // Remove any values of zero.
            $k = array_search(0, $form_users, true);

            while ($k !== false)
            {
                unset($form_users[$k]);
                $k = array_search(0, $form_users, true);
            }

            // Get the old list of users
            if ($is_new) {
                $old_users = array();
            }
            else {
                $query->select('user_id')
                      ->from('#__pk_project_users')
                      ->where('project_id = ' . (int) $table->id);

                $db->setQuery($query);
                $old_users = $db->loadColumn();
            }

            $add    = array();
            $delete = array();

            // Determine which users were removed
            foreach ($old_users AS $uid)
            {
                if (!in_array($uid, $form_users)) {
                    $delete[] = $uid;
                }
            }

            // Determine which users were added
            foreach ($form_users AS $uid)
            {
                if (!in_array($uid, $old_users)) {
                    $add[] = $uid;
                }
            }

            // Delete users
            if (count($delete)) {
                $query->clear()
                      ->delete('#__pk_project_users')
                      ->where('project_id = ' . (int) $table->id)
                      ->where('user_id IN(' . implode(', ', $delete) . ')');

                $db->setQuery($query);
                $db->execute();
            }

            // Add users
            if (count($add)) {
                foreach ($add AS $uid)
                {
                    $query->clear()
                          ->insert('#__pk_project_users')
                          ->values((int) $table->id . ', ' . (int) $uid);

                    $db->setQuery($query);
                    $db->execute();
                }
            }
        }


        // Handle access level change
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
     * Handles project category changes
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentAfterSaveCategory($context, $table, $is_new)
    {
        // Handle access level change
        if ($this->trigger_change_access && $this->old_access !== (int) $table->access && $table->extension == 'com_pkprojects') {
            // Trigger onContentAfterChangeAccess event
            $dispatcher = JDispatcher::getInstance();
            $pks        = array((int) $table->id);
            $access     = (int) $table->access;

            $dispatcher->trigger('onProjectknifeAfterChangeAccess', array('com_pkprojects.category', $pks, $access));

            // Reset
            $this->trigger_change_access = false;
            $this->old_access = 0;
        }

        return true;
    }


    /**
     * Handles task changes
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentAfterSaveTask($context, $table, $is_new)
    {
        // Update progress
        if ($is_new) {
            if ((int) $table->project_id > 0 && $table->published > 0) {
                $this->progress(array((int) $table->project_id));
            }
        }
        else {
            if ((int) $table->project_id != (int) $this->old_task_project) {
                $projects = array((int)$this->old_task_project, (int) $table->project_id);
            }
            else {
                $projects = array((int) $table->project_id);
            }

            // Update progress if task progress, pub state or project changed.
            if ($table->progress != $this->old_task_progress || $table->published != $this->old_task_published || count($projects) == 2) {
                $this->progress($projects);
            }
        }

        // Update actual schedule
        $projects = array();

        if ($table->project_id > 0) {
            $projects[] = $table->project_id;
        }

        if ($this->old_task_project > 0 && $table->project_id != $this->old_task_project) {
            $projects[] = $this->old_task_milestone;
        }

        if (count($projects)) {
            $this->schedule($projects);
        }

        $this->old_task_project   = 0;
        $this->old_task_progress  = 0;
        $this->old_task_published = 1;

        return true;
    }


    /**
     * "onContentChangeState" event handler
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The item id's
     * @param     array      $value      The new publishing state
     *
     * @return    boolean
     */
    public function onContentChangeState($context, $pks, $value)
    {
        switch ($context)
        {
            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentChangeStateTask($context, $pks, $value);
                break;
        }

        return true;
    }


    /**
     * Updates task progress and schedule when a task state changes
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The item id's
     * @param     array      $value      The new publishing state
     *
     * @return    boolean
     */
    protected function onContentChangeStateTask($context, $pks, $value)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('project_id')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $pks) . ')');

        $db->setQuery($query);
        $projects = $db->loadColumn();

        if (count($projects)) {
            $this->progress($projects);
            $this->schedule($projects);
        }

        return true;
    }


    /**
     * "onProjectknifeAfterProgress" event handler
     *
     * @param     string    $context
     * @param     array     $data
     *
     * @return    void
     */
    public function onProjectknifeAfterProgress($context, $data)
    {
        switch ($context)
        {
            case 'com_pktasks.tasks':
            case 'com_pktasks.list':
                $this->onProjectknifeAfterProgressTask($context, $data);
                break;
        }
    }


    /**
     * Handles task progress changes
     *
     * @param     string    $context
     * @param     array     $data
     *
     * @return    void
     */
    protected function onProjectknifeAfterProgressTask($context, $data)
    {
        $pks   = array_keys($data);
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('project_id')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $pks) . ')');

        $db->setQuery($query);
        $projects = $db->loadColumn();

        if (count($projects)) {
            $this->progress($projects);
        }
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
            case 'com_pkprojects.category':
                return $this->onProjectknifeAfterChangeAccess($context, $pks, $access);
                break;
        }

        return true;
    }


    /**
     * Handles category access level inheritance
     *
     * @param     string     $context    The model context
     * @param     array      $pks        The affected item id's
     * @param     integer    $access     The new access level
     *
     * @return    boolean
     */
    protected function onProjectknifeAfterChangeAccessCategory($context, $pks, $access)
    {
        $access = (int) $access;
        $count  = count($pks);

        if ($count === 0) return true;

        JArrayHelper::toInteger($pks);

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Get all projects that inherit their access from the given categories.
        $query->select('id')
              ->from('#__pk_projects');

        if ($count === 1) {
            $query->where('catid = ' . (int) $pks[0]);
        }
        else {
            $query->where('catid IN(' . implode(', ', $pks) . ')');
        }

        $query->where('access != ' . $access)
              ->where('access_inherit = 1');

        $db->setQuery($query);

        $projects = $db->loadColumn();
        $count = count($projects);

        if ($count === 0) return true;


        // Update affected projects
        $query->clear()
              ->update('#__pk_projects')
              ->set('access = ' . $access);

        if ($count === 1) {
            $query->where('id = ' . $projects[0]);
        }
        else {
            $query->where('id IN(' . implode(', ', $projects) . ')');
        }

        $db->setQuery($query);
        $db->execute();


        // Trigger the event again
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onProjectknifeAfterChangeAccess', array('com_pkprojects.project', $projects, $access));

        return true;
    }


    /**
     * "onProjectknifePrepareSaveData" event handler
     *
     * @param     string    $context    The model context
     * @param     array     $data       The data to save
     * @param     bool      $is_new     Indicated whether this is a new item or not
     *
     * @return    void
     */
    public function onProjectknifePrepareSaveData($context, &$data, $is_new)
    {
        switch ($context)
        {
            case 'com_pkprojects.project':
            case 'com_pkprojects.form':
                $this->onProjectknifePrepareSaveDataProject($context, $data, $is_new);
                break;
        }
    }


    /**
     * Merges project user permissions into group permissions
     *
     * @param     string    $context    The model context
     * @param     array     $data       The data to save
     * @param     bool      $is_new     Indicated whether this is a new item or not
     *
     * @return    void
     */
    protected function onProjectknifePrepareSaveDataProject($context, &$data, $is_new)
    {
        if (!isset($data['userrules'])) {
            return;
        }

        if (!is_array($data['userrules'])) {
            $data['userrules'] = array();
        }

        if (!isset($data['rules'])) {
            if (!$is_new) {
                // Load existing rules
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);

                $query->select('asset_id')
                      ->from('#__pk_projects')
                      ->where('id = ' . (int) $data['id']);

                $db->setQuery($query);
                $asset_id = (int) $db->loadResult();

                $query->clear()
                      ->select('rules')
                      ->from('#__assets')
                      ->where('id = ' . $asset_id);

                $db->setQuery($query);
                $data['rules'] = json_decode($db->loadResult());
            }
            else {
                $data['rules'] = array();
            }
        }

        foreach ($data['userrules'] AS $rule => $users)
        {
            if (!isset($data['rules'][$rule])) {
                $data['rules'][$rule] = array();
            }

            if ($rule == 'core.user') {
                // Save member list for later
                $this->new_members = array_keys($users);
                continue;
            }

            foreach ($users AS $uid => $access)
            {
                if (!isset($data['rules'][$rule][$uid])) {
                    $data['rules'][$rule][$uid] = $access;
                }
            }
        }
    }


    /**
     * "onProjectknifeAfterCopy" event handler
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
            case 'com_pktasks.tasks':
            case 'com_pktasks.list':
                return $this->onProjectknifeAfterCopyTask($context, $pks, $options);
                break;
        }

        return true;
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
    protected function onProjectknifeAfterCopyTask($context, $pks, $options)
    {
        $pks   = array_values($pks);
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('project_id')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $pks) . ')')
              ->group('project_id');

        $db->setQuery($query);
        $projects = $db->loadColumn();

        // Update progress
        if (count($projects)) {
            $this->progress($projects);
            $this->schedule($projects);
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
        if ($location != 'site' || $context != 'com_pkprojects.list') {
            return;
        }


        // Get filter options from model
        $model = JModelLegacy::getInstance('List', 'PKprojectsModel');
        $state = $model->getState();

        // Category filter
        $options = array_merge(
            array(
                JHtml::_('select.option', '',  JText::_('JOPTION_SELECT_CATEGORY')),
                JHtml::_('select.option', '0', JText::_('PKGLOBAL_UNCATEGORISED')),
            ),
            $model->getCategoryOptions()
        );

        $filters[] = JHtml::_('select.genericlist',
            $options,
            'mod_filter_category_id',
            null,
            'value',
            'text',
            $state->get('filter.category_id')
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

        // Publishing state filter
        if (PKUserHelper::authProject('core.edit.state') || PKUserHelper::authProject('core.edit.own.state')) {
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
        if ($context != 'com_pkprojects.list') {
            return;
        }

        // Get filter options from model
        $model = JModelLegacy::getInstance('List', 'PKprojectsModel');
        $state = $model->getState();

        // Category filter
        $filters[] = '<input type="hidden" name="filter_category_id" id="filter_category_id" value="' . $state->get('filter.category_id')  . '"/>';

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
    }
}
