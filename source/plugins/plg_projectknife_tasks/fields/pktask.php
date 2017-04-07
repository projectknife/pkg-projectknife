<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_tasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die();


JFormHelper::loadFieldClass('list');


/**
 * Form Field class for selecting a task.
 *
 */
class JFormFieldPKtask extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKtask';


    /**
     * Method to get the field input markup.
     *
     * @return    string    The field input markup.
     */
    protected function getInput()
    {
        if (isset($this->element['modal']) && strtolower(trim($this->element['modal'])) == 'true') {
            // Modal
            $allowEdit  = ((string) $this->element['edit'] == 'true')   ? true : false;
            $allowClear = ((string) $this->element['clear'] != 'false') ? true : false;

            // The active task id field.
            $value = (int) $this->value > 0 ? (int) $this->value : '';
            $title = null;


            // Build the script.
            $script = 'function jSelectTask_' . $this->id . '(id, title, pid) '
                    . '{'
                    . '    document.getElementById("' . $this->id . '_id").value = id;'
                    . '    document.getElementById("' . $this->id . '_name").value = title;'
                    . '    if (id == "' . $value . '") {'
                    . '        jQuery("#' . $this->id . '_edit").removeClass("hidden");'
                    . '    }'
                    . '    else {'
                    . '        jQuery("#' . $this->id . '_edit").addClass("hidden");'
                    . '    }'
                    . '    jQuery("#' . $this->id . '_clear").removeClass("hidden");'
                    . '    jQuery("#taskSelect' . $this->id . 'Modal").modal("hide");';

            if ($this->required) {
                $script .= 'document.formvalidator.validate(document.getElementById("' . $this->id . '_id"));'
                         . 'document.formvalidator.validate(document.getElementById("' . $this->id . '_name"));';
            }

            $script .= '}';

            // Edit button script
            $script .= 'function jEditTask_' . $value . '(title)'
                     . '{'
                     . '     document.getElementById("' . $this->id . '_name").value = title;'
                     . '}';

            // Clear button script
            static $clear;

            if (!$clear) {
                $clear = true;

                $script .= 'function jClearTask(id)'
                         . '{'
                         . '    document.getElementById(id + "_id").value = "";'
                         . '    document.getElementById(id + "_name").value = "' . htmlspecialchars(JText::_('PLG_PROJECTKNIFE_TASKS_SELECT_TASK', true), ENT_COMPAT, 'UTF-8') . '";'
                         . '    jQuery("#"+id + "_clear").addClass("hidden");'
                         . '    if (document.getElementById(id + "_edit")) {'
                         . '        jQuery("#"+id + "_edit").addClass("hidden");'
                         . '    }'
                         . '    return false;'
                         . '}';
            }


            // Add the script to the document head.
            JFactory::getDocument()->addScriptDeclaration($script);

            // Setup variables for display.
            $html = array();
            $app  = JFactory::getApplication();

            $list_view = $app->isSite() ? 'list' : 'tasks';

            $list_url = 'index.php?option=com_pktasks&amp;view=' . $list_view . '&amp;layout=modal&amp;tmpl=component'
                      . '&amp;function=jSelectTask_' . $this->id;

            $item_url = 'index.php?option=com_pktasks&amp;view=task&amp;layout=modal&amp;tmpl=component'
			          . '&amp;task=article.edit&amp;function=jEditArticle_' . $value;

            $modal_title = JText::_('PLG_PROJECTKNIFE_TASKS_CHANGE_TASK');
            $urlSelect   = $list_url . '&amp;' . JSession::getFormToken() . '=1';
            $urlEdit     = $item_url . '&amp;id=' . $value . '&amp;' . JSession::getFormToken() . '=1';

            if ($value) {
                $db    = JFactory::getDbo();

                $query = $db->getQuery(true)
                      ->select($db->quoteName('title'))
                      ->from($db->quoteName('#__pk_tasks'))
                      ->where($db->quoteName('id') . ' = ' . (int) $value);

                $db->setQuery($query);

                try {
                    $title = $db->loadResult();
                }
                catch (RuntimeException $e) {
                    JError::raiseWarning(500, $e->getMessage());
                }
            }

            if (empty($title)) {
                $title = JText::_('PLG_PROJECTKNIFE_TASKS_SELECT_TASK');
            }

            $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

            // The current display field.
            $html[] = '<span class="input-append">';
            $html[] = '<input class="input-medium" id="' . $this->id . '_name" type="text" value="' . $title . '" disabled="disabled" size="35" />';

            // Select button
            $html[] = '<a class="btn hasTooltip" data-toggle="modal" role="button" href="#taskSelect' . $this->id . 'Modal"'
                    . ' title="' . JHtml::tooltipText('PLG_PROJECTKNIFE_TASKS_CHANGE_TASK') . '">'
                    . '<span class="icon-file"></span> ' . JText::_('JSELECT')
                    . '</a>';

            // Edit button
            if ($allowEdit) {
                $html[] = '<a'
                        . ' class="btn hasTooltip' . ($value ? '' : ' hidden') . '"'
                        . ' id="' . $this->id . '_edit"'
                        . ' data-toggle="modal"'
                        . ' role="button"'
                        . ' href="#taskEdit' . $value . 'Modal"'
                        . ' title="' . JHtml::tooltipText('PLG_PROJECTKNIFE_TASKS_EDIT_TASK') . '">'
                        . '<span class="icon-edit"></span> ' . JText::_('JACTION_EDIT')
                        . '</a>';
            }

            // Clear button
            if ($allowClear) {
                $html[] = '<button'
                        . ' class="btn' . ($value ? '' : ' hidden') . '"'
                        . ' id="' . $this->id . '_clear"'
                        . ' onclick="return jClearTask(\'' . $this->id . '\')">'
                        . '<span class="icon-remove"></span>' . JText::_('JCLEAR')
                        . '</button>';
            }

            $html[] = '</span>';

            // Select modal
            $html[] = JHtml::_(
                'bootstrap.renderModal',
                'taskSelect' . $this->id . 'Modal',
                array(
                    'title'       => JText::_('PLG_PROJECTKNIFE_TASKS_SELECT_TASK'),
                    'url'         => $urlSelect,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => '70',
                    'modalWidth'  => '80',
                    'footer'      => '<a type="button" class="btn" data-dismiss="modal" aria-hidden="true">'
                                      . JText::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</a>',
                )
            );

            // Modal window
            $html[] = JHtml::_(
                'bootstrap.renderModal',
                'taskEdit' . $value . 'Modal',
                array(
                    'title'       => JText::_('PLG_PROJECTKNIFE_TASKS_SELECT_TASK'),
                    'backdrop'    => 'static',
                    'keyboard'    => false,
                    'closeButton' => false,
                    'url'         => $urlEdit,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => '70',
                    'modalWidth'  => '80',
                    'footer'      => '<a type="button" class="btn" data-dismiss="modal" aria-hidden="true"'
                                     . ' onclick="jQuery(\'#taskEdit' . $value . 'Modal iframe\').contents().find(\'#closeBtn\').click();">'
                                     . JText::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</a>'
                                     . '<button type="button" class="btn btn-primary" aria-hidden="true"'
                                     . ' onclick="jQuery(\'#taskEdit' . $value . 'Modal iframe\').contents().find(\'#saveBtn\').click();">'
                                     . JText::_("JSAVE") . '</button>'
                                     . '<button type="button" class="btn btn-success" aria-hidden="true"'
                                     . ' onclick="jQuery(\'#taskEdit' . $value . 'Modal iframe\').contents().find(\'#applyBtn\').click();">'
                                     . JText::_("JAPPLY") . '</button>',
                )
            );

            // Note: class='required' for client side validation.
            $class = $this->required ? ' class="required modal-value"' : '';

            $html[] = '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

            return implode("\n", $html);
        }
        else {
            return parent::getInput();
        }
    }


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

        // Get a list of tasks
        $query->select('id AS value, title AS text')
              ->from('#__pk_tasks')
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
