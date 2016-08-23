<?php
/**
 * @package      pkg_projectknife
 * @subpackage   mod_pkfilters
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


// Do nothing if the Projectknife lib is not present
if (!defined('PK_LIBRARY')) {
    return;
}


// Get the current menu item and the supported components
$app       = JFactory::getApplication();
$menu      = $app->getMenu();
$menu_item = $menu->getActive();
$supported = PKApplicationHelper::getComponentNames();

if (($menu_item instanceof stdClass) == false) {
    $option = $app->input->get('option');
    $view   = $app->input->get('view');
}
else {
    $query  = $menu_item->query;

    if (isset($query['option'])) {
        if ($query['option'] != $app->input->getCmd('option')) {
            $option = $app->input->getCmd('option');
        }
        else {
            $option = $query['option'];
        }
    }

    if (isset($query['view'])) {
        if ($query['view'] != $app->input->getCmd('view')) {
            $view = $app->input->getCmd('view');
        }
        else {
            $view = $query['view'];
        }
    }
}

// Check if the component is supported
if (!in_array($option, $supported)) {
    return;
}


// Load Projectknife plugins and get the filters
$dispatcher = JEventDispatcher::getInstance();
JPluginHelper::importPlugin('projectknife');

$filters = array();

$dispatcher->trigger('onProjectknifeBeforeDisplayFilter', array($option . '.' . $view, 'site', &$filters));
$dispatcher->trigger('onProjectknifeAfterDisplayFilter',  array($option . '.' . $view, 'site', &$filters));

if (!count($filters)) {
    return;
}

// Load JS
JHtml::_('script', 'mod_pkfilters/filters.js', false, true, false, false, true);

JFactory::getDocument()->addScriptDeclaration('
    jQuery(document).ready(function()
	{
		PKfilters.init(jQuery("#mod-pkfilters-' . $module->id . '"));
	});
');

if ($params->get('js_chosen', 1)) {
    JHtml::_('formbehavior.chosen', '#mod-pkfilters-' . $module->id . ' select');
}

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));


require JModuleHelper::getLayoutPath('mod_pkfilters', $params->get('layout', 'horizontal'));