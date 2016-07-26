/**
 * @package      pkg_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKlistTasks = window.PKlistTasks || {};


/**
* Initialises progress button ajax functionality.
*
* @param    mixed        Object reference or selector of the target button
* @param    string       Ajax update url
*/
PKlistTasks.initProgressButton = function(s, updateURL)
{
    if (typeof s === 'string') {
        var buttons = jQuery(s);
    }
    else {
        var buttons = s;
    }

    var count = buttons.length;
    var btn   = null;

    for (var i = 0; i != count; i++)
    {
        btn = jQuery(buttons[i]);

        if (btn.hasClass('disabled')) continue;

        btn.click(function()
        {
            el = jQuery(this);

            if (el.hasClass('disabled')) {
                // Update still in progress
                return false;
            }

            // Get data
            var d_id   = parseInt(el.attr('data-id'));
            var d_prog = parseInt(el.attr('data-progress'));
            var d_new  = d_prog == 100 ? 0 : 100;

            // Temporarily disable the button
            el.addClass('disabled');

            // Send request
            jQuery.ajax(
            {
                url: updateURL,
                data: 'cid[]=' + d_id + '&progress=' + d_new + '&tmpl=component&format=json',
                type: 'GET',
                processData: true,
                cache: false,
                dataType: 'json',
                success: function(r)
                {
                    if (!r.success)  {
                        Joomla.renderMessages(r.messages);
                        return false;
                    }

                    // Remove all classes
                    el.removeClass('btn-danger');
                    el.removeClass('btn-warning');
                    el.removeClass('btn-success');

                    if (d_new == 100) {
                        // Task done
                        el.addClass('btn-success');
                        el.attr('data-progress', '100');
                        el.text(Joomla.JText._('PKGLOBAL_COMPLETED'));
                    }
                    else {
                        // Task is now to-do
                        el.attr('data-progress', '0');
                        var due = parseInt(el.attr('data-due'));

                        if (!Date.now) {
                            var now = (Math.floor(new Date().getTime() / 1000) / 86400) * 86400;
                        }
                        else {
                            var now = (Math.floor(Date.now() / 1000) / 86400) * 86400;
                        }

                        if (due > now || due == 0) {
                            el.text(Joomla.JText._('PKGLOBAL_TODO'));
                            el.addClass('btn-warning');
                        }
                        else if (due == now) {
                            el.text(Joomla.JText._('PKGLOBAL_DUE_TODAY'));
                            el.addClass('btn-warning');
                        }
                        else {
                            el.text(Joomla.JText._('PKGLOBAL_OVERDUE'));
                            el.addClass('btn-danger');
                        }
                    }

                    // Re-enable button
                    el.removeClass('disabled');
                }
            });
        });
    }
}

/**
* Initialises progress button ajax functionality.
*
* @param    mixed        Object reference or selector of the target button
* @param    string       Ajax update url
*/
PKlistTasks.initProgressSlider = function(s, updateURL)
{
    if (typeof s === 'string') {
        var sliders = jQuery(s);
    }
    else {
        var sliders = s;
    }

    var count = sliders.length;
    var sl    = null;

    for (var i = 0; i != count; i++)
    {
        sl = jQuery(sliders[i]);

        if (sl.hasClass('disabled')) {
            sl.slider('disable');
            continue;
        }

        sl.slider().on('slideStop', function(e)
        {
            el = jQuery(this);

            // Get data
            var d_id   = parseInt(el.attr('data-id'));
            var d_prog = parseInt(el.attr('data-progress'));
            var d_new  = parseInt(e.value);

            if (d_new == d_prog) {
                return false;
            }

            // Temp disable the slider
            el.slider('disable');

            // Send request
            jQuery.ajax(
            {
                url: updateURL,
                data: 'cid[]=' + d_id + '&progress=' + d_new + '&tmpl=component&format=json',
                type: 'GET',
                processData: true,
                cache: false,
                dataType: 'json',
                success: function(r)
                {
                    if (!r.success) {
                        Joomla.renderMessages(r.messages);
                        return false;
                    }

                    el.attr('data-progress', d_new);
                    el.slider('enable');
                }
            });
        })
        .data('slider');
    }
}


/**
* Initialises priority button ajax functionality.
*
* @param    mixed        Object reference or selector of the target button
* @param    string       Ajax update url
*/
PKlistTasks.initPriorityButton = function(s, updateURL)
{
    if (typeof s === 'string') {
        var buttons = jQuery(s);
    }
    else {
        var buttons = s;
    }

    var count = buttons.length;
    var btn   = null;

    for (var i = 0; i != count; i++)
    {
        btn = jQuery(buttons[i]);

        if (btn.hasClass('disabled')) {
            continue;
        }

        btn.click(function(e)
        {
            el = jQuery(this);

            // Get data
            var d_id  = parseInt(el.attr('data-id'));
            var d_val = parseInt(el.attr('data-value'));
            var d_new = (d_val == 1) ? 0 : 1;

            // Temp disable the button
            el.addClass('disabled');

            // Send request
            jQuery.ajax(
            {
                url: updateURL,
                data: 'cid[]=' + d_id + '&priority=' + d_new + '&tmpl=component&format=json',
                type: 'GET',
                processData: true,
                cache: false,
                dataType: 'json',
                success: function(r)
                {
                    if (!r.success) {
                        Joomla.renderMessages(r.messages);
                        return false;
                    }

                    el.attr('data-value', d_new);

                    if (d_new == 1) {
                        el.removeClass('prio-0');
                        el.addClass('prio-1');
                    }
                    else {
                        el.removeClass('prio-1');
                        el.addClass('prio-0');
                    }

                    PKlistTasks.initPriorityAfterUpdate(el, d_new);

                    el.removeClass('disabled');
                }
            });
        });
    }
}

PKlistTasks.initPriorityAfterUpdate = function(el, state)
{

}