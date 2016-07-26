<?php
/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKGrid
{
    protected static $js_loaded = false;

    public static function selectItem($i, $id, $size = 'mini')
    {
        return '<button type="button" id="grid-select-' . $i . '" class="btn btn-' . $size . ' grid-select-item" value="' . $i . '" style="display:none;">'
               . '<span class="icon-ok"></span>'
               . '</button>'
               . '<span style="display:none;">'
               . '<input id="cb' . $i . '" type="checkbox" value="' . intval($id) . '" name="cid[]"/>'
               . '</span>';
    }


    public static function selectAll($size = 'mini', $text = '')
    {
        $text = (empty($text) ? JText::_('PKGLOBAL_SELECT_ALL') : $text);

        return '<button type="button" id="grid-select-all" class="btn btn-' . $size . ' grid-select-all" value="0" style="display:none;">'
               . '<span class="icon-ok"></span> <span class="hidden-phone">' . $text . '</span>'
               . '</button>';
    }


    public static function script($form = 'adminForm')
    {
        if (self::$js_loaded) return;

        JHtml::_('script', 'projectknife/lib_projectknife/grid.js', false, true, false, false, true);

        JFactory::getDocument()->addScriptDeclaration('
            jQuery(document).ready(function(){
                var f = jQuery("#' . $form . '");

                if (f.length) {
                    var el = jQuery("#grid-select-all", f);

                    if (el.length) {
                        el.click(function(){PKGrid.toggleSelectAll("#' . $form . '");})
                    }

                    el = jQuery("button.grid-select-item", f);

                    if (el.length) {
                        el.click(function(){PKGrid.toggleSelectItem(this.value, "#' . $form . '");});
                    }
                }
            });
        ');

        self::$js_loaded = true;
    }
}