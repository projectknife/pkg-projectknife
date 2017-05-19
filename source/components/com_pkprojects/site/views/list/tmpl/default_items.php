<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


// Load language strings
$txt_cat      = JText::_('JCATEGORY');
$txt_no_cat   = JText::_('PKGLOBAL_UNCATEGORISED');
$txt_ms       = JText::_('COM_PKMILESTONES_SUBMENU_MILESTONES');
$txt_tasks    = JText::_('COM_PKTASKS_SUBMENU_TASKS');
$txt_edit     = JText::_('JACTION_EDIT');
$txt_due      = JText::_('PKGLOBAL_DUE');
$txt_date     = JText::_('JDATE');
$txt_started  = JText::_('PKGLOBAL_STARTED');
$txt_starting = JText::_('PKGLOBAL_STARTING');
$txt_sdate    = JText::_('PKGLOBAL_START_DATE');
$txt_ddate    = JText::_('PKGLOBAL_DUE_DATE');
$txt_tags     = JText::_('JTAG');


// Determine heading and item date formats
$date_dynamic = $this->params->get('date_dynamic', 1);
$date_default = $this->params->get('date_default', 'due_date');
$date_format  = $this->params->get('date_format', JText::_('DATE_FORMAT_LC4'));


$date_pos_d = strpos(strtolower($date_format), 'd');
$date_pos_m = strpos(strtolower($date_format), 'm');
$date_pos_y = strpos(strtolower($date_format), 'y');

if ($date_pos_d === false) $date_pos_d = -1;
if ($date_pos_m === false) $date_pos_m = -1;
if ($date_pos_y === false) $date_pos_y = -1;

$date_heading_format = ($date_pos_m > $date_pos_y) ? 'Y/m' : 'm/Y';
$date_item_format    = ($date_pos_m > $date_pos_d) ? 'd M' : 'M d';


// Determine heading
$heading_date_fields   = array('start_date', 'due_date', 'created');
$heading_string_fields = array('author_name', 'category_title');

$list_order      = str_replace('a.', '', $this->escape($this->state->get('list.ordering', 'a.due_date')));
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
$url_list   = 'index.php?option=com_pkprojects&view=list&Itemid=' . PKRouteHelper::getMenuItemId('active');
$url_ms     = 'index.php?option=com_pkmilestones&view=list&Itemid=' . PKRouteHelper::getMenuItemId('com_pkmilestones', 'list');
$url_tasks  = 'index.php?option=com_pktasks&view=list&Itemid=' . PKRouteHelper::getMenuItemId('com_pktasks', 'list');
$url_return = base64_encode($url_list);



// Misc
$count          = count($this->items);
$user           = JFactory::getUser();
$db_nulldate    = JFactory::getDbo()->getNullDate();
$time_now       = strtotime(JHtml::_('date'));
$view_levels    = JAccess::getAuthorisedViewLevels($user->get('id'));
$sorting_manual = ($list_order == 'ordering');


// Menu item id's
$itemid_active   = PKRouteHelper::getMenuItemId('active');
$itemid_overview = PKRouteHelper::getMenuItemId('com_pkdashboard', 'overview', array('id' => 0));
$itemid_form     = PKRouteHelper::getMenuItemId('com_pkprojects', 'form');


// Enable popups for date buttons
JFactory::getDocument()->addScriptDeclaration('
    jQuery(document).ready(function()
	{
		jQuery(".pk-date-popup").popover({"placement":"right"});
	});
');


for ($i = 0; $i != $count; $i++)
{
    $item = $this->items[$i];

    // Check permissions
    $can_edit     = PKUserHelper::authProject('core.edit', $item->id);
    $can_edit_own = (PKUserHelper::authProject('core.edit.own', $item->id) && $item->created_by == $user->id);
    $can_checkin  = ($user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0);
    $can_change   = ($can_edit || $can_edit_own);


    // Get the correct menu item id for this project
    $itemid = PKRouteHelper::getMenuItemId('com_pkdashboard', 'overview', array('id' => $item->id));

    if (!$itemid) {
        $itemid = $itemid_overview;
    }


    // Format title
    $link   = 'index.php?option=com_pkdashboard&view=overview&id=' . $item->slug . '&Itemid=' . $itemid . '&return=' . $url_return;
    $title  = '<a href="' . JRoute::_($link) . '" class="item-title">' . $this->escape($item->title) . '</a>';


    // Grid select button
    $btn_select = PKGrid::selectItem($i, $item->id) . ' ';


    // Edit button
    if ($item->checked_out) {
        $btn_edit = JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'list.', $can_checkin);
        $btn_edit = str_replace('btn-micro', 'btn-small btn-link', $btn_edit);
    }
    elseif ($can_change) {
        $link_edit = 'index.php?option=com_pkprojects&task=form.edit&id=' . $item->slug . '&Itemid=' . $itemid_form . '&return=' . $url_return;
        $btn_edit = '<a class="btn btn-small btn-link hasTooltip" title="' . $txt_edit . '" href="'
                  . JRoute::_($link_edit)  . '">'
                  . '<span class="icon-edit"></span></a>';
    }
    else {
        $btn_edit = '';
    }


    // Manual order button
    if ($sorting_manual) {
        if ($can_change) {
            $btn_order = '<span class="btn btn-small btn-link sortable-handler" style="cursor: move;"><span class="icon-menu"></span></span>'
                       . '<input type="text" style="display:none" name="order[]" size="5" value="' . intval($item->ordering) . '"/>';
        }
        else {
            $btn_order = '<span class="btn btn-small btn-link muted"><span class="icon-menu muted"></span></span>';
        }
    }
    else {
        $btn_order = '';
    }


    // Format category
    $category = $txt_cat . ': <a href="' . JRoute::_($url_list . '&filter_category_id=' . $item->category_id) . '">'
              . $this->escape(($item->category_title == '' ? $txt_no_cat : $item->category_title)) . '</a>';


    // Milestones info
    if (!$item->milestones_count) {
        $milestones = '0';
    }
    else {
        $milestones = $item->milestones_completed . '/' . $item->milestones_count;
    }


    // Tasks info
    if (!$item->tasks_count) {
        $tasks = '0';
    }
    else {
        $tasks = $item->tasks_completed . '/' . $item->tasks_count;
    }


    // Progress bar
    if ($item->progress) {
        $progress_bar = '<div class="progress">'
                      . '    <div class="bar" style="width: ' . $item->progress . '%">'
                      . '        <span class="label label-info pull-right">' . $item->progress . '%</span>'
                      . '   </div>'
                      . '</div>';
    }
    else {
        $progress_bar = '<div class="progress"><span class="label">0%</span></div>';
    }


    // Format tags
    if ($item->tags_count) {
        $tags = '';

        foreach ($item->tags->itemTags AS $ti => $tag)
        {
            if (!in_array($tag->access, $view_levels)) continue;

            $tag_params = new Registry($tag->params);
            $link_class = $tag_params->get('tag_link_class', 'label label-info');

            $tags .= '<a href="' . JRoute::_($url_list  . '&filter_tag_id=' . $tag->id) . '" class="' . $link_class . '">'
                   . $this->escape($tag->title)
                   . '</a> ';
        }

        if ($tags != '') {
            $tags = '<i class="icon-bookmark muted hasTooltip pk-minfo" title="' . $txt_tags . '"></i> ' . $tags;
        }
    }
    else {
        $tags = '';
    }


    // Format dates
    $item_dates = array();
    $item_dates['start_date'] = ($item->start_date != $db_nulldate) ? JHtml::_('date', $item->start_date, $date_format) : '-';
    $item_dates['due_date']   = ($item->due_date != $db_nulldate)   ? JHtml::_('date', $item->due_date, $date_format)   : '-';
    $item_dates['created']    = ($item->created != $db_nulldate)    ? JHtml::_('date', $item->created, $date_format)    : '-';


    // Format display date
    $item_date = $item_dates[$date_default];

    if ($item_date != '-') {
        $item_date = JHtml::_('date', $item->$date_default, $date_item_format);
    }

    if ($date_default == 'due_date') {
        $item_date_title = $txt_due;
    }
    elseif ($date_default == 'start_date') {
        if ($item_date != '-') {
            if ($time_now > strtotime($item_date)) {
                $item_date_title = $txt_started;
            }
            else {
                $item_date_title = $txt_starting;
            }
        }
        else {
            $item_date_title = $txt_started;
        }
    }
    else {
        $item_date_title = $txt_date;
    }


    // Display date tooltip
    if ($item_date != '-') {
        $date_tt_class = ' hasTooltip';
        $date_tt_title = ' title="' . $this->escape(PKDateHelper::relativeDays($item_dates[$date_default])) . '"';
    }
    else {
        $date_tt_class = '';
        $date_tt_title = '';
    }


    // Highlight css classes
    $highlight_ms     = $item->milestones_count == '0' ? ' muted' : '';
    $highlight_tasks  = $item->tasks_count == '0'      ? ' muted' : '';
    $highlight_date   = '';
    $highlight_ddate  = '';
    $highlight_addate = '';


    if ($item->tasks_count > 0 && $item->tasks_count > $item->tasks_completed) {
        if (strtotime($item_dates['due_date']) < $time_now) {
            $highlight_ddate = ' label label-important pk-nopad';
        }

        if ($date_default == 'due_date') {
            $highlight_date = $highlight_ddate;
        }
    }


    // Date popover info
    $class_sdate  = ($date_default == 'start_date' ? ' class="label"' : '');
    $class_ddate  = ($date_default == 'due_date'   ? ' class="label' . str_replace('pk-nopad', '', $highlight_ddate) . '"' : ' ');

    $date_popover = '<div class="row-fluid">'
                  . '<div class="span6">'
                  . '<p><strong>' . $txt_sdate . ':</strong> <span' . $class_sdate . '>' . $item_dates['start_date'] . '</span></p>'
                  . '</div>'
                  . '<div class="span6">'
                  . '<p><strong>' . $txt_ddate . ':</strong> <span' . $class_ddate . '>' . $item_dates['due_date'] . '</span></p>'
                  . '</div>'
                  . '</div>';

    $date_popover = htmlspecialchars($date_popover);

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

    // Comment count
    $comments = '';

    if (property_exists($item, 'comment_count')) {
        if ($item->comment_count > 0) {
            $comments = ' <span class="label">'
                      . $item->comment_count
                      . ' <i class="icon-comment"></i></span>';
        }
    }
    ?>
    <div class="row-fluid projectItem" id="project-<?php echo $item->id; ?>" sortable-group-id="<?php echo $item->category_id; ?>">
        <div class="span12">
            <div class="row-fluid">
                <div class="span12">
                    <h3 class="item-title">
                        <?php echo $btn_select . $btn_order . $title . $comments;?>
                        <div class="pull-right">
                            <?php echo $btn_edit; ?>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span12">
                    <?php echo $progress_bar; ?>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span2">
                    <div class="thumbnail center">
                        <a class="btn btn-link span12 pk-date-popup" href="javascript:void();" data-toggle="popover" data-content="<?php echo $date_popover; ?>">
                            <?php echo $item_date_title; ?>
                        </a>
                        <div class="clearfix"></div>
                        <div class="pk-dsp-block<?php echo $date_tt_class . $highlight_date; ?>"<?php echo $date_tt_title; ?>>
                            <span class="pk-txt-large"><?php echo $item_date; ?></span>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class="thumbnail center">
                        <a class="btn btn-link span12" href="<?php echo JRoute::_($url_ms . '&filter_project_id=' . $item->id); ?>">
                            <?php echo $txt_ms; ?>
                        </a>
                        <div class="clearfix"></div>
                        <div class="pk-dsp-block">
                            <span class="pk-txt-large<?php echo $highlight_ms; ?>"><?php echo $milestones; ?></span>
                        </div>
                    </div>
                </div>
                <div class="span2">
                    <div class="thumbnail center">
                        <a class="btn btn-link span12" href="<?php echo JRoute::_($url_tasks . '&filter_project_id=' . $item->id); ?>">
                            <?php echo $txt_tasks; ?>
                        </a>
                        <div class="clearfix"></div>
                        <div class="pk-dsp-block">
                            <span class="pk-txt-large<?php echo $highlight_tasks; ?>"><?php echo $tasks; ?></span>
                        </div>
                    </div>
                </div>
                <div class="span6">
                    <p><?php echo $category; ?></p>
                    <?php if ($tags) : ?>
                        <p><?php echo $tags; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
