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


class JFormFieldPKcalendar extends JFormFieldCalendar
{
    public $type = 'PKcalendar';


    /**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$config = JFactory::getConfig();
		$user   = JFactory::getUser();
        $form   = $this->form;

		// Translate placeholder text
		$hint = $this->translateHint ? JText::_($this->hint) : $this->hint;

		// Initialize some field attributes.
		$format = $this->format;

		// Build the attributes array.
		$attributes = array();

		empty($this->size)      ? null : $attributes['size'] = $this->size;
		empty($this->maxlength) ? null : $attributes['maxlength'] = $this->maxlength;
		empty($this->class)     ? null : $attributes['class'] = $this->class;
		!$this->readonly        ? null : $attributes['readonly'] = 'readonly';
		!$this->disabled        ? null : $attributes['disabled'] = 'disabled';
		empty($this->onchange)  ? null : $attributes['onchange'] = $this->onchange;
		empty($hint)            ? null : $attributes['placeholder'] = $hint;
		$this->autocomplete     ? null : $attributes['autocomplete'] = 'off';
		!$this->autofocus       ? null : $attributes['autofocus'] = '';

		if ($this->required) {
			$attributes['required'] = '';
			$attributes['aria-required'] = 'true';
		}


        // Set the initial value
        if ($this->value == '') {
            $fields = array(
                'task_id'      => (int) $this->form->getValue('task_id'),
                'milestone_id' => (int) $this->form->getValue('milestone_id'),
                'project_id'   => (int) $this->form->getValue('project_id'),
            );
        }

		// Handle the special case for "now".
		if (strtoupper($this->value) == 'NOW') {
			$this->value = JFactory::getDate()->format($format);
		}

		// If a known filter is given use it.
		switch (strtoupper($this->filter))
		{
			case 'SERVER_UTC':
				// Convert a date to UTC based on the server timezone.
				if ($this->value && $this->value != JFactory::getDbo()->getNullDate()) {
					// Get a date object based on the correct timezone.
					$date = JFactory::getDate($this->value, 'UTC');
					$date->setTimezone(new DateTimeZone($config->get('offset')));

					// Transform the date string.
					$this->value = $date->format($format, true, false);
				}
				break;

			case 'USER_UTC':
				// Convert a date to UTC based on the user timezone.
				if ($this->value && $this->value != JFactory::getDbo()->getNullDate()) {
					// Get a date object based on the correct timezone.
					$date = JFactory::getDate($this->value, 'UTC');

					$date->setTimezone(new DateTimeZone($user->getParam('timezone', $config->get('offset'))));

					// Transform the date string.
					$this->value = $date->format($format, true, false);
				}
				break;
		}

		// Including fallback code for HTML5 non supported browsers.
		JHtml::_('jquery.framework');
		JHtml::_('script', 'system/html5fallback.js', false, true);

		return JHtml::_('calendar', $this->value, $this->name, $this->id, $format, $attributes);
	}
}
