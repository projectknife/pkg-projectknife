<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


// Load language strings
$txt_proj       = JText::_('COM_PKPROJECTS_PROJECT');
$txt_no_proj    = JText::_('PKGLOBAL_UNCATEGORISED');
$txt_ms         = JText::_('COM_PKMILESTONES_SUBMENU_MILESTONES');
$txt_datef      = JText::_('DATE_FORMAT_LC4');
$txt_edit       = JText::_('JACTION_EDIT');
$txt_due        = JText::_('PKGLOBAL_DUE');
$txt_started    = JText::_('PKGLOBAL_STARTED');
$txt_starting   = JText::_('PKGLOBAL_STARTING');
$txt_todo       = JText::_('PKGLOBAL_TODO');
$txt_done       = JText::_('PKGLOBAL_COMPLETED');
$txt_overdue    = JText::_('PKGLOBAL_OVERDUE');
$txt_due_today  = JText::_('PKGLOBAL_DUE_TODAY');
$txt_priority   = JText::_('COM_PKTASKS_PRIORITY');
$txt_priority_n = JText::_('COM_PKTASKS_PRIORITY_NORMAL');
$txt_priority_h = JText::_('COM_PKTASKS_PRIORITY_HIGH');
$txt_unassigned = JText::_('COM_PKTASKS_UNASSIGNED');
$txt_project    = JText::_('COM_PKPROJECTS_PROJECT');
$txt_milestone  = JText::_('COM_PKMILESTONES_MILESTONE');
$txt_tags       = JText::_('JTAG');


// Determine heading and item date formats
$date_dynamic = $this->params->get('date_dynamic', 1);
$date_default = $this->params->get('date_default', 'actual_due_date');
$date_format  = $this->params->get('date_format', JText::_('DATE_FORMAT_LC4'));


$date_pos_d = strpos(strtolower($date_format), 'd');
$date_pos_m = strpos(strtolower($date_format), 'm');
$date_pos_y = strpos(strtolower($date_format), 'y');

if ($date_pos_d === false) $date_pos_d = -1;
if ($date_pos_m === false) $date_pos_m = -1;
if ($date_pos_y === false) $date_pos_y = -1;

$date_heading_format = ($date_pos_m > $date_pos_y) ? 'Y/m' : 'm/Y';
$date_item_format    = ($date_pos_m > $date_pos_d) ? 'd M' : 'M d';
$date_item_large     = ($date_pos_m > $date_pos_d) ? 'd'   : 'M';
$date_item_medium    = ($date_pos_m > $date_pos_d) ? 'M'   : 'd';


// Determine heading
$heading_date_fields   = array('start_date', 'due_date', 'created');
$heading_string_fields = array('author_name', 'project_title');

$list_order      = str_replace('a.', '', $this->escape($this->state->get('list.ordering', 'a.actual_due_date')));
$heading_show    = false;
$heading_by_date = false;
$heading_prefix  = '';
$heading_string  = '';
$heading_new     = '';

if (in_array($list_order, $heading_date_fields)) {
    $heading_show    = true;
    $heading_by_date = true;

    if ($list_order == 'due_date') {
        $heading_prefix = JText::_('PKGLOBAL_DUE');
    }
    elseif ($list_order == 'start_date') {
        $heading_prefix = JText::_('PKGLOBAL_STARTING');
    }
    else {
        $heading_prefix = JText::_('PKGLOBAL_CREATED');
    }
}
elseif (in_array($list_order, $heading_string_fields)) {
    $heading_show = true;
}


// Determine the primary date to display in the list
if ($date_dynamic && $heading_by_date) {
    $date_default = $list_order;
}


// Setup URL related vars
$url_list   = 'index.php?option=com_pktasks&view=list&Itemid=' . PKRouteHelper::getMenuItemId('active');
$url_return = base64_encode($url_list);


// Misc
$count         = count($this->items);
$user          = JFactory::getUser();
$doc           = JFactory::getDocument();
$db_nulldate   = JFactory::getDbo()->getNullDate();
$time_now      = strtotime(JHtml::_('date'));
$today         = floor($time_now / 86400) * 86400;
$view_levels   = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
$params        = JComponentHelper::getParams('com_pktasks');
$progress_type = (int) $params->get('progress_type', 1);

$filter_project = (int) $this->state->get('filter.project_id');
$filter_ms      = (int) $this->state->get('filter.milestone_id');

// JS for priority button
$doc->addScriptDeclaration('
    jQuery(document).ready(function()
    {
    	PKlistTasks.initPriorityAfterUpdate = function(el, state)
        {
            if (state == 1) {
                el.text("' . $txt_priority . ': ' . $txt_priority_h . '");
                el.addClass("label-important");
            }
            else {
                el.text("' . $txt_priority . ': ' . $txt_priority_n . '");
                el.removeClass("label-important");
            }
        }
    });
');


for ($i = 0; $i != $count; $i++)
{
    $item = $this->items[$i];

    // Assignees
    $assignees    = array();
    $assignee_pks = array();

    if ($item->assignee_count) {
        foreach ($item->assignees AS $assignee)
        {
            $assignees[]    = $this->escape($assignee->assignee_name);
            $assignee_pks[] = $assignee->id;
        }

        $assignees = implode(', ', $assignees);
    }
    else {
        $assignees = $txt_unassigned;
    }

    // Check permissions
    $can_edit = PKUserHelper::authProject('task.edit', $item->project_id);

    if (!$can_edit) {
        $can_edit = (PKUserHelper::authProject('task.edit.own', $item->project_id) && $item->created_by == $user->id);
    }

    $can_edit_state = PKUserHelper::authProject('task.edit.state', $item->project_id);

    if (!$can_edit_state) {
        $can_edit_state = (PKUserHelper::authProject('task.edit.own.state', $item->project_id) && $item->created_by == $user->id);
    }

    $can_edit_progress = PKUserHelper::authProject('task.edit.progress', $item->project_id);

    if (!$can_edit_progress) {
        $can_edit_progress = (PKUserHelper::authProject('task.edit.own.progress', $item->project_id) && $item->created_by == $user->id);

        if (!$can_edit_progress) {
            $can_edit_progress = (PKUserHelper::authProject('task.edit.assigned.progress', $item->project_id) && in_array($user->id, $assignee_pks));
        }
    }

    // Check edit progress permission based on predecessor progress
    if ($can_edit_progress && !$item->can_progress) {
        $can_edit_progress = false;
    }

    $can_checkin = ($user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0);


    // Format title
    $link  = PKtasksHelperRoute::getItemRoute($item->slug, $item->project_slug). '&return=' . $url_return;
    $title = '<a href="' . JRoute::_($link) . '" class="item-title">' . $this->escape($item->title) . '</a>';


    // Grid select button
    $btn_select = PKGrid::selectItem($i, $item->id);


    // Edit button
    if ($item->checked_out) {
        $btn_edit = JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'list.', $can_checkin);
        $btn_edit = str_replace('btn-micro', 'btn-small btn-link', $btn_edit);
    }
    elseif ($can_edit) {
        $btn_edit = '<a class="btn btn-small btn-link hasTooltip" title="' . $txt_edit . '" href="'
                  . JRoute::_(PKTasksHelperRoute::getFormRoute($item->slug) . '&return=' . $url_return)  . '">'
                  . '<span class="icon-edit"></span></a>';
    }
    else {
        $btn_edit = '';
    }


    // Format Project
    if ($filter_project) {
        $project = '';
    }
    else {
        $project = '<span class="label">' . $txt_project . ': ' . $this->escape($item->project_title) . '</span> ';
    }


    // Format Milestone
    if ($filter_ms || $item->milestone_title == '') {
        $milestone = '';
    }
    else {
        $milestone = '<span class="label">' . $txt_milestone . ': ' . $this->escape($item->milestone_title) . '</span> ';
    }


    // Priority
    if ($item->priority == '1') {
        $prio_txt   = $txt_priority . ': ' . $txt_priority_h;
        $prio_class = ' label-important' . ($can_edit_state ? '' : ' disabled');
    }
    else {
        $prio_txt   = $txt_priority . ': ' . $txt_priority_n;
        $prio_class = $can_edit_state ? '' : ' disabled';
    }

    $priority = '<span id="prio-' . $item->id . '"'
              . 'class="label priority prio-' . $item->priority . $prio_class . '"'
              . 'data-id="' . $item->id. '" data-value="' . intval($item->priority) . '">'
              . $prio_txt
              . '</span>';


    // Format progress
    if ($progress_type == 1) {
        $due_date = JHtml::_('date', $item->due_date, 'd-m-Y');
        $due_time = floor(strtotime($due_date) / 86400) * 86400;

        // Simple button
        if ($item->progress == 100) {
            $progress_class = 'success';
            $progress_text  = $txt_done;
        }
        else {
            if ($due_time > $today || $due_time == 0) {
                $progress_class = 'warning';
                $progress_text  = $txt_todo;
            }
            elseif ($due_time == $today) {
                $progress_class = 'danger';
                $progress_text  = $txt_due_today;
            }
            else {
                $progress_class = 'danger';
                $progress_text  = $txt_overdue;
            }
        }

        if (!$can_edit_progress) {
            $progress_class .= ' disabled';
        }

        $progress = '<a id="progress-' . $i . '" class="span12 task-progress btn btn-' . $progress_class .  '"'
                  . ' data-id="' . $item->id . '" data-progress="' . $item->progress . '" data-due="' . $due_time .  '">'
                  . $progress_text
                  . '</a>';
    }
    else {
        // Slider
        if (!$can_edit_progress) {
            $doc->addScriptDeclaration('jQuery(document).ready(function(){jQuery("#progress-' . $i . '").slider("disable");});');
        }

        $progress = '<input id="progress-' . $i . '" data-slider-id="slider-' . $i . '" '
                  . 'class="task-progress" data-slider-ticks="[0, 50, 100]" '
                  . 'type="text" data-slider-min="0" data-slider-max="100" '
                  . 'data-progress="' . $item->progress . '" data-id="' . $item->id . '" '
                  . 'data-slider-step="' . $progress_type . '" data-slider-value="' . $item->progress . '"/>';
    }


    // Format display date
    $item_date = '<div class="hasTooltip" title="' . $this->escape(PKDateHelper::relativeDays(JHtml::_('date',  $item->$date_default))) . '">'
               . '<div class="pk-txt-large">' . JHtml::_('date',  $item->$date_default, $date_item_large) . '</div>'
			   . '<div class="pk-txt-medium">' . JHtml::_('date', $item->$date_default, $date_item_medium) . '</div>'
               . '</div>';


    // Tags
    if ($item->tags_count) {
        $tags = '';

        foreach ($item->tags->itemTags AS $ti => $tag)
        {
            if (!in_array($tag->access, $view_levels)) {
                continue;
            }

            $tagParams  = new Registry($tag->params);
            $link_class = $tagParams->get('tag_link_class', 'label label-info');

            $tags .= '<a href="' . JRoute::_($url_list  . '&filter_tag_id=' . $tag->id) . '" class="' . $link_class . '">'
                   . $this->escape($tag->title)
                   . '</a> ';
        }
    }
    else {
        $tags = '';
    }


    // Heading group
    if ($heading_show) {
        if ($heading_by_date) {
            $heading_new = $heading_prefix . ' ' . JHtml::_('date', $item->$list_order, $date_heading_format);
        }
        else {
            $heading_new = $item->$list_order;
        }

        if ($heading_new != $heading_string) {
            $heading_string = $heading_new;

            echo '<h3>' . $this->escape($heading_string) . '</h3><hr/>';
        }
    }
    ?>
    <div class="row-fluid">
        <div class="span2 hidden-phone">
            <div class="thumbnail center">
                <?php echo $progress; ?>
                <div class="clearfix"></div>
                <p></p>
                <div class="pk-dsp-block">
                    <?php echo $item_date; ?>
                </div>
            </div>
        </div>
        <div class="span10">
            <div class="row-fluid">
                <div class="span12">
                    <h3 class="item-title" style="margin-top: 8px !important;">
                        <?php echo $btn_select  . ' ' . $title; ?>
                        <div class="pull-right"><?php echo $btn_edit; ?></div>
                    </h3>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <p><i class="icon-bookmark muted hasTooltip pk-minfo" title="<?php echo $txt_tags; ?>"></i> <?php echo $project . $milestone . $priority . ' ' . $tags; ?></p>
                    <p><i class="icon-user muted hasTooltip pk-minfo"></i> <?php echo $assignees; ?></p>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <p></p>
    </div>
    <?php
}
