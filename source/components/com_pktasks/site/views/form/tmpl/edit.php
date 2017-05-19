<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', '#item-form select');
JHtml::_('script', 'lib_projectknife/form.js', false, true, false, false, true);
JHtml::_('script', 'lib_projectknife/calendar.js', false, true, false, false, true);

$app    = JFactory::getApplication();
$input  = $app->input;
$params = JComponentHelper::getParams('com_pktasks');

JText::script('PKGLOBAL_UNDEFINED');

JFactory::getDocument()->addScriptDeclaration('
    Joomla.submitbutton = function(task)
    {
        if (task == "form.cancel" || document.formvalidator.isValid(document.getElementById("item-form")))
        {
            ' . $this->form->getField('description')->save() . '
            Joomla.submitform(task, document.getElementById("item-form"));
        }
    };

    jQuery(document).ready(function()
    {
        jQuery("#jform_project_id").change(
            function()
            {
                PKform.ajaxUpdateSchedule(this, "#jform_project_schedule", "index.php?option=com_pkprojects&task=form.getSchedule");
                PKform.ajaxUpdateOptions("#jform_milestone_id", true, "index.php?option=com_pktasks&task=list.getMilestoneOptions&project_id=" + jQuery(this).val());
            }
        );

        // Cannot trigger change event for project select because the selected milestone would be lost
        PKform.ajaxUpdateSchedule(jQuery("#jform_project_id"), "#jform_project_schedule", "index.php?option=com_pkprojects&task=form.getSchedule");

        jQuery("#jform_milestone_id").change(
            function()
            {
                PKform.ajaxUpdateSchedule(this, "#jform_milestone_schedule", "index.php?option=com_pkmilestones&task=form.getSchedule");
            }
        ).trigger("change");
    });
');
?>
<form action="<?php echo JRoute::_('index.php?option=com_pktasks&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
    <div class="btn-toolbar">
        <?php echo $this->toolbar; ?>
    </div>
    <div class="form-inline form-inline-header">
    	<?php
    	echo $this->form->renderField('title');
        echo $this->form->renderField('alias');
    	?>
    </div>
    <p></p>
    <div class="form-horizontal">
        <?php
            echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'description'));
            echo JHtml::_('bootstrap.addTab', 'myTab', 'description', JText::_('JGLOBAL_DESCRIPTION', true));
            ?>
            <div class="row-fluid">
                <div class="12">
                    <fieldset class="adminform">
                        <?php echo $this->form->getInput('description'); ?>
                    </fieldset>
                </div>
            </div>
        <?php
            echo JHtml::_('bootstrap.endTab');
            echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('PKGLOBAL_PUBLISHING', true));
            ?>
            <div class="row-fluid form-vertical">
                <div class="span4">
                    <?php
                    $fields = $this->form->getFieldset('publishing-left-col');

                    foreach ($fields as $field)
                    {
                        echo $field->renderField();
                    }
                    ?>
                </div>
                <div class="span4">
                    <?php
                    $fields = $this->form->getFieldset('publishing-middle-col');

                    foreach ($fields as $field)
                    {
                        echo $field->renderField();
                    }
                    ?>
                </div>
                <div class="span4">
                    <?php
                    $fields = $this->form->getFieldset('publishing-right-col');

                    foreach ($fields as $field)
                    {
                        echo $field->renderField();
                    }
                    ?>
                </div>
            </div>
        <?php
            echo JHtml::_('bootstrap.endTab');

            $fieldsets = $this->form->getFieldsets();
            $ignore    = array('publishing-left-col', 'publishing-middle-col', 'publishing-right-col');
            $fields    = array();

            $fieldset_title = "";

            foreach ($fieldsets AS $fieldset)
            {
                if (in_array($fieldset->name, $ignore)) {
                    continue;
                }

                if (!empty($fieldset->label)) {
                    $fieldset_title = JText::_($fieldset->label);
                }
                else {
                    $fieldset_title = JText::_('COM_PKTASKS_TASK_TAB_' . strtoupper($fieldset->name));
                }

                echo JHtml::_('bootstrap.addTab', 'myTab', $fieldset->name, $fieldset_title);

                if (isset($fieldset->description) && trim($fieldset->description)) {
        			echo '<p class="alert alert-info">' . $this->escape(JText::_($fieldset->description)) . '</p>';
        		}
                ?>
                <div class="row-fluid form-vertical form-vertical-desktop">
                    <div class="span12">
                        <?php
                        $fields = $this->form->getFieldset($fieldset->name);

                        foreach ($fields as $field)
                        {
                            echo $field->renderField();
                        }
                        ?>
                    </div>
                </div>
                <?php
                echo JHtml::_('bootstrap.endTab');
            }

            echo JHtml::_('bootstrap.endTabSet');
        ?>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="return" value="<?php echo $this->return; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
