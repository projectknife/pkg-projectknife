<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

$list_order     = $this->escape($this->state->get('list.ordering', 'a.actual_due_date'));
$list_dir       = $this->escape($this->state->get('list.direction', 'asc'));
$list_order_sec = $this->escape($this->state->get('list.ordering_sec', 'a.progress'));
$list_dir_sec   = $this->escape($this->state->get('list.direction_sec', 'asc'));
?>
<div id="filter-bar" class="btn-toolbar">
    <div class="filter-search btn-group pull-left">
        <label for="filter_search" class="element-invisible"><?php echo JText::_('JSEARCH_FILTER_LABEL');?></label>
        <input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" />
    </div>
    <div class="btn-group pull-left">
        <button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
            <span class="icon-search"></span>
        </button>
        <button type="button" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();">
            <span class="icon-remove"></span>
        </button>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
        <?php echo $this->pagination->getLimitBox(); ?>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <span class="label hasTooltip" style="cursor: help;" title="<?php echo JText::_('PKGLOBAL_NUM_LIST'); ?>">#</span>
    </div>

    <!-- Secondary order -->
    <div class="btn-group pull-right hidden-phone">
        <label for="directionTable_sec" class="element-invisible"><?php echo JText::_('PKGLOBAL_ORDER_BY');?>:</label>
        <select name="directionTable_sec" id="directionTable_sec" class="input-small" onchange="Joomla.orderTable()">
            <option value=""><?php echo JText::_('PKGLOBAL_ORDER_BY');?>:</option>
            <option value="asc" <?php if (strtolower($list_dir_sec) == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('PKGLOBAL_ORDER_BY_AZ');?></option>
            <option value="desc" <?php if (strtolower($list_dir_sec) == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('PKGLOBAL_ORDER_BY_ZA');?></option>
        </select>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="sortTable_sec" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
        <select name="sortTable_sec" id="sortTable_sec" class="input-medium" onchange="Joomla.orderTable()">
            <option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
            <?php echo JHtml::_('select.options', $this->sort_options, 'value', 'text', $list_order_sec);?>
        </select>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <span class="label hasTooltip" style="cursor: help;" title="<?php echo JText::_('PKGLOBAL_SECONDARY_SORT_AND_ORDER'); ?>"><?php echo JText::_('J2'); ?></span>
    </div>

    <!-- Primary order -->
    <div class="btn-group pull-right hidden-phone">
        <label for="directionTable" class="element-invisible"><?php echo JText::_('PKGLOBAL_ORDER_BY');?>:</label>
        <select name="directionTable" id="directionTable" class="input-small" onchange="Joomla.orderTable()">
            <option value=""><?php echo JText::_('PKGLOBAL_ORDER_BY');?>:</option>
            <option value="asc" <?php if ($list_dir == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('PKGLOBAL_ORDER_BY_AZ');?></option>
            <option value="desc" <?php if ($list_dir == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('PKGLOBAL_ORDER_BY_ZA');?></option>
        </select>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="sortTable" class="element-invisible" ><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
        <select name="sortTable" id="sortTable" class="input-medium hasTooltip" title="<?php echo JText::_('PKGLOBAL_PRIMARY'); ?>" onchange="Joomla.orderTable()">
            <option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
            <?php echo JHtml::_('select.options', $this->sort_options, 'value', 'text', $list_order);?>
        </select>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <span class="label hasTooltip" style="cursor: help;" title="<?php echo JText::_('PKGLOBAL_PRIMARY_SORT_AND_ORDER'); ?>"><?php echo JText::_('J1'); ?></span>
    </div>
</div>
<div class="clearfix"> </div>