<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_projects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;


use Joomla\Registry\Registry;

JLoader::register('JFormFieldRules', JPATH_SITE . '/libraries/joomla/form/fields/rules.php');
JLoader::register('JFormRulePKUserRules', __DIR__ . '/../rules/pkuserrules.php');


/**
 * Field for assigning permissions to users for a given project
 *
 */
class JFormFieldPKUserRules extends JFormFieldRules
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'PKUserRules';


    protected function getActions($component, $section)
    {
        $actions    = array($component => JAccess::getActions($component, $section));
        $components = PKApplicationHelper::getComponents();

        foreach ($components AS $cmp)
        {
            if (!$cmp->enabled || $cmp->element == $component) {
                continue;
            }

            $tmp = JAccess::getActions($cmp->element, $section);

            if (is_array($tmp) && count($tmp)) {
                $actions[$cmp->element] = $tmp;
            }
        }

        return $actions;
    }


    /**
     * Method to get the field input markup for Access Control Lists.
     * Optionally can be associated with a specific component and section.
     *
     * @return    string    The field input markup.
     */
    protected function getInput()
    {
        JHtml::_('bootstrap.tooltip');

        // Initialise some field attributes.
        $section    = $this->section;
        $component  = $this->component;
        $assetField = $this->assetField;

        $db = JFactory::getDbo();

        // Get the actions for the asset.
        $actions = $this->getActions($component, $section);

        // Iterate over the children and add to the actions.
        foreach ($this->element->children() as $el)
        {
            if ($el->getName() == 'action') {
                $actions[] = (object) array(
                    'name'        => (string) $el['name'],
                    'title'       => (string) $el['title'],
                    'description' => (string) $el['description']
                );
            }
        }

        // Get the explicit rules for this asset.
        if ($section == 'component') {
            // Need to find the asset id by the name of the component.
            $query = $db->getQuery(true);

            $query->select($db->quoteName('id'))
                  ->from($db->quoteName('#__assets'))
                  ->where($db->quoteName('name') . ' = ' . $db->quote($component));

            $db->setQuery($query);
            $assetId = (int) $db->loadResult();
        }
        else {
            // Find the asset id of the content.
            // Note that for global configuration, com_config injects asset_id = 1 into the form.
            $assetId = $this->form->getValue($assetField);
        }

        // Full width format.

        // Get the rules for just this asset (non-recursive).
        $assetRules = JAccess::getAssetRules($assetId);

        // Get the available users.
        if ($assetId) {
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__pk_projects')
                  ->where('asset_id = ' . (int) $assetId);

            $db->setQuery($query);
            $project_id = (int) $db->loadResult();

            $users = $this->getUsers($project_id);
        }
        else {
            $users = array();
        }

        // Setup chosen settings
        $chosen_settings = array('placeholder_text_multiple' => 'test');

        // Setup ajax chosen settings
        $chosen_ajax_settings = new Registry(
            array(
                'selector'      => '#' . $this->fieldname . '_search',
                'type'          => 'GET',
                'url'           => JUri::root() . 'index.php?option=com_ajax&plugin=SearchProjectUser&format=string',
                'dataType'      => 'json',
                'jsonTermKey'   => 'like',
                'minTermLength' => 3
            )
        );

        JHtml::_('formbehavior.chosen', '#' . $this->fieldname . '_search', null, $chosen_settings);
        JHtml::_('formbehavior.ajaxchosen', $chosen_ajax_settings);

        // Catch update event
        JFactory::getDocument()->addScriptDeclaration('
            jQuery(document).ready(function() {
                jQuery("#' . $this->fieldname . '_search").chosen().change(function()
                {
                    var users_str = jQuery(this).val();

                    if (users_str == null) {
                        var users_list = new Array();
                    }
                    else {
                        var users_list = users_str.toString().split(",");
                    }

                    var tabs  = jQuery("#' . $this->fieldname . '-tabs li");
                    var panes = jQuery("#' . $this->fieldname . '-panes .tab-pane");

                    if (users_list.length > tabs.length) {
                        // Add new user
                        var idx = users_list.length - 1;

                        var choices = jQuery("#' . $this->fieldname . '_search_chzn .search-choice");
                        var name    = jQuery("span", choices[idx]).text();
                        var ui_idx  = parseInt(jQuery("a", choices[idx]).attr("data-option-array-index"));
                        var opts    = jQuery("#' . $this->fieldname . '_search option");
                        var uid     = opts[ui_idx].value;

                        var permissions = "' . addslashes($this->getHTMLpermissions($actions, $assetId, false, '{uid}', '{name}')) . '";
                        permissions = permissions.replace(/{uid}/g, uid);
                        permissions = permissions.replace(/{name}/g, name);

                        if (tabs.length == 0) {
                            var active1 = " class=\"active\"";
                            var active2 = " active";
                        }
                        else {
                            var active1 = "";
                            var active2 = "";
                        }
                        jQuery("#' . $this->fieldname . '-tabs").append("<li id=\'user-tab-" + uid + "\' " + active1 + "><a href=\'#permission-" + uid + "\' data-toggle=\'tab\'>" + name + "</a></li>");
                        jQuery("#' . $this->fieldname . '-panes").append("<div class=\'tab-pane" + active2 + "\' id=\'permission-" + uid + "\'>" + permissions + "</div>");
                    }
                    else {
                        // Remove user
                        var count = tabs.length;
                        var uid   = 0;
                        var idx   = -1;

                        for (var i = 0; i < count; i++)
                        {
                            uid = tabs[i].id.split("-")[2];
                            idx = users_list.indexOf(uid);

                            if (idx == -1) {
                                tabs[i].remove();
                                panes[i].remove();
                                break;
                            }
                        }
                    }
                });
            });
        ');


        // Prepare output
        $html   = array();
        // $html[] = '<input type="hidden" name="' . $this->name . '" value=""/>';
        $html[] = '<div class="control-group">';
        $html[] = '<div class="control-label"><label id="jform_' . $this->fieldname . '-lbl" class="" for="' . $this->fieldname . '_search">User Access</label></div>';
        $html[] = '<div class="controls">';
        $html[] = '<select id="' . $this->fieldname . '_search" name="' . $this->fieldname . '_search" class="span12" multiple>';

        foreach ($users as $user)
        {
            $html[] = '<option value="' . (int) $user->value . '" selected="selected">' . $user->text . '</option>';
        }

        $html[] = '</select>';
        $html[] = '</div>';
        $html[] = '</div>';

        // Begin tabs
        $html[] = '<p class="rule-desc">Manage the permission settings for the users below.</p>';
        $html[] = '<div id="' . $this->fieldname . '-sliders" class="tabbable tabs-left">';

        // Building tab nav
        $html[] = '<ul class="nav nav-tabs" id="' . $this->fieldname . '-tabs">';

        $active = null;

        foreach ($users as $user)
        {
            if (is_null($active)) $active = 'active';

            $html[] = '<li id="' . $this->fieldname . '-tab-' . $user->value . '" class="' . $active . '">';
            $html[] = '<a href="#' . $this->fieldname . '-pane-' . $user->value . '" data-toggle="tab">';
            $html[] = $user->text;
            $html[] = '</a>';
            $html[] = '</li>';

            $active = '';
        }

        $html[] = '</ul>';

        $html[] = '<div class="tab-content" id="' . $this->fieldname . '-panes">';

        $active = null;

        // Start a tab pane for each user group.
        foreach ($users as $user)
        {
            if (is_null($active)) $active = ' active';

            $html[] = '<div class="tab-pane' . $active . '" id="' . $this->fieldname . '-pane-' . $user->value . '">';
            $html[] = $this->getHTMLpermissions($actions, $assetId, true, $user->value, $user->text);
            $html[] = '</div>';

            $active = '';
        }

        $html[] = '</div></div>';

        return implode("\n", $html);
    }


    protected function getHTMLpermissions($actions, $assetId, $can_calc, $uid = '', $uname = '')
    {
        static $assetRules = null;

        if (is_null($assetRules)) {
            $assetRules = JAccess::getAssetRules($assetId);
        }

        $html = array();

        foreach ($actions as $action_group => $action_options)
        {
			$html[] = '<table class="table table-striped">';
            $html[] = '<thead>';
            $html[] = '<tr>';

            $html[] = '<th class="actions" id="' . $this->fieldname . '-actions-th-' . $uid . '">';
            $html[] = '<span class="acl-action">' . JText::_(strtoupper($action_group) . '_PERMISSIONS_HEADING') . '</span>';
            $html[] = '</th>';
            $html[] = '<th class="settings" id="' . $this->fieldname . '-settings-th-' . $uid . '">';
            $html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_SELECT_SETTING') . '</span>';
            $html[] = '</th>';

            if ($can_calc) {
                $html[] = '<th id="' . $this->fieldname . '-aclaction-th-' . $uid . '">';
                $html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_CALCULATED_SETTING') . '</span>';
                $html[] = '</th>';
            }

            $html[] = '</tr>';
            $html[] = '</thead>';
            $html[] = '<tbody>';


            foreach ($action_options as $action)
            {
                $html[] = '<tr>';
                $html[] = '<td headers="actions-th' . $uid . '">';
                $html[] = '<label for="' . $this->id . '_' . $action->name . '_' . $uid . '" class="hasTooltip" title="'
                    . htmlspecialchars(JText::_($action->title) . ' ' . JText::_($action->description), ENT_COMPAT, 'UTF-8') . '">';
                $html[] = JText::_($action->title);
                $html[] = '</label>';
                $html[] = '</td>';

                $html[] = '<td headers="' . $this->fieldname . '-settings-th-' . $uid . '">';

                $html[] = '<select data-chosen="true" class="input-small"'
                    . ' name="' . $this->name . '[' . $action->name . '][-' . $uid . ']"'
                    . ' id="' . $this->id . '_' . $action->name    . '_' . $uid . '"'
                    . ' title="' . JText::sprintf('JLIB_RULES_SELECT_ALLOW_DENY_GROUP', JText::_($action->title), trim($uname)) . '">';

                if (is_numeric($uid)) {
                    $inheritedRule = JAccess::check($uid, $action->name, $assetId);
                    $assetRule     = $assetRules->allow($action->name, $uid * -1);
                }
                else {
                    $inheritedRule = null;
                    $assetRule     = null;
                }

                // The parent group has "Not Set", all children can rightly "Inherit" from that.
                $html[] = '<option value=""' . ($assetRule === null ? ' selected="selected"' : '') . '>'
                    . JText::_(empty($component) ? 'JLIB_RULES_NOT_SET' : 'JLIB_RULES_INHERITED') . '</option>';
                $html[] = '<option value="1"' . ($assetRule === true ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_ALLOWED')
                    . '</option>';
                $html[] = '<option value="0"' . ($assetRule === false ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_DENIED')
                    . '</option>';

                $html[] = '</select>&#160; ';

                // If this asset's rule is allowed, but the inherited rule is deny, we have a conflict.
                if (($assetRule === true) && ($inheritedRule === false))
                {
                    $html[] = JText::_('JLIB_RULES_CONFLICT');
                }

                $html[] = '</td>';

                // Build the Calculated Settings column.
                // The inherited settings column is not displayed for the root group in global configuration.
                if ($can_calc)
                {
                    $html[] = '<td headers="' . $this->fieldname . '-aclaction-th-' . $uid . '">';

                    // This is where we show the current effective settings considering currrent group, path and cascade.
                    // Check whether this is a component or global. Change the text slightly.
                    if (is_numeric($uid)) {
                        $permission = JAccess::check($uid, 'core.admin', $assetId);
                    }
                    else {
                        $permission = null;
                    }


                    if ($permission !== true)
                    {
                        if ($inheritedRule === null)
                        {
                            $html[] = '<span class="label label-important">' . JText::_('JLIB_RULES_NOT_ALLOWED') . '</span>';
                        }
                        elseif ($inheritedRule === true)
                        {
                            $html[] = '<span class="label label-success">' . JText::_('JLIB_RULES_ALLOWED') . '</span>';
                        }
                        elseif ($inheritedRule === false)
                        {
                            if ($assetRule === false)
                            {
                                $html[] = '<span class="label label-important">' . JText::_('JLIB_RULES_NOT_ALLOWED') . '</span>';
                            }
                            else
                            {
                                $html[] = '<span class="label"><span class="icon-lock icon-white"></span> ' . JText::_('JLIB_RULES_NOT_ALLOWED_LOCKED')
                                    . '</span>';
                            }
                        }
                    }
                    elseif ($can_calc)
                    {
                        $html[] = '<span class="label label-success"><span class="icon-lock icon-white"></span> ' . JText::_('JLIB_RULES_ALLOWED_ADMIN')
                            . '</span>';
                    }
                    else
                    {
                        // Special handling for  groups that have global admin because they can't  be denied.
                        // The admin rights can be changed.
                        if ($action->name === 'core.admin')
                        {
                            $html[] = '<span class="label label-success">' . JText::_('JLIB_RULES_ALLOWED') . '</span>';
                        }
                        elseif ($inheritedRule === false)
                        {
                            // Other actions cannot be changed.
                            $html[] = '<span class="label label-important"><span class="icon-lock icon-white"></span> '
                                . JText::_('JLIB_RULES_NOT_ALLOWED_ADMIN_CONFLICT') . '</span>';
                        }
                        else
                        {
                            $html[] = '<span class="label label-success"><span class="icon-lock icon-white"></span> ' . JText::_('JLIB_RULES_ALLOWED_ADMIN')
                                . '</span>';
                        }
                    }

                    $html[] = '</td>';
                }

                $html[] = '</tr>';
            }

            $html[] = '</tbody>';
            $html[] = '</table>';
        }

        $html[] = '<input type="hidden" name="' . $this->name . '[core.user][' . $uid . ']" value="1"/>';

        return implode('', $html);
    }


    /**
     * Get a list of the users.
     *
     * @return    array
     */
    protected function getUsers($project_id = 0)
    {
        // Get system plugin settings
        $sys_params = PKPluginHelper::getParams('system', 'projectknife');

        switch ($sys_params->get('user_display_name'))
        {
            case '1':
                $display_name_field = 'name';
                break;

            default:
                $display_name_field = 'username';
                break;
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id AS value, a.' . $display_name_field . ' AS text')
              ->from('#__users AS a')
              ->join('INNER', '#__pk_project_users AS b ON b.user_id = a.id')
              ->where('b.project_id = ' . $project_id)
              ->group('a.id, a.' . $display_name_field)
              ->order('a.' . $display_name_field . ' ASC');

        $db->setQuery($query);
        $options = $db->loadObjectList();

        return $options;
    }
}
