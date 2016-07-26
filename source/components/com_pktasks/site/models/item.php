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
            $db    = $this->getDbo();
            $query = $db->getQuery(true);

            $query->select($this->getState('item.select', 'a.*'));

            $query->from('#__pk_tasks AS a');

            // Join over the users for the checked out user.
            $query->select('uc.name AS editor')
                  ->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

            // Join over the asset groups.
            $query->select('ag.title AS access_level')
                  ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

            // Join over the users for the author.
            $query->select('ua.name AS author_name')
                  ->join('LEFT', '#__users AS ua ON ua.id = a.created_by');

            // Join over the projects for the title
            $query->select('p.title AS project_title')
                  ->join('LEFT', '#__pk_projects AS p ON p.id = a.project_id');

            // Join over the milestones for the title
            $query->select('m.title AS milestone_title')
                  ->join('LEFT', '#__pk_milestones AS m ON m.id = a.milestone_id');

            $query->where('a.id = ' . $pk);

            $db->setQuery($query);
            $data = $db->loadObject();

            if (empty($data)) {
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
