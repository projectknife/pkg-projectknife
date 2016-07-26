<?php
/**
 * @package      pkg_projectknife
 * @subpackage   lib_projectknife
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


abstract class PKToolbar
{
    /**
     * Prepared toolbar html elements
     *
     * @var    array
     */
    protected static $html = array();

    /**
     * Button group open/closed flag
     *
     * @var    boolean
     */
    protected static $group_open = false;

    /**
     * Menu open/closed flag
     *
     * @var    boolean
     */
    protected static $menu_open = false;

    /**
     * JS loaded flag
     *
     * @var    boolean
     */
    protected static $js_loaded = false;


    /**
     * Returns the toolbar as html string.
     *
     * @param     boolean    $js    Whether to load the javascript file or not
     *
     * @return    string
     */
    public static function render($js = true, $options = array())
    {
         // Load toolbar js
         if (!self::$js_loaded && $js) {
            JHtml::_('script', 'projectknife/lib_projectknife/toolbar.js', false, true, false, false, true);
            JFactory::getDocument()->addScriptDeclaration('jQuery(document).ready(function(){PKToolbar.init();});');

            self::$js_loaded = true;
         }

         return '<div id="pk-toolbar">'
                . implode("\n", self::$html)
                . '</div>'
                . '<input type="hidden" name="pk-toolbar-edit-mode" id="pk-toolbar-edit-mode" value="0" />';
    }


    /**
     * Opens or closes a button group
     *
     * @param     array    $attribs    Additional html attributes
     *
     * @return    void
     */
    public static function group($attribs = array())
    {
        if (self::$group_open) {
            self::$html[] = '</div>';
            self::$group_open = false;
        }
        else {
            $attr = '';
            $class = 'btn-group';

            if (is_array($attribs)) {

                if (isset($attribs['class'])) {
                    $class .= ' ' . $attribs['class'];
                    unset($attribs['class']);
                }

                foreach ($attribs AS $name => $value)
                {
                    $attr .= ' ' . $name . '="' . $value . '"';
                }
            }

            self::$html[] = '<div class="' . $class . '"' . $attr . '>';
            self::$group_open = true;
        }
    }


    /**
     * Opens or closes a menu container
     *
     * @param     array    $name    Additional html attributes
     *
     * @return    void
     */
    public static function menu($name = '', $visible = true)
    {
        if (self::$menu_open) {
            self::$html[] = '</div>';
            self::$menu_open = false;
        }
        else {
            $attr = ' class="pk-toolbar-menu btn-toolbar form-inline"';

            if (!$visible) {
                $attr .= ' style="display:none;"';
            }

            self::$html[] = '<div id="pk-toolbar-menu-' . $name. '"' . $attr . '>';
            self::$menu_open = true;
        }
    }


    /**
     * Creates a form task button
     *
     * @param     string     $task       The task to perform
     * @param     string     $text       The button text
     * @param     boolean    $list       Whether the task requires an item from the list to be selected or not
     * @param     array      $attribs    Additional html attributes for the button
     *
     * @return    void
     */
    public static function btnTask($task, $text, $list = false, $attribs = array())
    {
        $class = 'btn';
        $attr  = '';
        $click = '';
        $icon  = '';

        // Prepare class attribute
        if (isset($attribs['class'])) {
            $class .= ' ' . $attribs['class'];
            unset($attribs['class']);
        }

        // Parepare onclick attribute
        if (isset($attribs['onclick'])) {
            if (is_array($attribs['onclick'])) {
                $click = implode(';', $attribs['onclick']);
            }
            else {
                $click = $attribs['onclick'];
            }
            unset($attribs['onclick']);

            if (strlen($click) && substr($click, -1) != ";") {
                $click .= ';';
            }
        }

        // Check for icon attribute
        if (isset($attribs['icon'])) {
            $icon = '<span class="icon-' . $attribs['icon'] . '"></span> ';
            $text = '<span class="hidden-phone">' . $text . '</span>';
            unset($attribs['icon']);
        }

        // Prepare other attributes
        if (count($attribs)) {
            foreach ($attribs AS $name => $value)
            {
                $attr .= ' ' . $name . '="' . $value . '"';
            }
        }

        // Add submit js to onclick attrib
        if ($list) {
            $click .= "if (document.adminForm.boxchecked.value=='0'){"
                    . "alert('" . addslashes(JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')) . "');"
                    . "}else{"
                    . "Joomla.submitbutton('" . $task . "')"
                    . "}";
        }
        else {
            $click .= "Joomla.submitbutton('" . $task . "');";
        }

        // Generate button
        self::$html[] = '<a class="' . $class . '" onclick="' . $click . '" '
                      . 'href="javascript:void(0);"' . $attr . '>'
                      . $icon . $text
                      . '</a>';
    }


    /**
     * Creates a form task button
     *
     * @param     string     $task       The task to perform
     * @param     string     $text       The button text
     * @param     array      $attribs    Additional html attributes for the button
     *
     * @return    void
     */
    public static function btnClick($js, $text, $attribs = array())
    {
        $class = 'btn';
        $attr  = '';
        $click = '';
        $icon  = '';

        // Prepare class attribute
        if (isset($attribs['class'])) {
            $class .= ' ' . $attribs['class'];
            unset($attribs['class']);
        }


        // Check for icon attribute
        if (isset($attribs['icon'])) {
            $icon = '<span class="icon-' . $attribs['icon'] . '"></span> ';
            $text = '<span class="hidden-phone">' . $text . '</span>';
            unset($attribs['icon']);
        }

        // Prepare other attributes
        if (count($attribs)) {
            foreach ($attribs AS $name => $value)
            {
                $attr .= ' ' . $name . '="' . $value . '"';
            }
        }

        // Generate button
        self::$html[] = '<a class="' . $class . '" onclick="' . $js . '" '
                      . 'href="javascript:void(0);"' . $attr . '>'
                      . $icon . $text
                      . '</a>';
    }


    /**
     * Creates a URL button
     *
     * @param     string     $url        The target URL
     * @param     string     $text       The button text
     * @param     array      $attribs    Additional html attributes for the button
     *
     * @return    void
     */
    public static function btnURL($url, $text, $attribs = array())
    {
        $class = 'btn';
        $attr  = '';
        $icon  = '';

        // Prepare class attribute
        if (isset($attribs['class'])) {
            $class .= ' ' . $attribs['class'];
            unset($attribs['class']);
        }


        // Check for icon attribute
        if (isset($attribs['icon'])) {
            $icon = '<span class="icon-' . $attribs['icon'] . '"></span> ';
            $text = '<span class="hidden-phone">' . $text . '</span>';
            unset($attribs['icon']);
        }

        // Prepare other attributes
        if (count($attribs)) {
            foreach ($attribs AS $name => $value)
            {
                $attr .= ' ' . $name . '="' . $value . '"';
            }
        }

        // Generate button
        self::$html[] = '<a class="' . $class . '" '
                      . 'href="' . $url . '"' . $attr . '>'
                      . $icon . $text
                      . '</a>';
    }


    /**
     * Creates a search field
     *
     * @param     string     $value       The
     * @param     string     $text       The button text
     * @param     boolean    $list       Whether the task requires an item from the list to be selected or not
     * @param     array      $attribs    Additional html attributes for the button
     *
     * @return    void
     */
    public static function search($value = '')
    {
        self::$html[] = '<div class="btn-group hidden-phone">'
                      . '<input type="text" class="input-medium" name="filter_search" id="filter_search" '
                      . 'placeholder="' . JText::_('JSEARCH_FILTER') . '"  value="' . $value . '"'
                      . ' style="margin-bottom:0px !important" />'
                      . '</div>'
                      . '<a class="btn hidden-phone" href="javascript:void(0);" onclick="Joomla.submitbutton(\'\');">'
                      . '<span aria-hidden="true" class="icon-search"></span>'
                      . '</a>'
                      . '';
    }


    public static function selectSortBy($options, $selected = '', $suffix = '')
    {
        self::$html[] = '<div class="btn-group">'
                      . '<select name="sortTable' . $suffix . '" id="sortTable' . $suffix . '" class="inputbox input-medium" onchange="Joomla.orderTable()">'
                      . '<option value="">' . JText::_('JGLOBAL_SORT_BY') . '</option>'
                      . JHtml::_('select.options', $options, 'value', 'text', $selected, true)
                      . '</select>'
                      . '</div>';
    }


    public static function selectOrderBy($selected = 'asc', $suffix = '')
    {
        $select_a = (strtolower($selected) == 'asc'  ? ' selected="selected"' : '');
        $select_d = (strtolower($selected) == 'desc' ? ' selected="selected"' : '');

        self::$html[] = '<div class="btn-group">'
                      . '<select name="directionTable' . $suffix . '" id="directionTable' . $suffix . '" class="input-small" onchange="Joomla.orderTable()">'
                      . '<option value="">' . JText::_('PKGLOBAL_ORDER_BY') . ':</option>'
                      . '<option value="asc" ' . $select_a . '>' . JText::_('PKGLOBAL_ORDER_BY_AZ') . '</option>'
                      . '<option value="desc" ' . $select_d . '>' . JText::_('PKGLOBAL_ORDER_BY_ZA') . '</option>'
                      . '</select>'
                      . '</div>';
    }


    /**
     * Adds custom html code the the toolbar
     *
     * @param     string     $html    The html code to add
     *
     * @return    void
     */
    public static function custom($html)
    {
        self::$html[] = $html;
    }
}
