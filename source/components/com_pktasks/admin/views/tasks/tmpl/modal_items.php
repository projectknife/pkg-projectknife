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


$user   = JFactory::getUser();
$count  = count($this->items);
$app    = JFactory::getApplication();
$params = JComponentHelper::getParams('com_pktasks');
$progress_type = (int) $params->get('progress_type', 1);

$list_order       = $this->escape($this->state->get('list.ordering'));
$viewing_archived = ($this->state->get('filter.published') == 2);
$viewing_trashed  = ($this->state->get('filter.published') == -2);
$viewing_project  = ($this->state->get('filter.project_id') > 0);
$viewing_ms       = ($this->state->get('filter.milestone_id') > 0);
$sorting_manual   = ($list_order == 'a.ordering' || $list_order == 'ordering');
$function         = $app->input->getCmd('function', 'jSelectTask');
$db_nulldate      = JFactory::getDbo()->getNullDate();
$user_tz          = JFactory::getUser()->getParam('timezone', JFactory::getConfig()->get('offset'));

$date = JFactory::getDate('now', 'UTC');
$date->setTimeZone(new DateTimeZone($user_tz));

JHtml::_('actionsdropdown.' . ($viewing_archived ? 'unarchive' : 'archive'), '{cb}', 'tasks');
JHtml::_('actionsdropdown.' . ($viewing_trashed ? 'untrash' : 'trash'), '{cb}', 'tasks');
$html_actions = JHtml::_('actionsdropdown.render', '{title}');

$txt_edit      = JText::_('JACTION_EDIT');
$txt_cat       = JText::_('JCATEGORY');
$txt_no_cat    = JText::_('PKGLOBAL_UNCATEGORISED');
$txt_not_set   = JText::_('PKGLOBAL_NOT_SET');
$txt_datef     = JText::_('DATE_FORMAT_LC4');
$txt_author    = JText::_('JAUTHOR');
$txt_alias     = JText::_('JFIELD_ALIAS_LABEL');
$txt_order     = JHtml::tooltipText('JORDERINGDISABLED');
$txt_project   = JText::_('COM_PKPROJECTS_PROJECT');
$txt_todo      = JText::_('PKGLOBAL_TODO');
$txt_done      = JText::_('PKGLOBAL_COMPLETED');
$txt_overdue   = JText::_('PKGLOBAL_OVERDUE');
$txt_due_today = JText::_('PKGLOBAL_DUE_TODAY');
$txt_milestone = JText::_('COM_PKMILESTONES_MILESTONE');
$txt_inh_ms    = JText::_('COM_PKTASKS_ACCESS_INHERITED_MILESTONE');
$txt_inh_proj  = JText::_('COM_PKTASKS_ACCESS_INHERITED_PROJECT');

$today = floor(strtotime($date->calendar($txt_datef, true)) / 86400) * 86400;

for ($i = 0; $i != $count; $i++)
{
    $item = $this->items[$i];

    // Context info
    $context = '';

    if (!empty($item->project_title) && !$viewing_project) {
        $context .= $txt_project . ': ' . $this->escape($item->project_title);
    }

    if (!empty($item->milestone_title) && !$viewing_ms) {
        if (!empty($context)) {
            $context .= '&nbsp;';
        }

        $context .= $txt_milestone . ': ' . $this->escape($item->milestone_title);
    }

    $priority = '<i id="prio-' . $item->id . '"'
              . 'class="icon-flag priority prio-' . intval($item->priority) . '"'
              . 'data-id="' . $item->id. '" data-value="' . intval($item->priority) . '">'
              . '</i>';

    // Format start date
    $start_date = $txt_not_set;

    if (strcmp($item->start_date, $db_nulldate) !== 0) {
        $start_date = JHtml::_('date', $item->start_date, $txt_datef);
    }

    // Format due date
    $due_date = $txt_not_set;

    if (strcmp($item->due_date, $db_nulldate) !== 0) {
        $due_date = JHtml::_('date', $item->due_date, $txt_datef);
    }

    // Format viewing access level
    $access = $this->escape($item->access_level);

    if ($item->access_inherit) {
        if ($item->milestone_id) {
            $txt_access_inherit = '<strong>' . $txt_inh_ms . ':</strong><br/>' . $this->escape($item->milestone_title);
        }
        else {
            $txt_access_inherit = '<strong>' . $txt_inh_proj . ':</strong><br/>' . $this->escape($item->project_title);
        }

        $access = '<span class="hasTooltip" title="' . $txt_access_inherit . '">'
                . $this->escape($item->access_level)
                . ' <i class="icon-info-2"></i>'
                . '</span>';
    }

    // Format progress bar
    if ($progress_type == 1) {
        $progress = '';
    }
    else {
        // Progress percentage
        $progress = '';
    }
    ?>
    <tr class="row<?php echo ($i % 2); ?>" sortable-group-id="<?php echo $item->project_id; ?>">
        <td class="has-context">
            <div class="pull-left break-word">
                <a href="javascript:void(0);" onclick="if (window.parent) window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>', '<?php echo $this->escape($item->project_id); ?>');">
                    <?php echo $this->escape($item->title); ?>
                </a>
                <div class="small hidden-phone muted"><?php echo $context; ?></div>
            </div>
        </td>
        <td class="nowrap">
            <?php echo $progress; ?>
        </td>
        <td class="nowrap hidden-phone center">
            <?php echo $start_date; ?>
        </td>
        <td class="nowrap">
            <?php echo $due_date; ?>
        </td>
        <td class="nowrap">
            <?php echo $access; ?>
        </td>
        <td class="small hidden-phone">
            <?php echo $item->id; ?>
        </td>
    </tr>
    <?php
}