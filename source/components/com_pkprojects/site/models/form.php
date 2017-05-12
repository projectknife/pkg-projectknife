<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkprojects
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2017 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;

JLoader::register('PKProjectsModelProject', JPATH_ADMINISTRATOR . '/components/com_pkprojects/models/project.php');


class PKProjectsModelForm extends PKProjectsModelProject
{
    /**
     * Get the return URL.
     *
     * @return    string    The return URL.
     */
    public function getReturnPage()
    {
        return base64_encode($this->getState('return_page', ''));
    }


    /**
     * Method to auto-populate the model state.
     * Note. Calling getState in this method will result in recursion.
     *
     * @return    void
     */
    protected function populateState()
    {
        parent::populateState();

        $app    = JFactory::getApplication();
        $return = $app->input->get('return', null, 'default', 'base64');

        if (empty($return) || !JUri::isInternal(base64_decode($return))) {
            $return = base64_encode(JRoute::_(PKProjectsHelperRoute::getListRoute(), false));
        }

        $this->setState('return_page', base64_decode($return));
    }
}