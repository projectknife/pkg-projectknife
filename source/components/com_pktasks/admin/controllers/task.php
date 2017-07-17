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


    /**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean          True if successful, false otherwise.
	 */
	public function save($key = null, $urlVar = null)
	{
	    $data = $this->input->post->get('jform', array(), 'array');
        $id   = (isset($data['id']) ? intval($data['id']) : 0);

        if ($id || !isset($data['progress']) || !isset($data['predecessors'])) {
            return parent::save($key, $urlVar);
        }


        // Check on progress
        $progress = (int) $data['progress'];

        if (!$progress) {
            return parent::save($key, $urlVar);
        }

        $predecessors = $data['predecessors'];
        JArrayHelper::toInteger($predecessors);

        if (!count($predecessors)) {
            return parent::save($key, $urlVar);
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('title')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $predecessors) . ')')
              ->where('progress < 100')
              ->where('published > 0')
              ->order('title ASC');

        $db->setQuery($query);
        $blocking  = $db->loadColumn();

        if (count($blocking)) {
            // Set out a message that the progress cannot be changed
            $tasks = implode(', ', $blocking);
            $app   = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf('COM_PKTASKS_TASK_PROGRESS_BLOCKED_BY_PREDECESSORS', $tasks));
            unset($data['progress']);
        }

        return parent::save($key, $urlVar);
    }
}
