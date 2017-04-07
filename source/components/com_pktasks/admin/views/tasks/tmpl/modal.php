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


JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$list_order     = $this->escape($this->state->get('list.ordering', 'a.due_date'));
$list_dir       = $this->escape($this->state->get('list.direction', 'asc'));
$list_order_sec = $this->escape($this->state->get('list.ordering_sec', 'a.title'));
$list_dir_sec   = $this->escape($this->state->get('list.direction_sec', 'asc'));

JHtml::_('script', 'lib_projectknife/form.js', false, true, false, false, true);
JHtml::_('stylesheet', 'com_pktasks/tasks.css', false, true, false, false, true);
?>
<form action="<?php echo JRoute::_('index.php?option=com_pktasks&view=tasks'); ?>" method="post" name="adminForm" id="adminForm">
    <?php
    // Sidebar
    echo '<div id="j-main-container">';
    echo $this->loadTemplate('filter');

    if (empty($this->items)) :
        echo '<div class="alert alert-no-items">' . JText::_('JGLOBAL_NO_MATCHING_RESULTS') . '</div>';
    else :
        ?>
        <table class="table table-striped" id="taskList">
            <thead>
                <th>
                    <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $list_dir, $list_order); ?>
                </th>
                <th width="12%" class="nowrap center">
                    <?php echo JHtml::_('grid.sort', 'PKGLOBAL_PROGRESS', 'a.progress', $list_dir, $list_order); ?>
                </th>
                <th width="12%" class="nowrap hidden-phone center">
                    <?php echo JHtml::_('grid.sort', 'PKGLOBAL_START_DATE', 'a.start_date', $list_dir, $list_order); ?>
                </th>
                <th width="10%" class="nowrap">
                    <?php echo JHtml::_('grid.sort', 'PKGLOBAL_DUE_DATE', 'a.due_date', $list_dir, $list_order); ?>
                </th>
                <th width="15%" class="nowrap hidden-phone">
                    <?php echo JHtml::_('grid.sort',  'JGRID_HEADING_ACCESS', 'access_level', $list_dir, $list_order); ?>
                </th>
                <th width="5%" class="nowrap hidden-phone">
                    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $list_dir, $list_order); ?>
                </th>
            </thead>
            <tbody>
                <?php
                echo $this->loadTemplate('items');
                ?>
            </tbody>
        </table>
        <?php
        endif;
        echo $this->pagination->getListFooter();
        echo $this->loadTemplate('copy');
        ?>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" id="filter_order" name="filter_order" value="<?php echo $list_order; ?>" />
        <input type="hidden" id="filter_order_sec" name="filter_order_sec" value="<?php echo $list_order_sec; ?>" />
        <input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo $list_dir; ?>" />
        <input type="hidden" id="filter_order_sec_Dir" name="filter_order_sec_Dir" value="<?php echo $list_dir_sec; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>