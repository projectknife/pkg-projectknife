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


jimport('joomla.application.component.controller');


class PKProjectsController extends JControllerLegacy
{
    /**
     * Displays the current view.
     *
     * @param     boolean        $cachable     If true, the view output will be cached.
     * @param     array          $urlparams    An array of safe url parameters and their variable types.
     *
     * @return    jcontroller                  A JController object to support chaining.
     */
    public function display($cachable = false, $urlparams = false)
    {
        $cachable = true;
        $view     = $this->input->getCmd('view', 'list');

        // Set the default view
        $this->input->set('view', $view);

        parent::display($cachable, $urlparams);

        return $this;
    }
}
