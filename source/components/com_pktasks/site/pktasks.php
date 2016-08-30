<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pktasks
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


require_once JPATH_COMPONENT . '/helpers/route.php';


$controller = JControllerLegacy::getInstance('PKtasks');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();