<?php
/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


class PKModelAdmin extends JModelAdmin
{
    protected $event_prepare_save_data;


    /**
     * Constructor.
     *
     * @param    array    $config    An optional associative array of configuration settings.
     *
     */
    public function __construct($config = array())
    {
        parent::__construct($config);

        if (isset($config['event_prepare_save_data'])) {
            $this->event_prepare_save_data = $config['event_prepare_save_data'];
        }
        elseif (empty($this->event_prepare_save_data)) {
            $this->event_prepare_save_data = 'onProjectknifePrepareSaveData';
        }
    }


    /**
     * Method to get the record form.
     *
     * @param     array      $data       Data for the form.
     * @param     boolean    $do_load    True if the form is to load its own data (default case), false if not.
     *
     * @return    mixed                  A JForm object on success, false on failure
     */
    public function getForm($data = array(), $do_load = true)
    {
        return false;
    }


    /**
     * Method to save the form data.
     *
     * @param     array      $data    The form data.
     *
     * @return    boolean             True on success, False on error.
     */
    public function save($data)
    {
        $table   = $this->getTable();
        $key     = $table->getKeyName();
        $pk      = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
        $is_new  = (intval($pk) === 0);

        $this->prepareSaveData($data, $is_new);

        return parent::save($data);
    }


    /**
     * Method to prepare the user input before saving it
     *
     * @param     array    $data      The data to save
     * @param     bool     $is_new    Indicated whether this is a new item or not
     *
     * @return    void
     */
    protected function prepareSaveData(&$data, $is_new)
    {
        JPluginHelper::importPlugin('projectknife');

        $dispatcher = JEventDispatcher::getInstance();
        $context    = $this->option . '.' . $this->name;

        $dispatcher->trigger($this->event_prepare_save_data, array($context, &$data, $is_new));
    }
}
