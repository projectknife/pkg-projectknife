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


$user  = JFactory::getUser();
$count = count($this->items);

$list_order       = $this->escape($this->state->get('list.ordering'));
$viewing_archived = ($this->state->get('filter.published') == 2);
$viewing_trashed  = ($this->state->get('filter.published') == -2);
$viewing_category = ($this->state->get('filter.category_id') > 0);
$sorting_manual   = ($list_order == 'a.ordering' || $list_order == 'ordering');
$db_nulldate      = JFactory::getDbo()->getNullDate();

JHtml::_('actionsdropdown.' . ($viewing_archived ? 'unarchive' : 'archive'), '{cb}', 'projects');
JHtml::_('actionsdropdown.' . ($viewing_trashed ? 'untrash' : 'trash'), '{cb}', 'projects');
$html_actions = JHtml::_('actionsdropdown.render', '{title}');

$txt_cat     = JText::_('JCATEGORY');
$txt_no_cat  = JText::_('PKGLOBAL_UNCATEGORISED');
$txt_not_set = JText::_('PKGLOBAL_UNDEFINED');
$txt_datef   = JText::_('DATE_FORMAT_LC4');
$txt_author  = JText::_('JAUTHOR');
$txt_alias   = JText::_('JFIELD_ALIAS_LABEL');
$txt_order   = JHtml::tooltipText('JORDERINGDISABLED');
$txt_inht    = JHtml::tooltipText('PKGLOBAL_INHERITED_FROM_TASK');
$txt_inhc    = JHtml::tooltipText('COM_PKPROJECTS_INHERITED_FROM_CATEGORY');

for ($i = 0; $i != $count; $i++)
{
    $item = $this->items[$i];

    // Check permissions
    $can_create   = PKUserHelper::authProject('core.create', $item->id);
    $can_edit     = PKUserHelper::authProject('core.edit', $item->id);
    $can_edit_own = (PKUserHelper::authProject('core.edit.own', $item->id) && $item->created_by == $user->id);
    $can_checkin  = ($user->authorise('core.manage', 'com_checkin') || $item->checked_out == $uid || $item->checked_out == 0);
    $can_change   = ($can_edit || $can_edit_own);

    // Actions menu
    if (PKUserHelper::authProject('core.edit.state', $item->id)) {
        $actions = str_replace(array('{cb}', '{title}'), array('cb' . $i, $this->escape($item->title)), $html_actions);
    }
    else {
        $actions = '';
    }

    // Manual order handle
    if ($can_change && $sorting_manual) {
        $input_order = '<input type="text" style="display:none" name="order[]" size="5" '
                     . 'value="' . intval($item->ordering) . '" class="width-20 text-area-order " />';
    }
    else {
        $input_order = '';
    }

    // Title class
    $class_sortable = 'sortable-handler';

    if (!$can_change) {
        $class_sortable .= ' inactive';
    }
    elseif (!$sorting_manual) {
        $class_sortable .= ' inactive tip-top hasTooltip" title="' . $txt_order;
    }

    // Title link
    if ($can_edit || $can_edit_own) {
        $title = '<a href="index.php?option=com_pkprojects&task=project.edit&id=' . intval($item->id) . '">'
               . $this->escape($item->title)
               . ' </a>';
    }
    else {
        $title = '<span>' . $this->escape($item->title) . '</span>';
    }

    // Check-in button
    if ($this->items[$i]->checked_out) {
        $btn_checkin = JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'projects.', $can_checkin) . ' ';
    }
    else {
        $btn_checkin = '';
    }

    // Context info
    if (!empty($this->items[$i]->category_title) && !$viewing_category) {
        $context = $txt_cat . ": " . $this->escape($item->category_title);
    }
    else {
        $context = '';
    }

    // Format start date
    if ($item->start_date_inherit && $item->start_date_task_id > 0) {
        $tip = '<strong>' . $txt_inht . ':</strong><br/>' . JHtmlString::truncate($this->escape($item->start_date_task_title), 32);

        $start_date = '<span class="hasTooltip" title="' . $tip . '" style="cursor:help;">'
                    . JHtml::_('date', $item->start_date, $txt_datef)
                    . ' <i class="icon-info-2"></i>'
                    . '</span>';
    }
    elseif (strcmp($item->start_date, $db_nulldate) !== 0) {
        $start_date = JHtml::_('date', $item->start_date, $txt_datef);
    }
    else {
        $start_date = $txt_not_set;
    }

    // Format due date
    if ($item->due_date_inherit && $item->due_date_task_id > 0) {
        $tip = '<strong>' . $txt_inht . ':</strong><br/>' . JHtmlString::truncate($this->escape($item->due_date_task_title), 32);

        $due_date = '<span class="hasTooltip" title="' . $tip . '" style="cursor:help;">'
                  . JHtml::_('date', $item->due_date, $txt_datef)
                  . ' <i class="icon-info-2 hidden-phone"></i>'
                  . '</span>';
    }
    elseif (strcmp($item->due_date, $db_nulldate) !== 0) {
        $due_date = JHtml::_('date', $item->due_date, $txt_datef);
    }
    else {
        $due_date = $txt_not_set;
    }

    // Format viewing access level
    if ($item->access_inherit) {
        $tip = '<strong>' . $txt_inhc . ':</strong><br/>' . JHtmlString::truncate($this->escape($item->category_title), 32);

        $access = '<span class="hasTooltip" title="' . $tip . '" style="cursor:help;">'
                . $item->access_level
                . ' <i class="icon-info-2 hidden-phone"></i>'
                . '</span>';
    }
    else {
        $access = $this->escape($item->access_level);
    }

    // Format progress bar
    if ($item->progress) {
        $progress = '<div class="progress">'
                  . '    <div class="bar" style="width: ' . $item->progress . '%">'
                  . '        <span class="label label-info pull-right">' . $item->progress . '%</span>'
                  . '   </div>'
                  . '</div>';
    }
    else {
        $progress = '<div class="progress"><span class="label">0%</span></div>';
    }
    ?>
    <tr class="row<?php echo ($i % 2); ?>" sortable-group-id="<?php echo $item->category_id; ?>">
        <td class="order nowrap center hidden-phone">
            <span class="<?php echo $class_sortable; ?>"><i class="icon-menu"></i></span>
            <?php echo $input_order; ?>
        </td>
        <td class="center">
            <?php echo JHtml::_('grid.id', $i, (int) $item->id); ?>
        </td>
        <td class="center">
            <div class="btn-group">
                <?php
                    echo JHtml::_('jgrid.published', (int) $item->published, $i, 'projects.', $can_change, 'cb')
                    . $actions;
                ?>
            </div>
        </td>
        <td class="has-context">
            <div class="pull-left break-word">
                <?php echo $btn_checkin . $title; ?>
                <div class="small muted hidden-phone"><?php echo $context; ?></div>
            </div>
        </td>
        <td class="nowrap hidden-phone">
            <?php echo $progress; ?>
        </td>
        <td class="nowrap">
            <?php echo $start_date; ?>
        </td>
        <td class="nowrap">
            <?php echo $due_date; ?>
        </td>
        <td class="nowrap hidden-phone">
            <?php echo $access; ?>
        </td>
        <td class="small hidden-phone">
            <?php echo $item->id; ?>
        </td>
    </tr>
    <?php
}