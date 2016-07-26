/**
 * @package      pkg_projectknife
 * @subpackage   mod_pkfilters
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKfilters = window.PKfilters || {};


/**
* Initialises the projectknife filter module
*
* @param    object       Module container object
*/
PKfilters.init = function(el)
{
    // For each select filter, apply the selected value to the hidden adminForm field
    // and the submit the form.
    jQuery('select', el).change(function()
    {
        var n = jQuery(this).attr('id').split('mod_')[1];

        jQuery('#' + n).val(jQuery(this).val());
        jQuery('#adminForm').submit();
    });
}