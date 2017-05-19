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


JLoader::register('PKtasksControllerTasks', JPATH_ADMINISTRATOR . '/components/com_pktasks/controllers/tasks.php');


class PKtasksControllerList extends PKtasksControllerTasks
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
    public function getModel($name = 'Form', $prefix = 'PKtasksModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }
}