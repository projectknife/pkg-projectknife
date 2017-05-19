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


// Access check
if (!JFactory::getUser()->authorise('core.manage')) {
    return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
}

JLoader::register('PKProjectsHelper', __DIR__ . '/helpers/pkprojects.php');

$controller = JControllerLegacy::getInstance('PKprojects');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
