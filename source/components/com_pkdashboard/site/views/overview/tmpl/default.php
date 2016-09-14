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

    <div class="item-page">
        <?php
        if ($this->params->get('show_title', 1)) {
            echo '<div class="page-header"><h2>' . $this->escape($item->title) . '</h2></div>';
            echo $item->event->afterDisplayTitle;
        }
        ?>
        <dl class="article-info muted">
            <dd class="category-name">
                <?php echo JText::_('JCATEGORY') . ': ' . ($item->category_id ? $item->category_title : JText::_('PKGLOBAL_UNCATEGORISED')) . ' '; ?>
            </dd>
            <dd class="published">
                <span class="icon-calendar"></span>
                <time itemprop="datePublished" datetime="2015-04-29T19:02:26+02:00">Created: <?php echo JHtml::_('date', $item->created, $txt_dateformat); ?></time>
            </dd>
        </dl>
        <?php
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
            echo '<ul class="tags inline">' . $tags . '</ul>';
        }
        ?>
        <div itemprop="articleBody">
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

        <?php echo $item->event->afterDisplayContent; ?>
    </div>
<?php endif; ?>

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
    	<div class="span6">
    		<?php echo $modules->render('pk-dashboard-left', array('style' => 'xhtml'), null); ?>
    	</div>
    	<div class="span6">
    		<?php echo $modules->render('pk-dashboard-right', array('style' => 'xhtml'), null); ?>
    	</div>
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
