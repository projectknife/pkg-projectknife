<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;

JLoader::register('PKMilestonesModelMilestones', JPATH_ADMINISTRATOR . '/components/com_pkmilestones/models/milestones.php');


class PKMilestonesModelList extends PKMilestonesModelMilestones
{

}
