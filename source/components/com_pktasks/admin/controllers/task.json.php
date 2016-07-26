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



class PKtasksControllerTask extends JControllerLegacy
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


    public function searchUser()
    {
        $app   = JFactory::getApplication();
        $input = $app->input;

        $project_id = $input->get('project_id', 0, 'integer');

        if (!$project_id) {
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


        $obj = new stdClass();
        $obj->value = $project_id;
        $obj->text = 'Test';

        echo json_encode(array($obj));
        JFactory::getApplication()->close();
    }
}