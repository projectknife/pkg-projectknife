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
    protected $restrict_access    = true;
    protected $restrict_published = true;
    protected $auth_levels        = array();
    protected $auth_projects      = array();


    public function __construct()
    {
        parent::__construct();

        $this->setupRestrictions();
    }


    protected function setupRestrictions()
    {
        $user = JFactory::getUser();

        // Determine whether to restrict item viewing access or not
        $can_admin  = $user->authorise('core.admin', $this->option);
        $can_manage = $user->authorise('core.manage', $this->option);

        $this->restrict_access = (!$can_admin && !$can_manage);

        // Determine whether to restrict viewing to published items only
        $this->restrict_published = !$user->authorise('core.edit.state', $this->option);

        if ($this->restrict_access) {
            $this->auth_levels   = $user->getAuthorisedViewLevels();
            $this->auth_projects = PKUserHelper::getProjects();
        }
    }


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

            $dispatcher->trigger('onPKAfterGetListQuery', array($this->context, &$this->query));
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
        $id .= ':' . $this->restrict_access;
        $id .= ':' . $this->restrict_published;
        $id .= ':' . serialize($this->auth_levels);
        $id .= ':' . serialize($this->auth_projects);

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
        parent::populateState($ordering, $direction);

        // Save restrictions in state
        $this->setState('restrict.access',    $this->restrict_access);
        $this->setState('restrict.published', $this->restrict_published);

        // Load Projectknife plugins
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('projectknife');

        $dispatcher->trigger('onProjectknifeAfterPopulateState', array($this->context, &$this->state));
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

        $dispatcher->trigger('onProjectknifeAfterPrepareItems', array($this->context, &$items));

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
