<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_projects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die();


/**
 * Form Field class for display access info
 *
 */
class JFormFieldPKaccessInfo extends JFormField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKaccessInfo';


    /**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
	    $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, rules')
              ->from('#__viewlevels');

        $db->setQuery($query);
        $viewrules = $db->loadObjectList();
        $group_ids = array();
        $count     = count($viewrules);

        for ($i = 0; $i != $count; $i++)
        {
            $view = &$viewrules[$i];

            $view->rules = json_decode($view->rules);
            $group_ids   = array_merge($group_ids, $view->rules);
        }

        $group_ids = array_unique($group_ids);

        $query->clear()
              ->select('id, title')
              ->from('#__usergroups')
              ->where('id IN(' . implode(', ', $group_ids) . ')');



		return '<span id="' . $this->getGetId() . '"></span>';
	}
}
