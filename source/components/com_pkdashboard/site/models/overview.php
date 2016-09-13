<?php
/**
 * @package      pkg_projectknife
 * @subpackage   com_pkdashboard
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2015-2016 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die;


use Joomla\Registry\Registry;

JLoader::register('PKprojectsModelProject', JPATH_ADMINISTRATOR . '/components/com_pkprojects/models/project.php');


class PKdashboardModelOverview extends PKprojectsModelProject
{
    /**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if (!$item) {
            return $item;
        }

        // Split the description into introtext and fulltext
        $item->text      = $item->description;
        $item->introtext = $item->description;
        $item->fulltext  = '';

        if (strlen($item->description)) {
            $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
    		$tagPos  = preg_match($pattern, $item->description);

    		if ($tagPos == 0) {
    			$item->introtext = $item->description;
    			$item->fulltext  = '';
    		}
    		else {
    			list ($item->introtext, $item->fulltext) = preg_split($pattern, $item->description, 2);
    		}
        }

        // Get category title
        if ($item->category_id) {
            $query = $this->_db->getQuery(true);

            $query->select('title')
                  ->from('#__categories')
                  ->where('id = ' . (int) $item->category_id);

            $this->_db->setQuery($query);
            $item->category_title = $this->_db->loadResult();
        }
        else {
            $item->category_title = '';
        }

        // Get tags
        $item->tags = new JHelperTags();
        $item->tags->getItemTags('com_pkprojects.project', $item->id);

        return $item;
    }


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
        $return = $app->get('return', null, 'default', 'base64');

        if (empty($return) || !JUri::isInternal(base64_decode($return))) {
            $return = '';
        }

        $this->setState('return_page', base64_decode($return));
    }
}