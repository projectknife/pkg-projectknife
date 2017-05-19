<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_projects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;


use Joomla\Registry\Registry;


class JFormRulePKUserRules extends JFormRuleRules
{
    /**
	 * Method to test the value.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 * @param   Registry          $input    An optional Registry object with the entire data set to validate against the entire form.
	 * @param   JForm             $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 */
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		// Get the possible field actions and the ones posted to validate them.
		$fieldActions = self::getFieldActions($element);
		$valueActions = self::getValueActions($value);

		// Make sure that all posted actions are in the list of possible actions for the field.
		foreach ($valueActions as $action)
		{
			if (!in_array($action, $fieldActions))
			{
				return false;
			}
		}

		return true;
	}


    /**
	 * Method to get the list of permission action names from the form field value.
	 *
	 * @param   mixed  $value  The form field value to validate.
	 *
	 * @return  array  A list of permission action names from the form field value.
	 *
	 * @since   11.1
	 */
	protected function getValueActions($value)
	{
		$actions = array_merge(array('core.user'), parent::getValueActions($value));

		return $actions;
	}

	/**
	 * Method to get the list of possible permission action names for the form field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the
	 *                                      form field object.
	 *
	 * @return  array   A list of permission action names from the form field element definition.
	 */
	protected function getFieldActions(SimpleXMLElement $element)
	{
		$actions = array('core.user');

        // Initialise some field attributes.
		$section   = $element['section']   ? (string) $element['section']   : '';
		$component = $element['component'] ? (string) $element['component'] : '';

		// Get the asset actions for the element.
		$elActions  = JAccess::getActions($component, $section);
        $components = PKApplicationHelper::getComponents();

        foreach ($components AS $cmp)
        {
            if (!$cmp->enabled || $cmp->element == $component) {
                continue;
            }

            $tmp = JAccess::getActions($cmp->element, $section);

            if (is_array($tmp) && count($tmp)) {
                $elActions = array_merge($elActions, $tmp);
            }
        }

		// Iterate over the asset actions and add to the actions.
		foreach ($elActions as $item)
		{
			$actions[] = $item->name;
		}

		// Iterate over the children and add to the actions.
		foreach ($element->children() as $el)
		{
			if ($el->getName() == 'action')
			{
				$actions[] = (string) $el['name'];
			}
		}

		return $actions;
	}
}
