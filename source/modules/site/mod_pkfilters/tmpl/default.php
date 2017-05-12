<?php
/**
 * @package      pkg_projectknife
 * @subpackage   mod_pkfilters
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


echo '<fieldset class="form-vertical mod-pkfilters" id="mod-pkfilters-' . $module->id . '">';

foreach ($filters AS $filter)
{
    echo '<div class="control-group"><div class="controls">' . $filter . '</div></div>';
}

echo '</fieldset>';
?>
