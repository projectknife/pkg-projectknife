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


// Run this code only once
if (defined('PK_LIBRARY')) {
    return;
}

// Make sure the cms libraries are loaded
if (!defined('JPATH_PLATFORM')) {
    require_once dirname(__FILE__) . '/../cms.php';
}

// Register the Projectknife library
JLoader::registerPrefix('PK', JPATH_LIBRARIES . '/projectknife');
JFormHelper::addFieldPath(JPATH_LIBRARIES . '/projectknife/form/fields');

// Define version
$v = new PKVersion();

define('PK_VERSION', $v->getShortVersion());
define('PK_LIBRARY', 1);