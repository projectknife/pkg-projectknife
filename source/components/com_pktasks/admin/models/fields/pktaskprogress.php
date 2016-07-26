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


JFormHelper::loadFieldClass('list');


/**
 * Form Field class for selecting the task progresss.
 *
 */
class JFormFieldPKtaskProgress extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKtaskProgress';

    /**
     * Progress display tyle
     *
     * @var integer
     */
    protected $progress_type = 1;


    /**
     * Method to instantiate the form field object.
     *
     * @param    jform    $form    The form to attach to the form field object.
     */
    public function __construct($form = null)
    {
        parent::__construct($form);

        $params = JComponentHelper::getParams('com_pktasks');

        $this->progress_type = (int) $params->get('progress_type', 1);
    }


    /**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$attr .= $this->required ? ' required aria-required="true"' : '';
		$attr .= $this->autofocus ? ' autofocus' : '';

		// To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->readonly == '1' || (string) $this->readonly == 'true' || (string) $this->disabled == '1'|| (string) $this->disabled == 'true') {
			$attr .= ' disabled="disabled"';
		}

		// Initialize JavaScript field attributes.
		$attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

        if ($this->progress_type > 1) {
            // Get the field options.
    		$options = (array) $this->getOptions();

            if ($this->value > 0) {
                // Floor value to the nearest available step
                $this->value = floor($this->value / $this->progress_type) * $this->progress_type;
            }

    		// Create a read-only list (no name) with hidden input(s) to store the value(s).
    		if ((string) $this->readonly == '1' || (string) $this->readonly == 'true') {
    			$html[] = JHtml::_('select.genericlist', $options, '', trim($attr), 'value', 'text', $this->value, $this->id);
    			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"/>';
    		}
    		else {
    			$html[] = JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
    		}
        }
        else {
            // To-do/Done toggle button
            $html = array();

    		// Initialize some field attributes.
    		$class     = !empty($this->class) ? ' class="radio ' . $this->class . '"' : ' class="radio btn-group btn-group-yesno"';
    		$required  = $this->required ? ' required aria-required="true"' : '';
    		$autofocus = $this->autofocus ? ' autofocus' : '';
    		$disabled  = $this->disabled ? ' disabled' : '';
    		$readonly  = $this->readonly;

    		// Start the radio field output.
    		$html[] = '<fieldset id="' . $this->id . '"' . $class . $required . $autofocus . $disabled . ' >';

            $this->value = (int) $this->value;

    		// Get the field options.
    		$options = array();

            $opt = new stdClass();
            $opt->value = $this->value < 100 ? intval($this->value) : 0;
            $opt->text  = JText::_('PKGLOBAL_TODO');
            $options[]  = $opt;

            $opt = new stdClass();
            $opt->value = 100;
            $opt->text  = JText::_('PKGLOBAL_COMPLETED');
            $options[]  = $opt;

    		// Build the radio field output.
    		foreach ($options as $i => $option)
    		{
    			// Initialize some option attributes.
    			$checked = ((string) $option->value == (string) $this->value) ? ' checked="checked"' : '';
    			$class = !empty($option->class) ? ' class="' . $option->class . '"' : '';

    			$disabled = !empty($option->disable) || ($readonly && !$checked);

    			$disabled = $disabled ? ' disabled' : '';

    			// Initialize some JavaScript option attributes.
    			$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';
    			$onchange = !empty($option->onchange) ? ' onchange="' . $option->onchange . '"' : '';

    			$html[] = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '" value="'
    				. htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $required . $onclick
    				. $onchange . $disabled . ' />';

    			$html[] = '<label for="' . $this->id . $i . '"' . $class . ' >'
    				. JText::alt($option->text, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)) . '</label>';

    			$required = '';
    		}

    		// End the radio field output.
    		$html[] = '</fieldset>';
        }


		return implode($html);
	}


    /**
     * Method to get the field options.
     *
     * @return    array    The field option objects.
     */
    protected function getOptions()
    {
        for ($i = 0; $i < 101; $i += $this->progress_type)
        {
            $options[] = JHtml::_(
                'select.option',
                $i,
                $i . '%',
                'value',
                'text',
                false
            );
        }

        return $options;
    }
}
