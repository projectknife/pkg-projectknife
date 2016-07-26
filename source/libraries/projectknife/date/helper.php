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


/**
 * Projectknife Date Helper Class
 *
 */
abstract class PKDateHelper
{
    /**
     * Returns a relative timestamp in days.
     *
     * @param     string    $date    The date to format.
     *
     * @return    string
     */
    public static function relativeDays($date)
    {
        static $now           = null;
        static $txt_today     = null;
        static $txt_yesterday = null;
        static $txt_tomorrow  = null;
        static $txt_in_days   = null;
        static $txt_days_ago  = null;


        if (is_null($now)) {
            $now           = strtotime(JHtml::_('date', 'now', 'd-m-Y'));
            $txt_today     = JText::_('PKGLOBAL_TODAY');
            $txt_yesterday = JText::_('PKGLOBAL_YESTERDAY');
            $txt_tomorrow  = JText::_('PKGLOBAL_TOMORROW');
            $txt_in_days   = JText::_('PKGLOBAL_IN_DAYS');
            $txt_days_ago  = JText::_('PKGLOBAL_DAYS_AGO');
        }

        $diff = strtotime($date) - $now;

        if ($diff < 86400 && $diff >= 0) {
            return $txt_today;
        }

        if ($diff < 0) {
            // In the past
            $diff = abs(floor($diff / 86400));

            if ($diff <= 1) {
                return $txt_yesterday;
            }

            return sprintf($txt_days_ago, $diff);
        }
        else {
            // In the future
            $diff = abs(floor($diff / 86400));

            if ($diff <= 1) {
                return $txt_tomorrow;
            }

            return sprintf($txt_in_days, $diff);
        }
    }
}
