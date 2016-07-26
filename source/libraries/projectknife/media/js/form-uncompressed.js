/**
 * @package      pkg_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKform = window.PKform || {};


/**
* Updates the options inside a <select></select> field via ajax request
*
* @param    mixed        Object reference or selector of the field to update
* @param    keepfirst    Keep the first option in the list
* @param    string       The url to call for the new options
*/
PKform.ajaxUpdateOptions = function(el, keepfirst, reloadUrl)
{
    if (typeof el === 'string') {
        el = jQuery(el);
    }

    // Empty the list
    if (keepfirst) {
        var opts     = jQuery('option', el);
        var opts_num = opts.length;

        if (opts_num) {
            var opt_first = jQuery(opts[0]).clone(true);

            el.empty();
            el.append(opt_first);
            el.val(opt_first.val());
            el.trigger("change");
            el.trigger("liszt:updated");
        }
    }
    else {
        el.empty();
        el.val('');
        el.trigger("change");
        el.trigger("liszt:updated");
    }

    // Update content
    jQuery.ajax(
    {
        url: reloadUrl,
        data: 'tmpl=component&format=json',
        type: 'GET',
        processData: true,
        cache: false,
        dataType: 'json',
        success: function(r)
        {
            var json_data = r.data;
            var json_num  = json_data.length;
            var json_opt  = null;

            for (var i = 0; i < json_num; i++)
            {
                json_opt = json_data[i];

                el.append('<option value="' + json_opt.value + '">' + json_opt.text + '</option>');
            }

            el.trigger("liszt:updated");
        }
    });
}


/**
* Updates the schedule info field
*
* @param    object       The object calling the function
* @param    mixed        Object reference or selector of the field to update
* @param    string       The url from which to get the schedule
*/
PKform.ajaxUpdateSchedule = function(src, el, dataUrl)
{
    if (typeof el === 'string') {
        el = jQuery(el);
    }

    var field_val = jQuery(src).val();

    if (field_val == "" || field_val == "0") {
        el.val(Joomla.JText._('PKGLOBAL_UNDEFINED'));
    }
    else {
        jQuery.ajax(
        {
            url: dataUrl,
            data: 'tmpl=component&format=json&id=' + field_val,
            type: 'GET',
            processData: true,
            cache: false,
            dataType: 'json',
            success: function(r)
            {
                // Check for error
                if (!r.success)  {
                    Joomla.renderMessages(r.messages);
                    return;
                }

                var null_date = "0000-00-00 00:00:00";

                // Check if dates are set
                if (r.data.start_date == null_date || r.data.due_date == null_date) {
                    el.val(Joomla.JText._('PKGLOBAL_UNDEFINED'));
                    return;
                }

                if (r.data.start_date == null_date) {
                    r.data.start_date = Joomla.JText._('PKGLOBAL_UNDEFINED');
                }
                else if (r.data.due_date == null_date) {
                    r.data.due_date = Joomla.JText._('PKGLOBAL_UNDEFINED');
                }

                el.val("[" + r.data.start_date + " | " + r.data.due_date + "]");
            }
        });
    }
}