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



class PKTasksControllerTask extends JControllerLegacy
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


    /**
     * Prints out a json string of tasks.
     *
     */
    public function searchDependency()
    {
        $app   = JFactory::getApplication();
        $input = $app->input;

        $project_id = $input->get('project_id', 0, 'integer');
        $like       = trim($input->get('like', ''));

        if (!$project_id || empty($like)) {
            echo json_encode(array());
            $app->close();
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Check access to the project
        if (!PKUserHelper::isSuperAdmin()) {
            // Get project access
            $query->select('access')
                  ->from('#__pk_projects')
                  ->where('id = ' . $project_id);

            $db->setQuery($query);
            $access = $db->loadResult();

            $levels   = PKUserHelper::getAccessLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($access, $levels) && !in_array($project_id, $projects)) {
                // Access denied. Fail silently.
                echo json_encode(array());
                $app->close();
            }
        }

        // Search query
        $query->clear()
              ->select('a.id AS value, a.title AS text')
              ->from('#__pk_tasks AS a')
              ->where('a.project_id = ' . $project_id)
              ->where('a.title LIKE ' . $db->quote('%' . $like . '%'))
              ->group('a.id, a.title');

        $db->setQuery($query);
        $items = $db->loadObjectList();

        echo json_encode($items);
        JFactory::getApplication()->close();
    }
}