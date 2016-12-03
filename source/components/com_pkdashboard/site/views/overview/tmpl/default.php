<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkdashboard
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;

// Load Projectknife plugins
JPluginHelper::importPlugin('projectknife');
$dispatcher = JEventDispatcher::getInstance();


JHtml::_('stylesheet', 'projectknife/lib_projectknife/core.css', false, true, false, false, true);
JHtml::_('behavior.multiselect');
JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');

$view_levels = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
$modules     = JFactory::getDocument()->loadRenderer('modules');
$item        = &$this->item;

$txt_dateformat = JText::_('DATE_FORMAT_LC4');

$show_category   = (int) $this->params->get('show_category', 1);
$show_date       = (int) $this->params->get('show_date', 0);
$show_author     = (int) $this->params->get('show_author', 0);
$show_start      = (int) $this->params->get('show_start', 1);
$show_due        = (int) $this->params->get('show_due', 1);
$show_milestones = (int) $this->params->get('show_milestones', 1);
$show_tasks      = (int) $this->params->get('show_tasks', 1);
$show_tags       = (int) $this->params->get('show_tags', 1);
$show_details    = ($show_category || $show_date || $show_author || $show_start || $show_due || $show_milestones || $show_tasks || $show_tags);

$desc_span = $show_details ? '8' : '12';
$mod_span  = (count(JModuleHelper::getModules('pk-dashboard-right')) > 0 ? '8' : '12');
?>
<?php if ($this->params->get('show_page_heading', 1) == '1') : ?>
    <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
<?php endif; ?>

<form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_pkdashboard&view=overview&Itemid=' . PKRouteHelper::getMenuItemId('active')); ?>" method="post">
    <?php
    // Toolbar
    echo $this->toolbar;
    ?>
    <input type="hidden" name="task" value="" />
    <?php
        echo JHtml::_('form.token');

        // Render hidden filter fields
        $filters = array();
        $dispatcher->trigger('onProjectknifeDisplayHiddenFilter', array('com_pkdashboard.overview', &$filters));

        if (count($filters)) {
            echo implode("\n", $filters);
        }
    ?>
</form>
<?php if ($item->id) : ?>

    <div class="item-page pkdashboard-project-page">
        <?php
        if ($this->params->get('show_title', 1)) {
            echo '<div class="page-header"><h2>' . $this->escape($item->title) . '</h2></div>';
            echo $item->event->afterDisplayTitle;
        }
        ?>
        <div class="row-fluid">
            <div class="span<?php echo $desc_span; ?>">
                <?php
                echo $item->event->beforeDisplayContent;

                if ($this->params->get('show_description', 1)) {
                    echo $item->introtext;

                    if (strlen($item->fulltext)) {
                        ?>
                        <a href="javascript:void();" onclick="jQuery(this).hide();jQuery('#pk-db-fulltext').show();" class="btn" id="pk-db-fulltext-btn">
                            <?php echo JText::_('COM_PKDASHBOARD_SHOW_MORE'); ?>
                        </a>
                        <div id="pk-db-fulltext" style="display: none;">
                            <?php echo $item->fulltext; ?>
                            <a href="javascript:void();" onclick="jQuery('#pk-db-fulltext-btn').show();jQuery('#pk-db-fulltext').hide();" class="btn">
                                <?php echo JText::_('COM_PKDASHBOARD_SHOW_LESS'); ?>
                            </a>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <?php if ($show_details) : ?>
                <div class="span4">
                    <ul class="unstyled pkdetails pkdetails-project hidden-phone">
                        <?php
                        if ($show_category) {
                            echo '<li class="pkdetail-category">'
                            . JText::_('JCATEGORY') . ': '
                            . ($item->category_id ? $item->category_title : JText::_('PKGLOBAL_UNCATEGORISED'))
                            . '</li>';
                        }

                        if ($show_date) {
                            echo '<li class="pkdetail-created">'
                            . JText::_('PKGLOBAL_CREATED') . ': <span class="hasTooltip" title="' . $this->escape(PKDateHelper::relativeDays($item->created)) . '">'
                            . JHtml::_('date', $item->created, $txt_dateformat) . '</span>'
                            . '</li>';
                        }

                        if ($show_author) {
                            echo '<li class="pkdetail-created_by">'
                            . JText::_('PKGLOBAL_CREATED_BY_LABEL') . ': '
                            . $this->escape($item->author_name)
                            . '</li>';
                        }

                        if ($show_start) {
                            echo '<li class="pkdetail-start">'
                            . JText::_('PKGLOBAL_START_DATE'). ': <span class="hasTooltip" title="' . $this->escape(PKDateHelper::relativeDays($item->start_date)) . '">'
                            . JHtml::_('date', $item->start_date, $txt_dateformat) . '</span>'
                            . '</li>';
                        }

                        if ($show_due) {
                            echo '<li class="pkdetail-due">'
                            . JText::_('PKGLOBAL_DUE_DATE'). ': <span class="hasTooltip" title="' . $this->escape(PKDateHelper::relativeDays($item->due_date)) . '">'
                            . JHtml::_('date', $item->due_date, $txt_dateformat) . '</span>'
                            . '</li>';
                        }

                        if ($show_milestones) {
                            echo '<li class="pkdetail-milestones">'
                            . JText::_('COM_PKMILESTONES_SUBMENU_MILESTONES'). ': '
                            . ($item->milestones_count ? $item->milestones_completed . '/' . $item->milestones_count : '0')
                            . '</li>';
                        }

                        if ($show_tasks) {
                            echo '<li class="pkdetail-tasks">'
                            . JText::_('COM_PKTASKS_SUBMENU_TASKS'). ': '
                            . ($item->tasks_count ? $item->tasks_completed . '/' . $item->tasks_count : '0')
                            . '</li>';
                        }

                        if ($show_tags) {
                            $tags = '';

                            foreach ($this->item->tags->itemTags AS $ti => $tag)
                            {
                                if (!in_array($tag->access, $view_levels)) {
                                    continue;
                                }

                                $tagParams  = new Registry($tag->params);
                                $link_class = $tagParams->get('tag_link_class', 'label label-info');

                                $tags .= '<li class="tag-2 tag-list0" itemprop="keywords"><span class="' . $link_class . '">' . $this->escape($tag->title) . '</span></li>';
                            }

                            if ($tags) {
                                echo '<li>' . JText::_('JTAG') . ': <ul class="tags inline unstyled">' . $tags . '</ul></li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <?php echo $item->event->afterDisplayContent; ?>

        <!-- Begin Dashboard Modules -->
        <?php if (count(JModuleHelper::getModules('pk-dashboard-top'))) : ?>
            <div class="row-fluid">
            	<div class="span12">
            		<?php echo $modules->render('pk-dashboard-top', array('style' => 'xhtml'), null); ?>
            	</div>
            </div>
        <?php
        endif;

        if (count(JModuleHelper::getModules('pk-dashboard-left')) || count(JModuleHelper::getModules('pk-dashboard-right'))) : ?>
            <div class="row-fluid">
            	<div class="span<?php echo $mod_span; ?>">
            		<?php echo $modules->render('pk-dashboard-left', array('style' => 'xhtml'), null); ?>
            	</div>
                <?php if (count(JModuleHelper::getModules('pk-dashboard-right'))) : ?>
                    <div class="span4">
                        <div class="hidden-phone">
                		  <?php echo $modules->render('pk-dashboard-right', array('style' => 'xhtml'), null); ?>
                        </div>
                	</div>
                <?php endif; ?>
            </div>
        <?php
        endif;

        if (count(JModuleHelper::getModules('pk-dashboard-bottom'))) : ?>
            <div class="row-fluid">
            	<div class="span12">
            		<?php echo $modules->render('pk-dashboard-bottom', array('style' => 'xhtml'), null); ?>
            	</div>
            </div>
        <?php endif; ?>
        <!-- End Dashboard Modules -->
    </div>
<?php endif; ?>


