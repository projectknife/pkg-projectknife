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


$item = $this->item;


// Load Projectknife plugins
JPluginHelper::importPlugin('projectknife');
$dispatcher = JEventDispatcher::getInstance();

JHtml::_('stylesheet', 'lib_projectknife/core.css', false, true, false, false, true);
JHtml::_('stylesheet', 'com_pktasks/task.css', false, true, false, false, true);
JHtml::_('bootstrap.tooltip');

$txt_dateformat = JText::_('DATE_FORMAT_LC4');

$show_project = (int) $this->params->get('show_project', 1);
$show_date    = (int) $this->params->get('show_date', 0);
$show_author  = (int) $this->params->get('show_author', 0);
$show_start   = (int) $this->params->get('show_start', 1);
$show_due     = (int) $this->params->get('show_due', 1);
$show_tags    = (int) $this->params->get('show_tags', 1);
$show_details = ($show_project || $show_date || $show_author || $show_start || $show_due || $show_tags);

$desc_span = $show_details ? '8' : '12';
$mod_span  = (count(JModuleHelper::getModules('pk-task-right')) > 0 ? '8' : '12');

JFactory::getDocument()->addScriptDeclaration('
    Joomla.submitbutton = function(task)
    {
        Joomla.submitform(task, document.getElementById("adminForm"));
    };
');
?>
<?php if ($this->params->get('show_page_heading', 1) == '1') : ?>
    <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
<?php endif; ?>

<form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_pktasks&view=item&Itemid=' . PKRouteHelper::getMenuItemId('active')); ?>" method="post">
    <?php
    // Toolbar
    echo $this->toolbar;
    ?>
    <input type="hidden" name="task" value="" />
    <?php
        echo JHtml::_('form.token');

        // Render hidden filter fields
        $filters = array();
        $dispatcher->trigger('onProjectknifeDisplayHiddenFilter', array('com_pktasks.item', &$filters));

        if (count($filters)) {
            echo implode("\n", $filters);
        }
    ?>
</form>

<div class="item-page pktasks-task-page">
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
                echo $item->description;
            }
            ?>
        </div>
        <?php if ($show_details) : ?>
            <div class="span4">
                <ul class="unstyled pkdetails pkdetails-task hidden-phone">
                    <?php
                    if ($show_project) {
                        echo '<li class="pkdetail-project">'
                        . JText::_('COM_PKPROJECTS_PROJECT') . ': '
                        . $item->project_title
                        . '</li>';
                    }

                    if ($show_date) {
                        echo '<li class="pkdetail-created">'
                        . JText::_('PKGLOBAL_CREATED') . ': <span class="hasTooltip" title="' . $this->escape(PKDateHelper::relativeDays($item->created)) . '">'
                        . JHtml::_('date', $item->created, $txt_dateformat) . '</span>'
                        . '</li>';
                    }

                    if ($show_author) {
                        $author_link = PKUserHelper::getProfileLink($item->created_by);

                        if ($author_link) {
                            $author_name = '<a href="' . $author_link . '">'
                                         . $this->escape($item->author_name)
                                         . '</a>';
                        }
                        else {
                            $author_name = $this->escape($item->author_name);
                        }

                        echo '<li class="pkdetail-created_by">'
                        . JText::_('PKGLOBAL_CREATED_BY_LABEL') . ': '
                        . $author_name
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

                    if ($show_tags && isset($this->item->tags->itemTags)) {
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

    <!-- Begin Task Modules -->
    <?php if (count(JModuleHelper::getModules('pk-task-top'))) : ?>
        <div class="row-fluid">
        	<div class="span12">
        		<?php echo $modules->render('pk-task-top', array('style' => 'xhtml'), null); ?>
        	</div>
        </div>
    <?php
    endif;

    if (count(JModuleHelper::getModules('pk-task-left')) || count(JModuleHelper::getModules('pk-task-right'))) : ?>
        <div class="row-fluid">
        	<div class="span<?php echo $mod_span; ?>">
        		<?php echo $modules->render('pk-task-left', array('style' => 'xhtml'), null); ?>
        	</div>
            <?php if (count(JModuleHelper::getModules('pk-task-right'))) : ?>
                <div class="span4">
                    <div class="hidden-phone">
            		  <?php echo $modules->render('pk-task-right', array('style' => 'xhtml'), null); ?>
                    </div>
            	</div>
            <?php endif; ?>
        </div>
    <?php
    endif;

    if (count(JModuleHelper::getModules('pk-task-bottom'))) : ?>
        <div class="row-fluid">
        	<div class="span12">
        		<?php echo $modules->render('pk-task-bottom', array('style' => 'xhtml'), null); ?>
        	</div>
        </div>
    <?php endif; ?>
    <!-- End Dashboard Modules -->

    <?php echo $item->event->afterDisplayContent; ?>
</div>