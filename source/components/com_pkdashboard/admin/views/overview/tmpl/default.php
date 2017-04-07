<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkdashboard
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$modules = JFactory::getDocument()->loadRenderer('modules');
?>
<?php
    // Sidebar
    if (!empty($this->sidebar)) {
        echo '<div id="j-sidebar-container" class="span2">' . $this->sidebar . '</div><div id="j-main-container" class="span10">';
    }
    else {
        echo '<div id="j-main-container">';
    }
    ?>
    <div class="row-fluid">
        <div class="span12">
            <?php echo $modules->render('pk-dashboard-top', array('style' => 'xhtml'), null); ?>
        </div>
    </div>
    <div class="row-fluid">
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
    <div class="row-fluid">
        <div class="span12">
            <?php echo $modules->render('pk-dashboard-bottom', array('style' => 'xhtml'), null); ?>
        </div>
    </div>
</div>