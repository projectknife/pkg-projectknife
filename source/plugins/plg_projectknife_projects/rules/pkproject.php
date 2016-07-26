<?php
/**
 * @package      pkg_projectknife
 * @subpackage   plg_projectknife_projects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die();


use Joomla\Registry\Registry;


class JFormRulePKProject extends JFormRuleRules
{
    /**
     * Method to test the value.
     *
     * @param     simplexmlelement    $element    The SimpleXMLElement object representing the <field /> tag for the form field object.
     * @param     mixed               $value      The form field value to validate.
     * @param     string              $group      The field name group control value.
     * @param     registry            $input      An optional Registry object with the entire data set to validate against the entire form.
     * @param     jform               $form       The form object for which the field is being tested.
     *
     * @return    boolean                         True if the value is valid, false otherwise.
     */
    public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
        $value = (int) $value;

        if (!$value) {
            return true;
        }

        // Load project id and access
        $query->select('id, access')
              ->from('#__pk_projects')
              ->where('id = ' . $value);

        $db->setQuery($query);
        $project = $db->loadObject();

        // Check if project record exists
        if (empty($project)) {
            return false;
        }

        if (!PKUserHelper::isSuperAdmin()) {
            // Check viewing access
            $levels   = PKUserHelper::getAccessLevels();
            $projects = PKUserHelper::getProjects();

            if (!in_array($project->access, $levels) && !in_array($project->id, $projects)) {
                return false;
            }

            // Check permission
            $permission = isset($element['permission']) ? $element['permission'] : null;

            if (!is_null($permission)) {
                if (!PKUserHelper::authCategory($permission, $project->id)) {
                    return false;
                }
            }
        }

        return true;
    }
}
