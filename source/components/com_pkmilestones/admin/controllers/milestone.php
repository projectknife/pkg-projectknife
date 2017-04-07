<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


class PKMilestonesControllerMilestone extends JControllerForm
{
    /**
     * Method to check if you can add a new record.
     *
     * @param     array      $data    An array of input data.
     *
     * @return    boolean
     */
    protected function allowAdd($data = array())
    {
        $pid = (isset($data['project_id'])) ? intval($data['project_id']) : 0;

        return (PKUserHelper::authProject('milestone.create', $pid));
    }


    /**
     * Method to check if you can edit an existing record.
     *
     * @param     array      $data    An array of input data.
     * @param     string     $key     The name of the key for the primary key; default is id.
     *
     * @return    boolean
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        $id  = (isset($data[$key])) ? intval($data[$key]) : 0;
        $pid = (isset($data['project_id'])) ? intval($data['project_id']) : 0;

        if (!$id) {
            return PKUserHelper::authProject('milestone.create', $pid);
        }

        // Check "edit" permission
        if (PKUserHelper::authProject('milestone.edit', $pid)) {
            return true;
        }


        // Fall back to "edit.own" permission check
        $user  = JFactory::getUser();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('created_by')
              ->from('#__pk_milestones')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $author = (int) $db->loadResult();

        $can_edit_own = PKUserHelper::authProject('milestone.edit.own', $pid);

        return ($can_edit_own && $user->id > 0 && $user->id == $author);
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
    public function getModel($name = 'Milestone', $prefix = 'PKMilestonesModel', $config = array('ignore_request' => true))
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
        $model = $this->getModel('Milestone', 'PKMilestonesModel', array());

        // Preset the redirect
        $this->setRedirect(JRoute::_('index.php?option=com_pkmilestones&view=milestones' . $this->getRedirectToListAppend(), false));

        return parent::batch($model);
    }
}
