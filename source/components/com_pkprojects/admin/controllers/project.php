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


class PKprojectsControllerProject extends JControllerForm
{
    /**
     * Function that allows child controller access to model data
     * after the data has been saved.
     *
     * @param     jmodellegacy    $model    The data model object.
     * @param     array           $data     The validated data.
     *
     * @return    void
     */
    protected function postSaveHook(JModelLegacy $model, $data = array())
    {
        // Set the project as active
        $new_id = (int) $model->getState($this->context. '.id');

        PKApplicationHelper::setProjectId($new_id);
    }


    /**
     * Method to get a model object, loading it if required.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    Configuration array for model. Optional.
     *
     * @return    object               The model.
     */
    public function getModel($name = 'Project', $prefix = 'PKprojectsModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }
}
