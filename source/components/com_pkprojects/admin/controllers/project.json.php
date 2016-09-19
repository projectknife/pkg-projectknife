<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();



class PKprojectsControllerProject extends JControllerLegacy
{
    protected $text_prefix = 'COM_PKPROJECTS';


    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    The array of possible config values. Optional.
     *
     * @return    jmodel
     */
    public function getModel($name = 'Project', $prefix = 'PKprojectsModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }


    public function searchMember()
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

        // Get project access
        $query->select('access')
              ->from('#__pk_projects')
              ->where('id = ' . $project_id);

        $db->setQuery($query);
        $access = $db->loadResult();

        // Get user groups assigned to this level
        $query->clear()
              ->select('rules')
              ->from('#__viewlevels')
              ->where('id = ' . $access);

        $db->setQuery($query);
        $rules = $db->loadResult();

        if (empty($rules)) {
            $groups = array();
        }
        else {
            $groups = json_decode($rules);
        }

        // Get manually added users
        $query->clear()
              ->select('user_id')
              ->from('#__pk_project_users')
              ->where('project_id = ' . $project_id);

        $db->setQuery($query);
        $users = $db->loadColumn();

        $user_count  = count($users);
        $group_count = count($groups);

        // Check access to the project
        if (!PKUserHelper::isSuperAdmin()) {
            $levels   = PKUserHelper::getAccessLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($access, $levels) && !in_array($project_id, $projects)) {
                // Access denied. Fail silently.
                echo json_encode(array());
                $app->close();
            }
        }

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

        // Search query
        $query->clear()
              ->select('a.id AS value, a.' . $display_name_field . ' AS text')
              ->from('#__users AS a')
              ->join('inner', '#__user_usergroup_map AS m ON m.user_id = a.id')
              ->where('a.' . $display_name_field . ' LIKE ' . $db->quote('%' . $like . '%'))
              ->group('a.id, a.' . $display_name_field);

        // Filter on member groups and users
        if ($group_count || $user_count) {
            if ($group_count && $user_count) {
                $query->where('(m.group_id IN(' . implode(', ', $groups) . ') OR a.id IN(' . implode(', ', $users) . '))');
            }
            elseif ($group_count) {
                $query->where('m.group_id IN(' . implode(', ', $groups) . ')');
            }
            else {
                $query->where('a.id IN(' . implode(', ', $users) . ')');
            }
        }

        $db->setQuery($query);
        $items = $db->loadObjectList();

        echo json_encode($items);
        JFactory::getApplication()->close();
    }


    /**
     * Returns the start and due date of a project
     *
     */
    public function getSchedule()
    {
        $app   = JFactory::getApplication();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
        $input = $app->input;
        $id    = $input->get('id', 0, 'integer');

        // Get access
        $query->select('access')
              ->from('#__pk_projects')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $access = $db->loadResult();

        // Check access
        if (!PKUserHelper::isSuperAdmin()) {
            $levels   = PKUserHelper::getAccessLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($access, $levels) && !in_array($project_id, $projects)) {
                // Access denied.
                $app->enqueueMessage(JText::_('COM_PKPROJECTS_PROJECT_VIEW_ACCESS_DENIED'), 'error');
                echo new JResponseJson(null, '', true);
            }
        }

        // Get the start and due date
        $query->clear()
              ->select('start_date, due_date')
              ->from('#__pk_projects')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $result = $db->loadObject();

        // Check if empty
        if (empty($result)) {
            $app->enqueueMessage(JText::_('COM_PKPROJECTS_PROJECT_NOT_FOUND'), 'error');
            echo new JResponseJson(null, '', true);
        }

        $config = JFactory::getConfig();
        $user   = JFactory::getUser();

        // Create time zone
        $tz = new DateTimeZone($user->getParam('timezone', $config->get('offset')));

        // Set start date
        $date = JFactory::getDate($result->start_date, 'UTC');
        $date->setTimeZone($tz);

        $result->start_date = $date->format(JText::_('DATE_FORMAT_LC4'), true);

        // Set due date
        $date = JFactory::getDate($result->due_date, 'UTC');
        $date->setTimeZone($tz);

        $result->due_date = $date->format(JText::_('DATE_FORMAT_LC4'), true);


        echo new JResponseJson($result);
    }
}
