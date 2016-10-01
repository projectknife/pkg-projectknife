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


JFormHelper::loadFieldClass('list');
JLoader::register('PKUserHelper', JPATH_LIBRARIES . '/projectknife/user/helper.php');


/**
 * Form Field class for selecting a project.
 *
 */
class JFormFieldPKproject extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'PKproject';


    /**
     * Method to get the field options.
     *
     * @return    array    The field option objects.
     */
    protected function getOptions()
    {
        $db      = JFactory::getDbo();
        $query   = $db->getQuery(true);
        $options = parent::getOptions();

        $permission = null;

        if (isset($this->element['permission'])) {
            $permission = $this->element['permission'];
        }

        // Add default option
        if (!count($options)) {
            $options[] = JHtml::_(
                'select.option',
                '',
                JText::_('PLG_PROJECTKNIFE_PROJECTS_FIELD_PROJECT_OPTION'),
                'value',
                'text',
                false
            );
        }

        // Get a list of projects
        $query->select('id AS value, title AS text, published')
              ->from('#__pk_projects')
              ->order('title ASC');

        $db->setQuery($query);
        $projects = $db->loadObjectList();
        $count    = count($projects);


        if ($permission && !PKUserHelper::isSuperAdmin()) {
            // Check permission before adding option
            for ($i = 0; $i != $count; $i++)
            {
                if (!PKUserHelper::authProject(strval($permission), intval($projects[$i]->value)) && $projects[$i]->value != $this->value) {
                    continue;
                }

                $options[] = JHtml::_(
                    'select.option',
                    $projects[$i]->value,
                    $projects[$i]->text,
                    'value',
                    'text',
                    false
                );
            }
        }
        else {
            // No permission check
            for ($i = 0; $i != $count; $i++)
            {
                $options[] = JHtml::_(
                    'select.option',
                    $projects[$i]->value,
                    $projects[$i]->text,
                    'value',
                    'text',
                    false
                );
            }
        }

        return $options;
    }
}
