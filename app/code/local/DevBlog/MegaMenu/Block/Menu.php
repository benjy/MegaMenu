<?php
/**
 *
 *
 * @package    DevBlog_MegaMenu
 * @author     Ben (ben@devblog.com.au)
 */

class DevBlog_MegaMenu_Block_Menu extends Mage_Catalog_Block_Navigation
{
    /* Keep track of our menu columns */
    protected  $menuColumns = array();

    /* Maximum description length */
    protected $descriptionLength = 200;

    /**
     * Build the entire Mega Menu.
     *
     * @return string
     */
    public function getMegaMenu()
    {
        return $this->_renderCategoryMenuHtml($this->getParentCategoryIds(), $level = 0);
    }

    /**
     * Renders a category array into a HTML UL list.
     *
     * @param int[] $categories
     * @param $level
     * @return string
     */
    protected function _renderCategoryMenuHtml($categories, $level)
    {
        // Load all the categories.
        $categories = $this->loadCategories($categories);

        $html = "";
        $activeCategories = array();

        // get all the active menu categories.
        foreach ($categories as $cat) {
            if ($cat->getIsActive() && $cat->getIncludeInMenu()) {
                $activeCategories[] = $cat;
            }
        }

        // counter inside the loop
        $index = 0;

        // Get the category count.
        $count = count($activeCategories);

        // Pull out all active top level categories.
        foreach ($activeCategories as $category) {

            // increment counter.
            $index++;

            // Calculate if its first or last.
            $isFirst = ($index == 1);
            $isLast = ($index == $count);

            // Render this item.
            $html .= $this->_renderCategoryItemHtml($category, $level, $isFirst, $isLast);

        }

        return $html;
    }

    /**
     * Renders a single category into a single HTML LI.
     *
     * @param $category
     * @param $level
     * @param $isFirst
     * @param $isLast
     * @return string
     */
    protected function _renderCategoryItemHtml($category, $level, $isFirst, $isLast)
    {
        // Build li css classe.
        $li_classes = "level" . $level;
        $li_classes .= ($level == 0) ? ' parent level-top' : '';
        $li_classes .= ($isFirst) ? ' first' : '';
        $li_classes .= ($isLast) ? ' last' : '';
        $li_classes .= ($this->isCategoryActive($category)) ? ' active' : '';

        // initalise
        $a_classes = '';

        // If its the top level.
        if ($level == 0) {
            $li_classes .= ' parent';
            $li_classes .= ' level-top';
            $a_classes = " level-top";
        }

        $html = "";
        $html .= "<li class='" . $li_classes . "'>
                     <a class='" . $a_classes . "' href='" . $this->getCategoryUrl($category) . "'>
                     <span>" . $category->getName() . "</span></a>";


        if ($this->hasMenuColumns()) {
            $count = count($this->menuColumns);
            $html .= "<div class='menu-columns columns-" . $count . "'>";
            $html .= $this->getMenuColumnsHtml($category);
            $html .= "</div>";
        }

        $html .= "</li>";

        return $html;
    }


    /**
     * @param $category
     * @return string
     */
    protected function getMenuColumnsHtml($category)
    {
        $html = "";
        $isFirst = true;
        $count = count($this->menuColumns);
        $index = 0;

        foreach ($this->menuColumns as $menuColumnCallback) {
            if (method_exists($this, $menuColumnCallback)) {
                $index++;
                $classes = "menu-column";
                if ($isFirst) {
                    $classes .= " first";
                    $isFirst = false;
                }
                if ($count == $index) {
                    $classes .= " last";
                }

                $html .= "<div class='" . $classes . "'>";
                $html .= call_user_func(array($this, $menuColumnCallback), $category);
                $html .= "</div>";
            }
        }

        return $html;
    }

    /**
     * Add call back functions that generate a column Each callback will be called once
     * per top level menu item.
     *
     * @param $menuColumnCallback
     */
    public function addMenuColumn($menuColumnCallback)
    {
        $this->menuColumns[] = $menuColumnCallback;
    }

    /**
     * Allows you to add multiple columns at once for ease of use.
     * @param $menuColumnCallbacks
     */
    public function addMenuColumns($menuColumnCallbacks)
    {
        if (is_array($menuColumnCallbacks)) {

            $this->menuColumns = $this->menuColumns + $menuColumnCallbacks;
        } else {
            $this->addMenuColumn($menuColumnCallbacks);
        }
    }

    /**
     * If this menu is to have a drop down with columns.
     * @return bool
     */
    public function hasMenuColumns()
    {
        return count($this->menuColumns) > 0;
    }

    /**
     * Get all the parent category ids.
     *
     * @return mixed
     */
    protected function getParentCategoryIds()
    {
        // Get the root category Id.
        $rootCatgoryId = Mage::app()->getWebsite(TRUE)->getDefaultStore()->getRootCategoryId();

        // Load categories as a comma separated string.
        $categoryIdsString = Mage::getModel('catalog/category')->load($rootCatgoryId)->getChildren();

        return $categoryIdsString;
    }


    /**
     * @param $categoryIdsString
     * @return array
     */
    protected function loadCategories($categoryIdsString)
    {
        // Stores our loaded category objects.
        $categories = array();

        // get the arrya of Ids.
        $categoryIds = explode(",", $categoryIdsString);

        // Load the top level category objects.
        foreach($categoryIds as $catId) {
            $categories[] = Mage::getModel('catalog/category')->load($catId);
        }

        return $categories;
    }


    /***** Provide some basic methods to build our columns *******/

    /**
     * Builds our sub-categories column for the menu navigation.
     *
     * @param $category
     * @return string
     */
    protected function getChildCategoriesColumnHtml($category)
    {
        $level = 1;
        $html = "";
        if ($category->hasChildren()) {
            $html .= "<ul class='level" . $level . "'>";

            // Increment the level.
            $level++;

            // Render the child menu.
            $html .= $this->_renderCategoryMenuHtml($category->getChildren(), $level);

            // End child menu item.
            $html .= "</ul>";
        }

        return $html;
    }

    /**
     * Display the category name and information.
     *
     * @TODO add an option to display the category image.
     *
     * @param $category
     * @return string
     */
    protected function getCategoryInfoColumnHtml($category)
    {
        // Reload the entire category object because it's only been lazy loaded by Magento and
        // some fields are not available.
        $fullCategory = Mage::getModel('catalog/category')->load($category->getId());

        $html = "";
        $html .= "<h2>" . $fullCategory->getName() . "</h2>";
        $html .= "<p>" . $this->getDescription($fullCategory) . "</p>";

        return $html;
    }

    /**
     * Try and use the custom Mega Menu description first,
     * If not populated, grab the standard description, but trim it down.
     *
     * @param object $category
     * @return string
     */
    private function getDescription($category)
    {

        if ($category->getMegaMenuDescription()) {
            $categoryDescription = $category->getMegaMenuDescription();
        }
        else {
            $categoryDescription = $category->getDescription();

            // Try trim the description down to the first sentence.
            if (strpos($categoryDescription, ".") !== FALSE) {
                $categoryDescription = substr($categoryDescription, 0, strpos($categoryDescription, ".") + 1); // first fullStop
            }
            // still too long, trim it down, using word boundary
            if (strlen($categoryDescription) > $this->descriptionLength) {
                $categoryDescription = wordwrap($categoryDescription, $this->descriptionLength);
                $categoryDescription = substr($categoryDescription, 0, strpos($categoryDescription, "\n"));
            }
        }

        return $categoryDescription;
    }
}