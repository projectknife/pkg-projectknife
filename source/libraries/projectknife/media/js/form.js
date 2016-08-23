/**
 * @package      pkg_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */


PKform=window.PKform||{},PKform.ajaxUpdateOptions=function(a,b,c){if("string"==typeof a&&(a=jQuery(a)),b){var d=jQuery("option",a),e=d.length;if(e){var f=jQuery(d[0]).clone(!0);a.empty(),a.append(f),a.val(f.val()),a.trigger("change"),a.trigger("liszt:updated")}}else a.empty(),a.val(""),a.trigger("change"),a.trigger("liszt:updated");jQuery.ajax({url:c,data:"tmpl=component&format=json",type:"GET",processData:!0,cache:!1,dataType:"json",success:function(b){for(var c=b.data,d=c.length,e=null,f=0;f<d;f++)e=c[f],a.append('<option value="'+e.value+'">'+e.text+"</option>");a.trigger("liszt:updated")}})},PKform.ajaxUpdateSchedule=function(a,b,c){"string"==typeof b&&(b=jQuery(b));var d=jQuery(a).val();""==d||"0"==d?b.val(Joomla.JText._("PKGLOBAL_UNDEFINED")):jQuery.ajax({url:c,data:"tmpl=component&format=json&id="+d,type:"GET",processData:!0,cache:!1,dataType:"json",success:function(a){if(!a.success)return void Joomla.renderMessages(a.messages);var c="0000-00-00 00:00:00";return a.data.start_date==c||a.data.due_date==c?void b.val(Joomla.JText._("PKGLOBAL_UNDEFINED")):(a.data.start_date==c?a.data.start_date=Joomla.JText._("PKGLOBAL_UNDEFINED"):a.data.due_date==c&&(a.data.due_date=Joomla.JText._("PKGLOBAL_UNDEFINED")),void b.val("["+a.data.start_date+" | "+a.data.due_date+"]"))}})};