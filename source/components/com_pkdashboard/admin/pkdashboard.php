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


// Access check
if (!JFactory::getUser()->authorise('core.manage')) {
    return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
}


JLoader::register('PKdashboardHelper', __DIR__ . '/helpers/pkdashboard.php');


$controller = JControllerLegacy::getInstance('PKdashboard');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();