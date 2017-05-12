<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


$list_order     = $this->escape($this->state->get('list.ordering'));
$list_dir       = $this->escape($this->state->get('list.direction'));
$list_order_sec = $this->escape($this->state->get('list.ordering_sec'));
$list_dir_sec   = $this->escape($this->state->get('list.direction_sec'));


$params = JComponentHelper::getParams('com_pktasks');
$progress_type = (int) $params->get('progress_type', 1);


// Load Projectknife plugins
JPluginHelper::importPlugin('projectknife');
$dispatcher = JEventDispatcher::getInstance();


JHtml::_('script', 'lib_projectknife/form.js', false, true, false, false, true);
JHtml::_('script', 'com_pktasks/list.js', false, true, false, false, true);
JHtml::_('behavior.multiselect');
JHtml::_('bootstrap.tooltip');
PKGrid::script();

JHtml::_('stylesheet', 'lib_projectknife/core.css', false, true, false, false, true);
JHtml::_('stylesheet', 'com_pktasks/tasks.css', false, true, false, false, true);


// Export strings to JS
JText::script('PKGLOBAL_TODO');
JText::script('PKGLOBAL_COMPLETED');
JText::script('PKGLOBAL_OVERDUE');
JText::script('PKGLOBAL_DUE_TODAY');


// JS for progress button
if ($progress_type > 1) {
    JHtml::_('script', 'com_pktasks/bootstrap-slider.js', false, true, false, false, true);
    JHtml::_('stylesheet', 'com_pktasks/bootstrap-slider.css', false, true, false, false, true);

    JFactory::getDocument()->addScriptDeclaration('
        jQuery(document).ready(function()
    	{
    		PKlistTasks.initProgressSlider(".task-progress", "index.php?option=com_pktasks&task=list.progress");
    	});
    ');
}
else {
    JFactory::getDocument()->addScriptDeclaration('
        jQuery(document).ready(function()
    	{
    		PKlistTasks.initProgressButton(".task-progress", "index.php?option=com_pktasks&task=list.progress");
    	});
    ');
}


// JS for priority button
JFactory::getDocument()->addScriptDeclaration('
    jQuery(document).ready(function()
    {
    	PKlistTasks.initPriorityButton(".priority", "index.php?option=com_pktasks&task=list.priority");
    });
');


// JS to disable milestone filter if no project is selected
if ((int) $this->state->get('filter.project_id') === 0) {
    JFactory::getDocument()->addScriptDeclaration('
        jQuery(document).ready(function()
        {
            var filter_ms = jQuery("#mod_filter_milestone_id");

            if (filter_ms.length) {
                filter_ms.attr("disabled", true).trigger("liszt:updated");

                var filter_ms_chzn = jQuery("#mod_filter_milestone_id_chzn");

                if (filter_ms_chzn.length) {
                    filter_ms_chzn.tooltip({"html": true,"container": "body", "title": "' . JText::_('PKGLOBAL_FILTER_LOCKED_PROJECT') . '"});
                }
            }
        });
    ');
}


JFactory::getDocument()->addScriptDeclaration('
    Joomla.orderTable = function()
    {
        jQuery("#filter_order").val(jQuery("#sortTable").val());
        jQuery("#filter_order_sec").val(jQuery("#sortTable_sec").val());

        jQuery("#filter_order_Dir").val(jQuery("#directionTable").val());
        jQuery("#filter_order_sec_Dir").val(jQuery("#directionTable_sec").val());

        Joomla.submitform("", document.getElementById("adminForm"));
    };

    Joomla.submitbutton = function(task)
    {
        if (task == "projects.copy_dialog") {
            jQuery("#copyDialog").modal("show");
        }
        else {
            Joomla.submitform(task, document.getElementById("adminForm"));
        }
    };
');
?>
<div class="grid project-list">
    <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>

    <form name="adminForm" id="adminForm" action="<?php echo JRoute::_(PKtasksHelperRoute::getListRoute()); ?>" method="post">
        <?php
        // Toolbar
        echo $this->toolbar;

        // Items
        echo $this->loadTemplate('items');

        // Copy options
        echo $this->loadTemplate('copy');

        // Bottom pagination
        if ($this->pagination->get('pages.total') > 1) :
            ?>
            <div class="pagination center">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
            <p class="counter center"><?php echo $this->pagination->getPagesCounter(); ?></p>
        <?php endif; ?>
        <input type="hidden" id="boxchecked" name="boxchecked" value="0" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="filter_order" id="filter_order" value="<?php echo $list_order; ?>" />
        <input type="hidden" name="filter_order_sec" id="filter_order_sec" value="<?php echo $list_order_sec; ?>" />
        <input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $list_dir; ?>" />
        <input type="hidden" name="filter_order_sec_Dir" id="filter_order_sec_Dir" value="<?php echo $list_dir_sec; ?>" />
        <?php
            echo JHtml::_('form.token');

            // Render hidden filter fields
            $filters = array();
            $dispatcher->trigger('onProjectknifeDisplayHiddenFilter', array('com_pktasks.list', &$filters));

            if (count($filters)) {
                echo implode("\n", $filters);
            }
        ?>
    </form>
</div>