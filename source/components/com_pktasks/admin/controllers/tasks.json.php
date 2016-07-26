<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();



class PKtasksControllerTasks extends JControllerLegacy
{
    protected $text_prefix = 'COM_PKTASKS';


    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    The array of possible config values. Optional.
     *
     * @return    jmodel
     */
    public function getModel($name = 'Task', $prefix = 'PKtasksModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }


    public function getMilestoneOptions()
    {
        $project_id = JFactory::getApplication()->input->getUInt('project_id', 0, 'integer');
        $model      = $this->getModel('Tasks');

        if ($project_id <= 0) {
            $items = array();
        }
        else {
            $filters = array('project_id' => $project_id, 'task_id' => '');
            $items   = $model->getMilestoneOptions($filters);
        }

        echo new JResponseJson($items);
    }


    public function progress()
    {
        $app   = JFactory::getApplication();
        $input = $app->input;
        $pks   = $input->get('cid', array(), 'array');
        $prog  = $input->get('progress', 0, 'int');

        // Make sure the item ids are integers
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        $k = array_search(0, $pks, true);

        while ($k !== false)
        {
            unset($pks[$k]);
            $k = array_search(0, $pks, true);
        }

        if (empty($pks)) {
            $app->enqueueMessage(JText::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            echo new JResponseJson(null, '', true);
        }

        // Check access
        if (!PKUserHelper::isSuperAdmin()) {
            $db     = JFactory::getDbo();
            $query  = $db->getQuery(true);

            $query->select('id, access, project_id')
                  ->from('#__pk_tasks')
                  ->where('id IN(' . implode(', ', $pks) . ')');

            $db->setQuery($query);
            $data = $db->loadAssocList('id');

            $levels   = PKUserHelper::getAccesslevels();
            $projects = PKUserHelper::getProjects();

            $count = count($pks);
            $id    = 0;

            // Remove inaccessible tasks
            for ($i = 0; $i != $count; $i++)
            {
                $id = $pks[$i];

                if (!isset($data[$id])
                    || (!in_array($data[$id]['access'], $levels) && !in_array($data[$id]['project'], $projects))
                    || !PKUserHelper::authProject('core.edit.state.task', $data[$id]['project'])
                ) {
                    unset($pks[$i]);
                }
            }
        }


        if (empty($pks)) {
            $app->enqueueMessage(JText::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            echo new JResponseJson(null, '', true);
        }

        $model = $this->getModel();

        // Update the progress
        try {
            $model->progress($pks, $prog);
            echo new JResponseJson(null, JText::plural($this->text_prefix . '_N_ITEMS_COPIED', count($pks)));
        }
        catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            echo new JResponseJson(null, '', true);
        }
    }


    public function priority()
    {
        $app   = JFactory::getApplication();
        $input = $app->input;
        $pks   = $input->get('cid', array(), 'array');
        $prio  = $input->get('priority', 0, 'int');

        // Make sure the item ids are integers
        JArrayHelper::toInteger($pks);

        // Remove any values of zero.
        $k = array_search(0, $pks, true);

        while ($k !== false)
        {
            unset($pks[$k]);
            $k = array_search(0, $pks, true);
        }

        if (empty($pks)) {
            $app->enqueueMessage(JText::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            echo new JResponseJson(null, '', true);
        }

        // Check access
        if (!PKUserHelper::isSuperAdmin()) {
            $db     = JFactory::getDbo();
            $query  = $db->getQuery(true);

            $query->select('id, access, project_id')
                  ->from('#__pk_tasks')
                  ->where('id IN(' . implode(', ', $pks) . ')');

            $db->setQuery($query);
            $data = $db->loadAssocList('id');

            $levels   = PKUserHelper::getAccesslevels();
            $projects = PKUserHelper::getProjects();

            $count = count($pks);
            $id    = 0;

            // Remove inaccessible tasks
            for ($i = 0; $i != $count; $i++)
            {
                $id = $pks[$i];

                if (!isset($data[$id])
                    || (!in_array($data[$id]['access'], $levels) && !in_array($data[$id]['project'], $projects))
                    || !PKUserHelper::authProject('core.edit.state.task', $data[$id]['project'])
                ) {
                    unset($pks[$i]);
                }
            }
        }


        if (empty($pks)) {
            $app->enqueueMessage(JText::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            echo new JResponseJson(null, '', true);
        }

        $model = $this->getModel();

        // Update the progress
        try {
            $model->priority($pks, $prio);
            echo new JResponseJson(null, JText::plural($this->text_prefix . '_N_ITEMS_COPIED', count($pks)));
        }
        catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            echo new JResponseJson(null, '', true);
        }
    }
}