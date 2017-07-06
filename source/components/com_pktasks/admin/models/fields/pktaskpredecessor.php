<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;


use Joomla\Registry\Registry;

JFormHelper::loadFieldClass('list');


/**
 * Form Field class for selecting task precedessors.
 *
 */
class JFormFieldPKTaskPredecessor extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKTaskPredecessor';


    /**
     * Method to get the field input markup for Access Control Lists.
     * Optionally can be associated with a specific component and section.
     *
     * @return    string    The field input markup.
     */
    protected function getInput()
    {
        $app = JFactory::getApplication();
        // Setup ajax request for finding users
        if ($app->isSite()) {
            $url  = JUri::root();
            $task = 'form';
        }
        else {
            $url  = JUri::root() . 'administrator/';
            $task = 'task';
        }

        $url .= 'index.php?option=com_pktasks&task=' . $task . '.searchPredecessor&tmpl=component&format=json';

        $chosenAjaxSettings = new Registry(
            array(
                'selector'      => '#' . $this->id,
                'type'          => 'GET',
                'url'           => $url . "&project_id=' + jQuery('#jform_project_id').val() + '",
                'dataType'      => 'json',
                'jsonTermKey'   => 'like',
                'minTermLength' => 0
            )
        );

        JHtml::_('formbehavior.ajaxchosen', $chosenAjaxSettings);


        $attr = 'class="span12" multiple="multiple"';
        $html   = array();

        $html[] = JHtml::_('select.genericlist', $this->getOptions(), $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
        $html[] = '<input type="hidden" name="' . $this->name . '" value=""/>';

        return implode("\n", $html);
    }


    /**
     * Method to get the field options.
     *
     * @return    array    The field option objects.
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        if (!is_array($this->value) || empty($this->value)) {
            return $options;
        }

        JArrayHelper::toInteger($this->value);

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id AS value, title AS text')
              ->from('#__pk_tasks')
              ->where('id IN(' . implode(', ', $this->value) . ')')
              ->order('title ASC');

        $db->setQuery($query);
        $tasks = $db->loadObjectList();

        foreach ($tasks AS $task)
        {
            $options[] = JHtml::_('select.option', $task->value, $task->text, 'value', 'text', false);
        }

        return $options;
    }
}