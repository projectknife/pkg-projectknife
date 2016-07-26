<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die();


class PKtasksTableTask extends JTable
{
    /**
     * Constructor
     *
     * @param    jdatabasedriver    $db    A database connector object
     */
    public function __construct(JDatabaseDriver $db)
    {
        parent::__construct('#__pk_tasks', 'id', $db);

        $this->_observers = new JObserverUpdater($this);

        JObserverMapper::attachAllObservers($this);
        JTableObserverTags::createObserver($this, array('typeAlias' => 'com_pktasks.task'));
    }


    /**
     * Overloaded check function
     *
     * @return    boolean    True on success, false on failure
     */
    public function check()
    {
        if (trim($this->title) == '') {
            $this->setError(JText::_('COM_PKTASKS_WARNING_PROVIDE_VALID_NAME'));
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
        $table = JTable::getInstance('Task', 'PKtasksTable', array('dbo', $this->getDbo()));

        if ($table->load(array('alias' => $this->alias, 'project_id' => $table->project_id)) && ($table->id != $this->id || $this->id == 0)) {
            $this->setError(JText::_('COM_PKTASKS_ERROR_PROJECT_UNIQUE_ALIAS'));

            return false;
        }

        return parent::store($update_nulls);
    }
}
