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
    private $menuColumns = array();

    /* Maximum description length */
    protected $descriptionLength = 200;

    /* Max number of brands to display */
    protected $numBrands = 10;

    /* Manufacturer option values are cached */
    protected static $manufacturerOptions = null;

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
    public function getParentCategoryIds()
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
    public function loadCategories($categoryIdsString)
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


    /******* Below functions should be in a child class that is using the above Mega Menu as a base ********/

    /**
     * Builds our sub-categories column for the menu navigation.
     *
     * @param $category
     * @return string
     */
    protected function getChildCategoriesColumn($category)
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


    protected function getCategoryInfoColumn($category)
    {
        // Reload the entire category object because it's only been lazy loaded by Magento and
        // some fields are not available.
        $fullCategory = Mage::getModel('catalog/category')->load($category->getId());

        //$categoryMenuImage = Mage::helper('images')->resizeCategoryImage($fullCategory, 260, 105, $imageSize = "large", $imageType = "thumb");
        $categoryMenuImage = '';
        $html = "";
        $html .= "<h2>" . $fullCategory->getName() . "</h2>";
        $html .= "<p>" . $this->getDescription($fullCategory) . "</p>";
        $html .= "<div class='menu-img'><img src='" . $categoryMenuImage . "' alt='' /></div>";

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
        } else {
            $categoryDescription = $category->getDescription();

            // Trim the description down.
            if (strpos($categoryDescription, ".") !== FALSE) {
                $categoryDescription = substr($categoryDescription, 0, strpos($categoryDescription, ".") + 1); // first fullStop
            }
            // still too long, trim it down, using word boundry
            if (strlen($categoryDescription) > $this->_descriptionLength) {
                $categoryDescription = wordwrap($categoryDescription, $this->_descriptionLength);
                $categoryDescription = substr($categoryDescription, 0, strpos($categoryDescription, "\n"));
            }
        }

        return $categoryDescription;
    }

    /**
     * Displays all the brands related to a manufacturer. We're statically caching attribute options because of the
     * performance overhead.
     *
     * @param $category
     * @return string $html
     */
    protected function getCategoryBrands($category)
    {
        // Reload the entire category object because it's only been lazy loaded by Magento and
        // some fields are not available.
        $fullCategory = Mage::getModel('catalog/category')->load($category->getId());

        // This will be key value pairs of manufacturers that are linked to this category via products.
        $manufacturers = array();

        // Load all the manufacturer attributes. This is cached in a static variable to prevent multiple loads.
        $options = $this->getManufacturerOptions();


        // Load the product collection.
        $productCollection =
            Mage::getResourceModel('catalog/product_collection')
                ->addCategoryFilter($fullCategory)
                ->addAttributeToSelect('manufacturer');

        // Iterate products are retrieve manufacturer
        foreach($productCollection as $product) {

            // Get the manufacturer Id for this product.
            $manufacturerId = $product->getManufacturer();

            if(!empty($manufacturerId)) {
                // If the manufacturer exists then increments the number of hits for this manufacturer
                // otherwise add it for the first time.
                if(array_key_exists($manufacturerId, $manufacturers)) {
                    $manufacturers[$manufacturerId]['numHits']++;
                }
                else {
                    $manufacturers[$manufacturerId] = array(
                        'optionValue' => $options[$manufacturerId],
                        'numHits' => 1,
                    );
                }
            }
        }

        // Sort based on number of hits for the brand.
        uasort($manufacturers, function($a, $b) {
            if($a == $b) {
                return 0;
            }
            return ($a['numHits'] > $b['numHits']) ? -1 : 1;
        });

        // Initialise HTML
        $html = "";
        $counter = 1;
        if(count($manufacturers) > 0) {

            $html .= "<ul>";
            foreach($manufacturers as $key => $man) {

                $fullLink = Mage::getBaseUrl() . $fullCategory->getUrlPath(). "?manufacturer=". $key;

                $html .= "<li><a href='" . $fullLink . "'>" . $man['optionValue']. "</a></li>";

                // They want a limit on the number of brands to be displayed.
                if($counter == self::BRAND_LIMIT) {
                    break;
                }
                $counter++;
            }
            $html .= "</ul>";
        }

        return $html;
    }

    /**
     * We load all the attribute options for manufacturers and then sort them into
     * key value pairs. This is called multiple times on a page laod the static variable
     * is for performance.
     *
     * @return array
     */
    private function getManufacturerOptions()
    {
        // If its null then load it for the first time.
        if(is_null(static::$manufacturerOptions)) {
            $allOptions = Mage::getModel('eav/config')
                ->getAttribute('catalog_product', 'manufacturer')
                ->getSource()
                ->getAllOptions();

            // Initialise array.
            static::$manufacturerOptions = array();

            // re-order them now into key value pairs for quicker access later.
            foreach($allOptions as $option) {
                static::$manufacturerOptions[$option['value']] = $option['label'];
            }
        }

        // Return our array.
        return static::$manufacturerOptions;
    }
}