/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKToolbar = window.PKToolbar || {};


PKToolbar.init = function()
{
    var bx = jQuery('#boxchecked');
    var tb = jQuery('#pk-toolbar');

    if (bx.length) {
        bx.change(function() {
            var btns = jQuery('.disabled-list', tb);

            if (btns.length) {
                if (jQuery(this).val() == "0") {
                    btns.addClass('disabled');
                }
                else {
                    btns.removeClass('disabled');
                }
            }
        });
    }
}


PKToolbar.showMenu = function(n)
{
    var tb = jQuery('#pk-toolbar');

    if (tb.length) {
        var menus = jQuery('.pk-toolbar-menu', tb);
        var m     = jQuery('#pk-toolbar-menu-' + n);

        if (menus.length && m.length) {
            menus.hide();
            m.show();
        }
    }
}