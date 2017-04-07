/**
 * @package      pkg_projectknife
 * @subpackage   mod_pkfilters
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKfilters=window.PKfilters||{},PKfilters.init=function(a){jQuery("select",a).change(function(){var a=jQuery(this).attr("id").split("mod_")[1];jQuery("#"+a).val(jQuery(this).val()),jQuery("#adminForm").submit()})};