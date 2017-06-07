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


JLoader::register('PKmilestonesModelMilestone', JPATH_ADMINISTRATOR . '/components/com_pkmilestones/models/milestone.php');
JLoader::register('PKmilestonesTableMilestone', JPATH_ADMINISTRATOR . '/components/com_pkmilestones/tables/milestone.php');


class plgProjectknifeMilestones extends JPlugin
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
     * The previous milestone of a task
     *
     * @var    integer
     */
    protected $old_task_milestone;

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
     * Re-calculates the progress of the given milestones
     *
     * @param     array      $pks    The milestone ids
     *
     * @return    boolean
     */
    protected function progress($pks)
    {
        $model = JModelLegacy::getInstance('Milestone', 'PKmilestonesModel', $config = array('ignore_request' => true));

        return $model->progress($pks);
    }


    /**
     * Re-calculates the schedule of the given milestones
     *
     * @param     array      $pks    The milestone ids
     *
     * @return    boolean
     */
    protected function schedule($pks)
    {
        $model = JModelLegacy::getInstance('Milestone', 'PKmilestonesModel', $config = array('ignore_request' => true));

        return $model->schedule($pks);
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

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                $this->onContentAfterDeleteTask($context, $table);
                break;
        }

        return true;
    }


    /**
     * Deletes all milestones of the deleted project
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
              ->from('#__pk_milestones')
              ->where('project_id = ' . (int) $table->id);

        $db->setQuery($query);
        $pks = $db->loadColumn();

        if (count($pks)) {
            $model = JModelLegacy::getInstance('Milestone', 'PKmilestonesModel', $config = array('ignore_request' => true));
            $model->delete($pks);
        }
    }


    /**
     * Updates the progress and schedule of a milestone when a task was deleted
     *
     * @param     string    $context    The model context
     * @param     object    $table      The table object instance
     *
     * @return    void
     */
    protected function onContentAfterDeleteTask($context, $table)
    {
        if ($table->milestone_id) {
            $this->progress(array($table->milestone_id));
            $this->schedule(array($table->milestone_id));
        }
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
            case 'com_pktasks.form':
                $this->onProjectknifeAfterProgressTask($context, $data);
                break;
        }
    }


    /**
     * Reacts to task progress changes
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

        $query->select('milestone_id')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $pks) . ')');

        $db->setQuery($query);
        $milestones = $db->loadColumn();

        if (count($milestones)) {
            $this->progress($milestones);
        }
    }


    /**
     * Registers milestone form fields
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
            case 'com_pkmilestones.milestone':
            case 'com_pkmilestones.form':
                return $this->onContentBeforeSaveMilestone($context, $table, $is_new);
                break;

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentBeforeSaveTask($context, $table, $is_new);
                break;
        }

        return true;
    }


    /**
     * Checks if the milestone access level has changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentBeforeSaveMilestone($context, $table, $is_new)
    {
        if (!$is_new) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('access')
                  ->from('#__pk_milestones')
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
     * Checks if the task progress or state has been changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentBeforeSaveTask($context, $table, $is_new)
    {
        // Save progress and milestone id for later
        if (!$is_new) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('milestone_id, progress, published')
                  ->from('#__pk_tasks')
                  ->where('id = ' . (int) $table->id);

            $db->setQuery($query);
            $result = $db->loadObject();

            $this->old_task_milestone = (int) $result->milestone_id;
            $this->old_task_progress  = (int) $result->progress;
            $this->old_task_published = (int) $result->published;
        }
        else {
            $this->old_task_progress  = 0;
            $this->old_task_milestone = 0;
            $this->old_task_published = 1;
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
            case 'com_pkmilestones.milestone':
            case 'com_pkmilestones.form':
                return $this->onContentAfterSaveMilestone($context, $table, $is_new);
                break;

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentAfterSaveTask($context, $table, $is_new);
                break;
        }

        return true;
    }


    /**
     * Handles access inheritance
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentAfterSaveMilestone($context, $table, $is_new)
    {
        if ($this->trigger_change_access && $this->old_access !== (int) $table->access) {
            // Trigger onProjectknifeAfterChangeAccess event
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
     * Updates milestone progress and schedule when a task is changed
     *
     * @param     string     $context    The model context
     * @param     object     $table      The table object instance
     * @param     boolean    $is_new     Indicates whether the record is new or not
     *
     * @return    boolean
     */
    protected function onContentAfterSaveTask($context, $table, $is_new)
    {
        // Update milestone progress
        if ($is_new) {
            if ((int) $table->milestone_id > 0 && $table->published > 0) {
                $this->progress(array((int) $table->milestone_id));
            }
        }
        else {
            if ((int) $table->milestone_id != (int) $this->old_task_milestone) {
                $milestones = array((int)$this->old_task_milestone, (int) $table->milestone_id);
            }
            else {
                $milestones = array((int) $table->milestone_id);
            }

            // Update progress if task progress, pub state or milestone changed.
            if ($table->progress != $this->old_task_progress || $table->published != $this->old_task_published || count($milestones) == 2) {
                $this->progress($milestones);
            }
        }

        // Update actual schedule
        $milestones = array();

        if ($table->milestone_id > 0) {
            $milestones[] = $table->milestone_id;
        }

        if ($this->old_task_milestone > 0 && $table->milestone_id != $this->old_task_milestone) {
            $milestones[] = $this->old_task_milestone;
        }

        if (count($milestones)) {
            $this->schedule($milestones);
        }

        $this->old_task_milestone = 0;
        $this->old_task_progress  = 0;
        $this->old_task_published = 1;

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

            case 'com_pktasks.task':
            case 'com_pktasks.form':
                return $this->onContentChangeStateTask($context, $pks, $state);
                break;
        }

        return true;
    }


    /**
     * Updates the milestone state when the parent project state has changed
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

        $query->update('#__pk_milestones')
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
     * Updates the milestone progress when a task state is changed
     *
     * @param     string     $context
     * @param     array      $pks
     * @param     integer    $state
     *
     * @return    boolean
     */
    protected function onContentChangeStateTask($context, $pks, $state)
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
            return true;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('milestone_id')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $pks) . ')')
              ->group('milestone_id');

        $db->setQuery($query);
        $milestones = $db->loadColumn();

        // Update progress
        if (count($milestones)) {
            $this->progress($milestones);
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

        // Get all milestones that inherit their access from the given projects.
        $query->select('id')
              ->from('#__pk_milestones');

        if ($count === 1) {
            $query->where('project_id = ' . (int) $pks[0]);
        }
        else {
            $query->where('project_id IN(' . implode(', ', $pks) . ')');
        }

        $query->where('access != ' . $access)
              ->where('access_inherit = 1');

        $db->setQuery($query);

        $milestones = $db->loadColumn();
        $count = count($milestones);

        if ($count === 0) return true;


        // Update affected milestones
        $query->clear()
              ->update('#__pk_milestones')
              ->set('access = ' . $access);

        if ($count === 1) {
            $query->where('id = ' . $milestones[0]);
        }
        else {
            $query->where('id IN(' . implode(', ', $milestones) . ')');
        }

        $db->setQuery($query);
        $db->execute();


        // Trigger the event again
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onProjectknifeAfterChangeAccess', array('com_pkmilestones.milestone', $milestones, $access));

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
    public function onProjectknifeAfterCopy($context, $pks, $options)
    {
        switch ($context)
        {
            case 'com_pkprojects.projects':
            case 'com_pkprojects.list':
                return $this->onProjectknifeAfterCopyProject($context, $pks, $options);
                break;

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
    protected function onProjectknifeAfterCopyProject($context, $pks, $options)
    {
        // Check if included
        if (!array_key_exists('include', $options)) {
            return true;
        }
        elseif (!is_array($options['include'])) {
            return true;
        }
        elseif (!array_key_exists('com_pkmilestones.milestones', $options['include'])) {
            return true;
        }
        elseif ($options['include']['com_pkmilestones.milestones'] == 0) {
            return true;
        }

        // Register classes
        $base_path = JPATH_ADMINISTRATOR . '/components/com_pkmilestones';
        JLoader::register('PKmilestonesModelMilestone', $base_path . '/models/milestone.php');
        JLoader::register('PKmilestonesTableMilestone', $base_path . '/tables/milestone.php');

        $model = PKmilestonesModelMilestone::getInstance('Milestone', 'PKmilestonesModel');
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Copy milestones to new projects
        foreach ($pks AS $old_id => $new_id)
        {
            $query->clear()
                  ->select('id')
                  ->from('#__pk_milestones')
                  ->where('project_id = ' . (int) $old_id);

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

        $query->select('milestone_id')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $pks) . ')')
              ->group('milestone_id');

        $db->setQuery($query);
        $milestones = $db->loadColumn();

        // Update progress
        if (count($milestones)) {
            $this->progress($milestones);
        }

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
        if ($context == 'com_pkprojects.projects' || $context == 'com_pkprojects.list') {
            $opt        = new stdClass();
            $opt->value = 'com_pkmilestones.milestones';
            $opt->text  = JText::_('COM_PKMILESTONES_SUBMENU_MILESTONES');

            return array($opt);
        }
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
        if ($location != 'site' || $context != 'com_pkmilestones.list') {
            return;
        }


        // Get filter options from model
        $model = JModelLegacy::getInstance('List', 'PKMilestonesModel');
        $state = $model->getState();
        $pid   = (int) $state->get('filter.project_id');

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
        if (PKUserHelper::authProject('milestone.edit.state', $pid) || PKUserHelper::authProject('milestone.edit.own.state', $pid)) {
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
        if ($context != 'com_pkmilestones.list') {
            return;
        }

        // Get filter options from model
        $model = JModelLegacy::getInstance('List', 'PKmilestonesModel');
        $state = $model->getState();

        // Category filter
        $filters[] = '<input type="hidden" name="filter_project_id" id="filter_project_id" value="' . $state->get('filter.project_id')  . '"/>';

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
        if (!PKUserHelper::authProject('milestone.create', $project_id)) {
            return;
        }


        $btn = new stdClass();
        $btn->title = JText::_('COM_PKMILESTONES_ADD_MILESTONE');
        $btn->link  = 'index.php?option=com_pkmilestones&task=';
        $btn->icon  = JHtml::image('com_pkmilestones/dashboard_button.png', 'yes', null, true);

        if (JFactory::getApplication()->isSite()) {
            $itemid = PKRouteHelper::getMenuItemId('com_pkmilestones', 'form');

            $btn->link .= "form.add";

            if ($itemid) {
                $btn->link .= '&Itemid=' . $itemid;
            }
        }
        else {
            $btn->link .= "milestone.add";
        }


        $buttons[] = $btn;
    }
}
