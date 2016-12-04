<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


class PKtasksModelItem extends JModelItem
{
    /**
     * Model context string.
     *
     * @var    string
     */
    protected $_context = 'com_pktasks.task';


    /**
     * Method to auto-populate the model state.
     * Note. Calling getState in this method will result in recursion.
     *
     * @return    void
     */
    protected function populateState()
    {
        $app = JFactory::getApplication();

        // Get item id
        $value = $app->input->getInt('id');
        $this->setState('task.id', $value);

        // Load the parameters.
        $value = $app->getParams();
        $this->setState('params', $value);
    }


    /**
     * Method to get item data.
     *
     * @param     integer    $pk    The id of the item.
     *
     * @return    mixed             Item data object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        if (empty($pk)) {
            $pk = (int) $this->getState('task.id');
        }

        if ($this->_item === null) {
            $this->_item = array();
        }

        if (isset($this->_item[$pk])) {
            return $this->_item[$pk];
        }

        try
        {
            $form = $this->getInstance('Form', 'PKTasksModel');

            if (!$form) {
                $data = null;
            }
            else {
                $data = $form->getItem($pk);
            }

            if (empty($data) || empty($data->id)) {
				return JError::raiseError(404, JText::_('COM_PKTASKS_ERROR_TASK_NOT_FOUND'));
			}

            // Convert parameter fields to objects.
			$data->params = clone $this->getState('params');

            $this->_item[$pk] = $data;
        }
        catch (Exception $e)
        {
            if ($e->getCode() == 404) {
                JError::raiseError(404, $e->getMessage());
            }
            else {
                $this->setError($e);
                $this->_item[$pk] = false;
            }
        }

        return $this->_item[$pk];
    }
}
