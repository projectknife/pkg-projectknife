<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkdashboard
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;

$buttons    = array();
$dispatcher = JEventDispatcher::getInstance();

$dispatcher->trigger('onProjectknifeDisplayDashboardButtons', array(&$buttons, $this->item->id));

if (!count($buttons)) {
    return;
}

$j = 0;
$btns_per_row = $this->params->get('buttons_per_row', 3);
$btn_img_size = (int) $this->params->get('max_button_img_size', 64);
$span = 12 / $btns_per_row;

JFactory::getDocument()->addStyleDeclaration('.pk-dashboard-buttons img {max-width: ' . $btn_img_size . 'px;}');

for ($i = 0; $i < count($buttons); $i++)
{
    $btn = $buttons[$i];

    if ($j == 0) {
        echo '<div class="row-fluid pk-dashboard-buttons">';
    }

    echo '<div class="span' . $span . '">';
    echo '<div class="thumbnail center">';
    echo '<a class="btn btn-link btn-block" href="' . $btn->link . '">';
    echo '<p>' . $btn->icon . '</p>';
    echo $btn->title;
    echo '</a>';
    echo '</div>';
    echo '</div>';

    $j++;

    if ($j == $btns_per_row) {
        $j = 0;
        echo '</div><p>&nbsp;</p>';
    }
}

while ($j != $btns_per_row && $j > 0)
{
    echo '<div class="span' . $span . '"></div>';
    $j++;
}

if ($j > 0) {
    echo '</div><p>&nbsp;</p>';
}