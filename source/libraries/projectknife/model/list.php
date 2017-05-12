<?php
/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class PKModelList extends JModelList
{
    /**
     * Method to cache the last query constructed.
     *
     * @return    object
     */
    protected function _getListQuery()
    {
        // Capture the last store id used.
        static $last_id;

        // Compute the current store id.
        $current_id = $this->getStoreId();

        // If the last store id is different from the current, refresh the query.
        if ($last_id != $current_id || empty($this->query)) {
            $last_id = $current_id;

            $this->query = $this->getListQuery();

            // Load Projectknife plugins
            $dispatcher = JEventDispatcher::getInstance();
            JPluginHelper::importPlugin('projectknife');

            $dispatcher->trigger('onPKAfterGetListQuery', array($this->name, &$this->query));
        }

        return $this->query;
    }


    /**
     * Method to get a store id based on model configuration state.
     *
     * @param     string    $id    A prefix for the store id.
     *
     * @return    string           A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('restrict.access');
        $id .= ':' . $this->getState('restrict.published');
        $id .= ':' . serialize($this->getState('auth.levels'));

        return parent::getStoreId($id);
    }


    /**
     * Method to auto-populate the model state.
     *
     * @param     string    $ordering     An optional ordering field.
     * @param     string    $direction    An optional direction (asc|desc).
     *
     * @return    void
     */
    protected function populateState($ordering = null, $direction = null)
    {
        $user = JFactory::getUser();

        // Set viewing access and publishing state restrictions
        $restrict_access    = (!$user->authorise('core.admin', $this->option) && !$user->authorise('core.manage', $this->option));
        $restrict_published = !$user->authorise('core.edit.state', $this->option);

        $this->setState('restrict.access',    $restrict_access);
        $this->setState('restrict.published', $restrict_published);

        if ($restrict_access) {
            $this->setState('filter.access', '');
            $this->setState('auth.levels',   $user->getAuthorisedViewLevels());
            $this->setState('auth.projects', PKUserHelper::getProjects());
        }
        else {
            $this->setState('auth.levels',   array());
            $this->setState('auth.projects', array());
        }

        if ($restrict_published) {
            $this->setState('filter.published', 1);
        }

        parent::populateState($ordering, $direction);

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterPopulateState', array($this->name, &$this->state));
    }


    /**
     * Method to get an array of data items.
     *
     * @return    mixed    An array of data items on success, false on failure.
     */
    public function getItems()
    {
        // Get a storage key.
        $store = $this->getStoreId();

        // Try to load the data from internal storage.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        // Load the list items.
        $query = $this->_getListQuery();

        try {
            $items = $this->_getList($query, $this->getStart(), $this->getState('list.limit'));
        }
        catch (RuntimeException $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Prepare items
        $this->prepareItems($items);

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterPrepareItems', array($this->name, &$items));

        // Add the items to the internal cache.
        $this->cache[$store] = $items;

        return $this->cache[$store];
    }


    /**
     * Injects additional data into a list of items
     *
     * @param     array    $items
     *
     * @return    void
     */
    protected function prepareItems(&$items)
    {

    }
}
