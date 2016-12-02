<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkmilestones
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;


JPluginHelper::importPlugin('content');


class PKMilestonesViewList extends JViewLegacy
{
    /**
     * Items loaded by the model
     *
     * @var    array
     */
    protected $items;

    /**
     * Pagination instance
     *
     * @var    jpagination
     */
    protected $pagination;

    /**
     * Model state
     *
     * @var    jregistry
     */
    protected $state;

    /**
     * Application params
     *
     * @var    jregistry
     */
    protected $params;

    /**
     * Toolbar html
     *
     * @var    string
     */
    protected $toolbar;

    /**
     * Category filter options
     *
     * @var    array
     */
    protected $category_options;

    /**
     * Access level filter options
     *
     * @var    array
     */
    protected $access_options;


    /**
     * Execute and display a template script.
     *
     * @param     string    $tpl    The name of the template file to parse
     *
     * @return    mixed             A string if successful, otherwise a Error object.
     */
    public function display($tpl = null)
    {
        $app = JFactory::getApplication();

        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');
        $this->params     = $app->getParams();
        $this->toolbar    = $this->getToolbar();

        $this->category_options = $this->get('CategoryOptions');
        $this->access_options   = $this->get('AccessOptions');

        // Prepare doc
        $this->prepareDocument();

        // Display
        parent::display($tpl);
    }


    /**
     * Method to prepare the document
     *
     * @return    void
     */
    protected function prepareDocument()
    {
        $app           = JFactory::getApplication();
        $menus         = $app->getMenu();
        $this->pathway = $app->getPathway();
        $title         = null;

        // Because the application sets a default page title, we need to get it from the menu item itself
        $this->menu = $menus->getActive();

        if ($this->menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $this->menu->title));
        }
        else {
            $this->params->def('page_heading', JText::_('COM_PKMILESTONES_SUBMENU_MILESTONES'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        }
        elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        }
        elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }


    protected function getToolbar()
    {
        if ((int) $this->params->get('show_toolbar', 1) == 0) {
            return '';
        }

        $filter_published = $this->state->get('filter.published');
        $filter_project   = (int) $this->state->get('filter.project_id');

        if (!$filter_project) {
            $filter_project = 'any';
        }

        $can_create = PKUserHelper::authProject('milestone.create', $filter_project);
        $can_change = PKUserHelper::authProject('milestone.edit.state', $filter_project) || PKUserHelper::authProject('milestone.edit.own.state', $filter_project);

        // Main Menu
        PKToolbar::menu('main');
            if ($can_create) {
                PKToolbar::btnTask('form.add', JText::_('JNEW'), false, array('icon' => 'plus'));
            }

            if ($can_create || $can_change) {
                PKToolbar::btnClick('PKToolbar.showMenu(\'edit\');PKGrid.show();', JText::_('JACTION_EDIT'), array('icon' => 'pencil'));
            }

            PKToolbar::btnClick('PKToolbar.showMenu(\'page\');', $this->state->get('list.limit'), array('icon' => 'list', 'id' => 'pk-toolbar-page-btn'));
            PKToolbar::search($this->escape($this->state->get('filter.search')));
        PKToolbar::menu();

        // Edit Menu
        if ($can_change || $can_create) {
            PKToolbar::menu('edit', false);
                PKToolbar::group();
                PKToolbar::btnClick('PKToolbar.showMenu(\'main\');PKGrid.hide();', '', array('icon' => 'chevron-left'));
                PKToolbar::custom(PKGrid::selectAll('normal'));
                PKToolbar::group();

                // List publishing state actions group
                if ($can_change) {
                    PKToolbar::group();
                    if ($filter_published != "" && $filter_published != "1") {
                        PKToolbar::btnTask('list.publish', JText::_('PKGLOBAL_PUBLISH'), true, array('icon' => 'eye-open', 'class' => 'disabled disabled-list'));
                    }

                    if ($filter_published != "0") {
                        PKToolbar::btnTask('list.unpublish', JText::_('PKGLOBAL_UNPUBLISH'), true, array('icon' => 'eye-close', 'class' => 'disabled disabled-list'));
                    }

                    if ($filter_published != "2") {
                        PKToolbar::btnTask('list.archive', JText::_('PKGLOBAL_ARCHIVE'), true, array('icon' => 'folder-open', 'class' => 'disabled disabled-list'));
                    }

                    if ($filter_published != "-2") {
                        PKToolbar::btnTask('list.trash', JText::_('PKGLOBAL_TRASH'), true, array('icon' => 'trash', 'class' => 'disabled disabled-list'));
                    }
                    else {
                        PKToolbar::btnTask('list.delete', JText::_('JACTION_DELETE'), true, array('icon' => 'trash', 'class' => 'disabled disabled-list'));
                    }
                    PKToolbar::group();
                }

                if ($can_create) {
                    PKToolbar::group();
                    PKToolbar::btnTask('list.copy_dialog', JText::_('JLIB_HTML_BATCH_COPY'), true, array('icon' => 'copy', 'class' => 'disabled disabled-list'));
                    PKToolbar::group();
                }

            PKToolbar::menu();
        }


        // Page menu
        PKToolbar::menu('page', false);
            PKToolbar::btnClick('PKToolbar.showMenu(\'main\');', '', array('icon' => 'chevron-left'));
            PKToolbar::custom('
                <div class="btn-group hidden-phone">
                <span class="label hasTooltip" style="cursor: help;" title="' . JText::_('PKGLOBAL_PRIMARY_SORT_AND_ORDER') . '">' . JText::_('J1') . '</span>
            </div>'
            );
            PKToolbar::selectSortBy($this->get('SortOptions'), $this->escape($this->state->get('list.ordering', 'a.due_date')));
            PKToolbar::selectOrderBy($this->escape($this->state->get('list.direction', 'asc')));
            PKToolbar::custom('
                <div class="btn-group hidden-phone">
                <span class="label hasTooltip" style="cursor: help;" title="' . JText::_('PKGLOBAL_SECONDARY_SORT_AND_ORDER') . '">' . JText::_('J2') . '</span>
            </div>'
            );
            PKToolbar::selectSortBy($this->get('SortOptions'), $this->escape($this->state->get('list.ordering_sec', 'a.progress')), '_sec');
            PKToolbar::selectOrderBy($this->escape($this->state->get('list.direction_sec', 'asc')), '_sec');
            PKToolbar::custom('
                <div class="btn-group hidden-phone">
                <span class="label hasTooltip" style="cursor: help;" title="' . JText::_('PKGLOBAL_NUM_LIST') . '">#</span>
            </div>'
            );
            PKToolbar::custom('<div class="btn-group">' . $this->pagination->getLimitBox() . '</div>');
        PKToolbar::menu();

        return PKToolbar::render(true);
    }
}
