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



class PKmilestonesControllerMilestone extends JControllerLegacy
{
    protected $text_prefix = 'COM_PKMILESTONES';


    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    The array of possible config values. Optional.
     *
     * @return    jmodel
     */
    public function getModel($name = 'Milestone', $prefix = 'PKmilestonesModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }


    /**
     * Returns the start and due date of a milestone
     *
     */
    public function getSchedule()
    {
        $app   = JFactory::getApplication();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
        $id    = $app->input->get('id', 0, 'integer');

        // Load milestone
        $query->select('a.id, a.project_id, a.access, a.start_date, a.due_date')
              ->from('#__pk_milestones AS a')
              ->leftJoin('#__pk_projects AS p ON p.id = a.project_id')
              ->where('a.id = ' . $id);

        $db->setQuery($query);
        $milestone = $db->loadObject();

        if (empty($milestone)) {
            $app->enqueueMessage(JText::_('COM_PKMILESTONES_MILESTONE_NOT_FOUND'), 'error');
            echo new JResponseJson(null, '', true);
        }

        // Check access
        if (!PKUserHelper::isSuperAdmin()) {
            $levels   = PKUserHelper::getAccessLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($milestone->access, $levels) && !in_array($milestone->project_id, $projects)) {
                $app->enqueueMessage(JText::_('COM_PKMILESTONES_MILESTONE_VIEW_ACCESS_DENIED'), 'error');
                echo new JResponseJson(null, '', true);
            }
        }

        $config = JFactory::getConfig();
        $user   = JFactory::getUser();
        $result = new stdClass();

        // Create time zone
        $tz = new DateTimeZone($user->getParam('timezone', $config->get('offset')));

        // Set start date
        $date = JFactory::getDate($milestone->start_date, 'UTC');
        $date->setTimeZone($tz);
        $result->start_date = $date->format(JText::_('DATE_FORMAT_LC4'), true);

        // Set due date
        $date = JFactory::getDate($milestone->due_date, 'UTC');
        $date->setTimeZone($tz);
        $result->due_date = $date->format(JText::_('DATE_FORMAT_LC4'), true);

        echo new JResponseJson($result);
    }
}
