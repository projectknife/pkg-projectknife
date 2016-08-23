/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKToolbar=window.PKToolbar||{},PKToolbar.init=function(){var a=jQuery("#boxchecked"),b=jQuery("#pk-toolbar");a.length&&a.change(function(){var a=jQuery(".disabled-list",b);a.length&&("0"==jQuery(this).val()?a.addClass("disabled"):a.removeClass("disabled"))})},PKToolbar.showMenu=function(a){var b=jQuery("#pk-toolbar");if(b.length){var c=jQuery(".pk-toolbar-menu",b),d=jQuery("#pk-toolbar-menu-"+a);c.length&&d.length&&(c.hide(),d.show())}};