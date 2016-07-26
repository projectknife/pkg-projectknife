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


class PKtasksControllerTask extends JControllerForm
{
    /**
     * Method to get a model object, loading it if required.
     *
     * @param     string    $name      The model name. Optional.
     * @param     string    $prefix    The class prefix. Optional.
     * @param     array     $config    Configuration array for model. Optional.
     *
     * @return    object               The model.
     */
    public function getModel($name = 'Task', $prefix = 'PKtasksModel', $config = array('ignore_request' => true))
    {
        if (empty($name)) {
            $name = $this->context;
        }

        return parent::getModel($name, $prefix, $config);
    }


    /**
     * Method to run batch operations.
     *
     * @param     object     $model    The model.
     *
     * @return    boolean              True if successful, false otherwise and internal error is set.
     */
    public function batch($model = null)
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Set the model
        $model = $this->getModel('Task', 'PKtasksModel', array());

        // Preset the redirect
        $this->setRedirect(JRoute::_('index.php?option=com_pktasks&view=tasks' . $this->getRedirectToListAppend(), false));

        return parent::batch($model);
    }
}
