<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


// Load Projectknife plugins
$dispatcher = JEventDispatcher::getInstance();
JPluginHelper::importPlugin('projectknife');

$params = JComponentHelper::getParams('com_pkprojects');
$auto_access = (int) $params->get('auto_access', 1);

// Prepare category options
$keep_cat = new stdClass();
$keep_cat->value = '';
$keep_cat->text  = '- ' . JText::_('COM_PKPROJECTS_COPY_KEEP_CATEGORY') . ' -';

$categories = array_merge(array($keep_cat), $this->category_options);

// Prepare access level options
$keep_access = new stdClass();
$keep_access->value = '';
$keep_access->text  = '- ' . JText::_('PKGLOBAL_KEEP_ACCESS') . ' -';

$levels = array_merge(array($keep_access), $this->access_options);

// Prepare include options
$including = (array) $dispatcher->trigger('onProjectknifeCopyOptions', array('com_pkprojects.projects'));
?>
<div class="modal hide fade" id="copyDialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&#215;</button>
		<h3><?php echo JText::_('PKGLOBAL_COPY_OPTIONS'); ?></h3>
	</div>
	<div class="modal-body modal-batch">
        <div class="row-fluid form-horizontal">
            <div class="span6">
                <div class="control-group">
                    <div class="control-label">
                        <?php echo JText::_('JCATEGORY'); ?>
                    </div>
    				<div class="controls">
    					<?php echo JHtml::_('select.genericlist', $categories, 'copy[category_id]'); ?>
    				</div>
    			</div>
                <?php if(!$auto_access) : ?>
                <div class="control-group">
                    <div class="control-label">
                        <?php echo JText::_('JGRID_HEADING_ACCESS'); ?>
                    </div>
    				<div class="controls">
                        <?php echo JHtml::_('select.genericlist', $levels, 'copy[access]'); ?>
    				</div>
    			</div>
                <?php endif; ?>
            </div>
            <div class="span6">
                <div class="control-group">
                    <?php
                    $txt_include = JText::_('PKGLOBAL_COPY_INCLUDING');
                    $txt_yes     = JText::_('JYES');
                    $txt_no      = JText::_('JNO');

                    $i = 0;

                    foreach ($including AS $component)
                    {
                        if (!is_array($component)) {
                            continue;
                        }

                        foreach ($component AS $opt)
                        {
                            if (!is_object($opt)) {
                                continue;
                            }
                            ?>
                            <div class="control-label">
                                <?php echo $txt_include . ': ' . $opt->text; ?>
                            </div>
                            <div class="controls">
                                <fieldset id="copy_inc_<?php echo $i; ?>_"class="radio btn-group btn-group-yesno">
                                    <input id="copy_inc_<?php echo $i; ?>_1" type="radio" checked="checked" value="1" name="copy[include][<?php echo $opt->value; ?>]"/>
                                    <label for="copy_inc_<?php echo $i; ?>_1"><?php echo $txt_yes; ?></label>
                                    <input id="copy_inc_<?php echo $i; ?>_0" type="radio" value="0" name="copy[include][<?php echo $opt->value; ?>]"/>
                                    <label for="copy_inc_<?php echo $i; ?>_0"><?php echo $txt_no; ?></label>
                                </fieldset>
                            </div>
                            <?php
                            $i++;
                        }
                    }
                    ?>
    			</div>
            </div>
		</div>
	</div>
    <div class="modal-footer">
		<button class="btn" type="button" data-dismiss="modal">
			<?php echo JText::_('JCANCEL'); ?>
		</button>
		<button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('projects.copy');">
			<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
		</button>
	</div>
</div>