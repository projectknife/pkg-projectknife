<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_milestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die();


JFormHelper::loadFieldClass('list');


/**
 * Form Field class for selecting a milestone.
 *
 */
class JFormFieldPKmilestone extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKmilestone';


    /**
     * Method to get the field options.
     *
     * @return    array    The field option objects.
     */
    protected function getOptions()
    {
        $options    = parent::getOptions();
        $project_id = (int) $this->form->getValue('project_id');

        if (!$project_id) {
            return $options;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Get a list of milestones
        $query->select('id AS value, title AS text')
              ->from('#__pk_milestones')
              ->where('project_id = ' . $project_id)
              ->order('title ASC');

        $db->setQuery($query);
        $items = $db->loadObjectList();
        $count = count($items);

        for ($i = 0; $i != $count; $i++)
        {
            $options[] = JHtml::_(
                'select.option',
                $items[$i]->value,
                $items[$i]->text,
                'value',
                'text',
                false
            );
        }

        return $options;
    }
}
