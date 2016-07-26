/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKGrid = window.PKGrid || {};


/**
 * Shows all grid elements
 *
 * @param     string    fid    The form id. Defaults to #adminForm
 *
 * @return    void
 */
PKGrid.show = function(fid)
{
    var f = (typeof fid !== 'undefined') ? jQuery(fid) : jQuery('#adminForm');

    if (f.length == 0) {
        console.log("PKGrid.show - Form not found!");
        return;
    }

    // Show the select-all button
    var ba = jQuery('#grid-select-all', f);

    if (ba.length) ba.show();

    // Show each item select button
    var bi = jQuery('button.grid-select-item', f);
    if (bi.length) bi.slideDown(200);
}


/**
 * Hides all grid elements
 *
 * @param     string     fid    The form id. Defaults to #adminForm
 * @param     boolean    d      Whether to unselect all items. Defaults to True.
 *
 * @return    void
 */
PKGrid.hide = function(fid, d)
{
    var f = (typeof fid !== 'undefined') ? jQuery(fid) : jQuery('#adminForm');
    d     = (typeof d !== 'undefined')   ? d           : true;

    if (f.length == 0) {
        console.log("PKGrid.hide - Form not found!");
        return;
    }

    // Hide the select-all button
    var ba = jQuery('#grid-select-all', f);
    if (ba.length) ba.hide();

    // Hide each item select button
    var bi = jQuery('button.grid-select-item', f);
    if (bi.length) bi.hide();

    // Deselect all buttons
    if (d) PKGrid.selectNone(fid);
}


/**
 * Selects all grid items
 *
 * @param     string     fid    The form id. Defaults to #adminForm
 *
 * @return    void
 */
PKGrid.selectAll = function(f)
{
    var f = (typeof fid !== 'undefined') ? jQuery(fid) : jQuery('#adminForm');

    if (f.length == 0) {
        console.log("PKGrid.selectAll - Form not found!");
        return;
    }

    // Mark "select all" button as pressed
    var el = jQuery('#grid-select-all', f);

    if (el.length) {
        el.addClass('btn-success');
        el.val(1);
    }

    // Check all boxes
    var cid = jQuery('input[name|="cid[]"]', f);
    if (cid.length) cid.prop('checked', true);

    // Select all item buttons
    el = jQuery('button.grid-select-item', f);
    if (el.length) el.addClass('btn-success');

    // Set total number of selected items to 0
    el = jQuery('#boxchecked', f);

    if (el.length) {
        el.val(cid.length);
        el.trigger('change');
    }
}


/**
 * Deselects all grid items
 *
 * @param     string     fid    The form id. Defaults to #adminForm
 *
 * @return    void
 */
PKGrid.selectNone = function(fid)
{
    var f = (typeof fid !== 'undefined') ? jQuery(fid) : jQuery('#adminForm');

    if (f.length == 0) {
        console.log("PKGrid.selectNone - Form not found!");
        return;
    }

    // Uncheck boxes
    var el = jQuery('input[name|="cid[]"]', f);
    if (el.length) el.prop('checked', false);

    // Deselect buttons
    el = jQuery('button.grid-select-item', f);
    if (el.length) el.removeClass('btn-success');

    // Deselect "select all" button
    el = jQuery('#grid-select-all', f);

    if (el.length) {
        el.removeClass('btn-success');
        el.val(0);
    }

    // Set total number of selected items to 0
    el = jQuery('#boxchecked', f);

    if (el.length) {
        el.val(0);
        el.trigger('change');
    }
}


/**
 * Toggles list selection
 *
 * @param     string     fid    The form id. Defaults to #adminForm
 *
 * @return    void
 */
PKGrid.toggleSelectAll = function(fid)
{
    var f = (typeof fid !== 'undefined') ? jQuery(fid) : jQuery('#adminForm');

    if (f.length == 0) {
        console.log("PKGrid.toggleSelectAll - Form not found!");
        return;
    }

    var el = jQuery('#grid-select-all', f);

    if (el.length) {
        if (el.val() == "1") {
            PKGrid.selectNone(fid);
        }
        else {
            PKGrid.selectAll(fid);
        }
    }
}


/**
 * Toggles item selection
 *
 * @param     string     i      The checkbox number to select
 * @param     string     fid    The form id. Defaults to #adminForm
 *
 * @return    void
 */
PKGrid.toggleSelectItem = function(i, fid)
{
    var f = (typeof fid !== 'undefined') ? jQuery(fid) : jQuery('#adminForm');

    if (f.length == 0) {
        console.log("PKGrid.toggleSelectItem - Form not found!");
        return;
    }

    var cb  = jQuery('#cb' + i, f);
    var bc  = jQuery('#boxchecked', f);
    var btn = jQuery('#grid-select-' + i, f);

    if (cb.length && btn.length) {
        if (cb.is(':checked')) {
            // Uncheck
            btn.removeClass('btn-success');
            cb.prop('checked', false);

            if (bc.length) bc.val(parseInt(bc.val()) - 1);
        }
        else {
            // Check
            btn.addClass('btn-success');
            cb.prop('checked', true);

            if (bc.length) bc.val(parseInt(bc.val()) + 1);
        }

        // Update check-all button if necessary
        var ba = jQuery('#grid-select-all', f);
        var c  = jQuery('input[name|="cid[]"]', f).length;

        if (ba.length && c && bc.length) {
            if (c == parseInt(bc.val()) && ba.val() == "0") {
                // All items are selected, but the "select all" button is not pressed
                ba.addClass('btn-success');
                ba.val(1);
            }
            else if (c > parseInt(bc.val()) && ba.val() == "1") {
                // Not all items are selected, but the "select all" button is pressed
                ba.removeClass('btn-success');
                ba.val(0);
            }
        }

        // Trigger change event
        if (bc.length) bc.trigger('change');
    }
}
