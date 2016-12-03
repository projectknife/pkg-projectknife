<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


$item = $this->item;


// Load Projectknife plugins
JPluginHelper::importPlugin('projectknife');
$dispatcher = JEventDispatcher::getInstance();

JHtml::_('stylesheet', 'lib_projectknife/core.css', false, true, false, false, true);
JHtml::_('stylesheet', 'com_pkmilestones/milestones.css', false, true, false, false, true);
JHtml::_('bootstrap.tooltip');

JFactory::getDocument()->addScriptDeclaration('
    Joomla.submitbutton = function(task)
    {
        Joomla.submitform(task, document.getElementById("adminForm"));
    };
');
?>
<div class="item-page view-milestone-item">
    <?php if ($this->params->get('show_page_heading', 1)) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
    <?php endif; ?>

    <div class="page-header">
		<h2><?php echo $this->escape($item->title); ?></h2>
	</div>

    <?php echo $item->event->afterDisplayTitle; ?>

    <form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_pkmilestones&view=item&Itemid=' . PKRouteHelper::getMenuItemId('active')); ?>" method="post">
        <?php
        // Toolbar
        echo $this->toolbar;
        ?>
        <input type="hidden" name="task" value="" />
        <?php
            echo JHtml::_('form.token');

            // Render hidden filter fields
            $filters = array();
            $dispatcher->trigger('onProjectknifeDisplayHiddenFilter', array('com_pkmilestones.item', &$filters));

            if (count($filters)) {
                echo implode("\n", $filters);
            }
        ?>
    </form>
    <dl class="article-info milestone-info muted">
        <dd class="project-name">
            Category: <a href="/projectknife/dev/article-category.html" itemprop="genre">Uncategorised</a>
        </dd>
        <dd class="createdby">
            <?php echo JText::_('PKGLOBAL_CREATED_BY_LABEL') . ': <span itemprop="name">' . $this->item->author_name . '</span>'; ?>
        </dd>
        <dd class="published">
            <span class="icon-calendar"></span>
            <time datetime="2015-04-29T19:02:26+02:00" itemprop="datePublished"> Published: 29 April 2015 </time>
        </dd>
        <dd class="hits">
            <span class="icon-eye-open"></span>
            <meta itemprop="interactionCount" content="UserPageVisits:9">
            Hits: 9
        </dd>
    </dl>
    <?php
    $tags = '';

    /*
    foreach ($this->item->tags->itemTags AS $ti => $tag)
    {
        if (!in_array($tag->access, $view_levels)) {
            continue;
        }

        $tagParams  = new Registry($tag->params);
        $link_class = $tagParams->get('tag_link_class', 'label label-info');

        $tags .= '<li class="tag-2 tag-list0" itemprop="keywords"><span class="' . $link_class . '">' . $this->escape($tag->title) . '</span></li>';
    }
    */

    if ($tags) {
        echo '<ul class="tags inline">' . $tags . '</ul>';
    }
    ?>
    <div class="item-description">
        <?php echo $item->event->beforeDisplayContent; ?>
        <?php echo $this->escape($item->description); ?>
        <?php echo $item->event->afterDisplayContent; ?>
    </div>
</div>