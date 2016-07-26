<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;


class PKprojectsTableProject extends JTable
{
    /**
     * Constructor
     *
     * @param    jdatabasedriver    $db    A database connector object
     */
    public function __construct(JDatabaseDriver $db)
    {
        parent::__construct('#__pk_projects', 'id', $db);

        $this->_observers = new JObserverUpdater($this);

        JObserverMapper::attachAllObservers($this);
        JTableObserverTags::createObserver($this, array('typeAlias' => 'com_pkprojects.project'));
    }


    /**
     * Method to compute the default name of the asset.
     *
     * @return    string
     */
    protected function _getAssetName()
    {
        $k = $this->_tbl_key;

        return 'com_pkprojects.project.' . (int) $this->$k;
    }


    /**
     * Method to return the title to use for the asset table.
     *
     * @return    string
     */
    protected function _getAssetTitle()
    {
        return $this->title;
    }


    /**
     * Method to get the parent asset id for the record
     *
     * @param     jtable     $table    A JTable object (optional) for the asset parent
     * @param     integer    $id       The id (optional) of the content.
     *
     * @return    integer
     */
    protected function _getAssetParentId(JTable $table = null, $id = null)
    {
        $asset_id = null;

        if ($this->category_id) {
            // This is an item under a category.
            $query = $this->_db->getQuery(true);

            $query->select($this->_db->quoteName('asset_id'))
                  ->from($this->_db->quoteName('#__categories'))
                  ->where($this->_db->quoteName('id') . ' = ' . (int) $this->category_id);

            $this->_db->setQuery($query);

            if ($result = $this->_db->loadResult()) {
                $asset_id = (int) $result;
            }
        }

        if (!$asset_id) {
            // Fall back to component
            $query = $this->_db->getQuery(true);

            $query->select($this->_db->quoteName('id'))
                  ->from($this->_db->quoteName('#__assets'))
                  ->where($this->_db->quoteName('name') . ' = ' . $this->_db->quote('com_pkprojects'));

            $this->_db->setQuery($query);

            if ($result = $this->_db->loadResult()) {
                $asset_id = (int) $result;
            }
        }

        // Return the asset id.
        if ($asset_id) {
            return $asset_id;
        }

        return parent::_getAssetParentId($table, $id);
    }


    /**
     * Overloaded check function
     *
     * @return    boolean    True on success, false on failure
     */
    public function check()
    {
        if (trim($this->title) == '') {
            $this->setError(JText::_('COM_PKPROJECTS_WARNING_PROVIDE_VALID_NAME'));
            return false;
        }

        if (trim($this->alias) == '') {
            $this->alias = $this->title;
        }

        $this->alias = JApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
        }

        return true;
    }


    /**
     * Overloaded bind function
     *
     * @param     array    $array     Named array
     * @param     mixed    $ignore    An optional array or space separated list of properties to ignore while binding.
     *
     * @return    mixed               Null if operation was satisfactory, otherwise returns an error string
     */
    public function bind($array, $ignore = '')
    {
        // Bind the rules.
        if (isset($array['rules']) && is_array($array['rules'])) {
            $rules = new JAccessRules($array['rules']);
            $this->setRules($rules);
        }

        return parent::bind($array, $ignore);
    }


    /**
     * Overrides JTable::store to set modified data and user id.
     *
     * @param     boolean    $update_nulls    True to update fields even if they are null.
     *
     * @return    boolean                     True on success.
     */
    public function store($update_nulls = false)
    {
        $date = JFactory::getDate();
        $user = JFactory::getUser();

        $this->modified = $date->toSql();

        if ($this->id) {
            // Existing item
            $this->modified_by = $user->get('id');
        }
        else {
            // New item. An item created and created_by field can be set by the user,
            // so we don't touch either of these if they are set.
            if (!(int) $this->created) {
                $this->created = $date->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }
        }

        // Verify that the alias is unique
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(id)')
              ->from($this->_tbl)
              ->where('alias = ' . $this->_db->quote($this->alias))
              ->where('id != ' . intval($this->id));

        $this->_db->setQuery($query);

        if ($this->_db->loadResult()) {
            $this->setError(JText::_('COM_PKPROJECTS_ERROR_PROJECT_UNIQUE_ALIAS'));
            return false;
        }

        return parent::store($update_nulls);
    }
}
