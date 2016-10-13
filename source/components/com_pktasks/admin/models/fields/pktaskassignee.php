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


use Joomla\Registry\Registry;

JFormHelper::loadFieldClass('list');


/**
 * Form Field class for assigning users to a task.
 *
 */
class JFormFieldPKtaskAssignee extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKtaskAssignee';


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
            $url = JUri::root();
        }
        else {
            $url = JUri::root() . 'administrator/';
        }

        $url .= 'index.php?option=com_pkprojects&task=project.searchMember&tmpl=component&format=json';

        $chosenAjaxSettings = new Registry(
            array(
                'selector'      => '#' . $this->id,
                'type'          => 'GET',
                'url'           => $url . "&project_id=' + jQuery('#jform_project_id').val() + '",
                'dataType'      => 'json',
                'jsonTermKey'   => 'like',
                'minTermLength' => 3
            )
        );

        JHtml::_('formbehavior.ajaxchosen', $chosenAjaxSettings);


        $attr = 'class="span12" multiple="multiple"';
        $html   = array();

        $html[] = JHtml::_('select.genericlist', $this->getOptions(), $this->name, trim($attr), 'value', 'text', $this->value, $this->id);

        /*
        $html[] = '<select id="' . $this->fieldname . '_search" name="' . $this->name . '" class="span12" multiple>';
        $html[] = implode('', $this->getOptions());
        $html[] = '</select>';
*/
        return implode(',', $html);
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

        // Get system plugin settings
        $sys_params = PKPluginHelper::getParams('system', 'projectknife');

        switch ($sys_params->get('user_display_name'))
        {
            case '1':
                $display_name_field = 'name';
                break;

            default:
                $display_name_field = 'username';
                break;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id AS value, ' . $display_name_field . ' AS text')
              ->from('#__users')
              ->where('id IN(' . implode(', ', $this->value) . ')')
              ->order('' . $display_name_field . ' ASC');

        $db->setQuery($query);
        $users = $db->loadObjectList();

        foreach ($users AS $user)
        {
            $options[] = JHtml::_('select.option', $user->value, $user->text, 'value', 'text', false);
        }

        return $options;
    }
}
