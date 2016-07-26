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
$item        = $this->item;
?>
<?php if ($this->params->get('show_page_heading', 1) == '1') : ?>
    <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
<?php endif; ?>

<form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_pkdashboard&view=overview&Itemid=' . PKApplicationHelper::getMenuItemId('active')); ?>" method="post">
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
        }
        ?>
        <div class="muted">
            <?php echo JText::_('JCATEGORY') . ': ' . ($this->item->category_id ? $this->item->category_title : JText::_('PKGLOBAL_UNCATEGORISED')) . ' '; ?>
            <?php
            $tags = '';

            foreach ($this->item->tags->itemTags AS $ti => $tag)
            {
                if (!in_array($tag->access, $view_levels)) {
                    continue;
                }

                $tagParams  = new Registry($tag->params);
                $link_class = $tagParams->get('tag_link_class', 'label label-info');

                $tags .= '<span class="' . $link_class . '">' . $this->escape($tag->title) . '</span> ';
            }

            if ($tags) {
                echo ' | ' . JText::_('PKGLOBAL_TAGS') . ': ' . $tags;
            }
            ?>
        </div>
        <?php
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
<?php endif; ?>
<div class="row">
    <div class="span12">
        <?php echo $modules->render('pk-dashboard-top', array('style' => 'xhtml'), null); ?>
    </div>
</div>
<div class="row">
    <div class="span4">
        <?php echo $modules->render('pk-dashboard-middle-left', array('style' => 'xhtml'), null); ?>
    </div>
    <div class="span4">
        <?php echo $modules->render('pk-dashboard-middle-center', array('style' => 'xhtml'), null); ?>
    </div>
    <div class="span4">
        <?php echo $modules->render('pk-dashboard-middle-right', array('style' => 'xhtml'), null); ?>
    </div>
</div>
<div class="row">
    <div class="span12">
        <?php echo $modules->render('pk-dashboard-bottom', array('style' => 'xhtml'), null); ?>
    </div>
</div>
