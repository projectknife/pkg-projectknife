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

$lang = JFactory::getLanguage();

if (!$lang->load('com_pkdashboard', JPATH_ADMINISTRATOR)) {
    $lang->load('com_pkdashboard', JPATH_ADMINISTRATOR . '/components/com_pkdashboard');
}

$controller = JControllerLegacy::getInstance('PKdashboard');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();