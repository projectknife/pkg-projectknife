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
<div class="grid project-list">
    <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>

    <form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_pkmilestones&view=item&Itemid=' . PKApplicationHelper::getMenuItemId('active')); ?>" method="post">
        <?php
        // Toolbar
        echo $this->toolbar;

        echo $this->escape($item->description);
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
</div>