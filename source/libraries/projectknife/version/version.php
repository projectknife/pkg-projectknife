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
 * Version information class for the Projectknife package.
 *
 */
final class PKVersion
{
    /** @var  string  Product name. */
    public $PRODUCT = 'Projectknife';

    /** @var  string  Release version. */
    public $RELEASE = '5.0';

    /** @var  string  Maintenance version. */
    public $DEV_LEVEL = '0';

    /** @var  string  Development status. */
    public $DEV_STATUS = 'Beta';

    /** @var  string  Build number. */
    public $BUILD = '1';

    /** @var  string  Code name. */
    public $CODENAME = 'Gnosh';

    /** @var  string  Release date. */
    public $RELDATE = '11-December-2016';

    /** @var  string  Release time. */
    public $RELTIME = '10:00';

    /** @var  string  Release timezone. */
    public $RELTZ = 'CET';

    /** @var  string  Copyright Notice. */
    public $COPYRIGHT = 'Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.';

    /** @var  string  Link text. */
    public $URL = '<a href="http://www.projectknife.net">Projectknife</a> is free software released under the GNU General Public License.';


    /**
     * Compares two a "PHP standardized" version number against the current Projectfork version.
     *
     * @param     string    $minimum    The minimum version of Projectfork which is compatible.
     *
     * @return    bool                  True if the version is compatible.
     */
    public function isCompatible($minimum)
    {
        return version_compare(PK_VERSION, $minimum, 'ge');
    }


    /**
     * Gets a "PHP standardized" version string for the current Projectfork.
     *
     * @return    string    Version string.
     */
    public function getShortVersion()
    {
        return $this->RELEASE . '.' . $this->DEV_LEVEL;
    }


    /**
     * Gets a version string for the current Projectfork with all release information.
     *
     * @return    string    Complete version string.
     */
    public function getLongVersion()
    {
        return $this->PRODUCT . ' ' . $this->RELEASE . '.' . $this->DEV_LEVEL . ' '
               . $this->DEV_STATUS . ' [ ' . $this->CODENAME . ' ] ' . $this->RELDATE . ' '
               . $this->RELTIME . ' ' . $this->RELTZ;
    }
}
