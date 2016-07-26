/**
 * @package      pkg_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKcal = window.PKcal || {};


/**
* Setup function for the dynamic calendar range
*
* @param    options
*/
PKcal.setup = function(options)
{
    // Setup events for dependency fields
    var dependencies = options.dependencies;
    var dependency   = null;

    for (var i = 0; i < dependencies.length; i++)
    {
        dependency = jQuery("#" + dependencies[i].field);

        if (!dependency.length) continue;

        dependency.change(function()
        {
            PKcal.dependencyChange(options)
        });
    }
}


/**
* Triggered when a date range dependency (project, milestone or task) selection is changed
*
* @param    options
*/
PKcal.dependencyChange = function(options)
{
    var dependencies = options.dependencies;
    var null_date    = options.settings.null_date;
    var dependency   = null;
    var field_val    = 0;
    var no_selection = true;

    for (var i = 0; i < dependencies.length; i++)
    {
        dependency = jQuery("#" + dependencies[i].field);

        if (!dependency.length) continue;

        field_val = dependency.val();

        if (field_val == "" || field_val == "0") continue;

        no_selection = false;

        jQuery.ajax(
        {
            url: 'index.php?option=' + dependencies[i].option + '&task=' + dependencies[i].task,
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

                // Check if dates are set
                if (r.data.start_date == null_date || r.data.due_date == null_date) {
                    return;
                }

                // Update date range
                if (options.date_range_field) {
                    jQuery("#" + options.date_range_field).val("[" + r.data.start_date + ", " + r.data.due_date + "]");
                }

                // Adjust selected dates to fit into the new range
                var start_field = jQuery('#' + options.start_date_field);
                var due_field   = jQuery('#' + options.due_date_field);

                var selected_start_time = Date.parse(start_field.val());
                var selected_due_time   = Date.parse(due_field.val());
                var new_start = start_field.val();
                var new_due   = due_field.val();

                var start_time = Date.parse(r.data.start_date);
                var due_time   = Date.parse(r.data.due_date);

                if (selected_start_time < start_time) {
                    selected_start_time = start_time;
                    new_start = r.data.start_date;
                }
                else if (due_time < selected_start_time) {
                    selected_start_time = due_time;
                    new_start = r.data.due_date;
                }

                if (due_time < selected_due_time) {
                    selected_due_time = due_time;
                    new_due = new_start;
                }
                else if (selected_start_time > selected_due_time) {
                    selected_due_time = selected_start_time;
                    new_due = r.data.due_date;
                }

                start_field.val(new_start);
                due_field.val(new_due);

                // Prepare calendar options
                var cal_options =
                {
                    field:      options.start_date_field,
                    format:     options.settings.format,
                    first_day:  options.settings.first_day,
                    start_date: r.data.start_date,
                    due_date:   r.data.due_date,
                    range:      true
                };

                // Setup start date calendar
                PKcal.update(cal_options);

                // Setup due date calendar
                cal_options.field = options.due_date_field;
                PKcal.update(cal_options);
            }
        });

        break;
    }

    if (no_selection) {
        // Update date range
        if (options.date_range_field) {
            jQuery("#" + options.date_range_field).val('');
        }

        var cal_options =
        {
            field:      options.start_date_field,
            format:     options.settings.format,
            first_day:  options.settings.first_day,
            range:      false
        };

        // Setup start date calendar
        PKcal.update(cal_options);

        // Setup due date calendar
        cal_options.field = options.due_date_field;
        PKcal.update(cal_options);
    }
}


/**
* Triggered by PKcal.dependencyChange(). Updates the calendar field.
*
* @param    options
*/
PKcal.update = function(options)
{
    if (options.range) {
        Calendar.setup(
        {
            inputField: options.field,
            ifFormat: options.format,
            button: options.field + '_img',
            align: "Tl",
            singleClick: true,
            firstDay: options.first_day,
            disableFunc: function(d)
            {
                var selected_time = parseInt(d.getTime());
                var start_time    = Date.parse(options.start_date);
                var due_time      = Date.parse(options.due_date);

                if (start_time > selected_time || due_time < selected_time) return true;

                return false;
            }
        });
    }
    else {
        Calendar.setup(
        {
            inputField: options.field,
            ifFormat: options.format,
            button: options.field + '_img',
            align: "Tl",
            singleClick: true,
            firstDay: options.first_day
        });
    }
}