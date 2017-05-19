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


JLoader::register('PKTasksControllerTasks', JPATH_ADMINISTRATOR . '/components/com_pktasks/controllers/tasks.json.php');


class PKTasksControllerList extends PKTasksControllerTasks
{
    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    The array of possible config values. Optional.
     *
     * @return    jmodel
     */
    public function getModel($name = 'Form', $prefix = 'PKTasksModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }


    public function getMilestoneOptions()
    {
        $project_id = JFactory::getApplication()->input->getUInt('project_id', 0, 'integer');
        $model      = $this->getModel('List');

        if ($project_id <= 0) {
            $items = array();
        }
        else {
            $filters = array('project_id' => $project_id, 'task_id' => '');
            $items   = $model->getMilestoneOptions($filters);
        }

        echo new JResponseJson($items);
    }
}