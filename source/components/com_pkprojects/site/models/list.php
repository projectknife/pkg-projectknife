<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


use Joomla\Registry\Registry;

JLoader::register('PKprojectsModelProjects', JPATH_ADMINISTRATOR . '/components/com_pkprojects/models/projects.php');

class PKprojectsModelList extends PKprojectsModelProjects
{

}
