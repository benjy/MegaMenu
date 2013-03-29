<?php
    /**
     * DevBlog Mega Menu
     *
     *
     * @package    DevBlog_MegaMenu
     * @author     Ben (ben@devblog.com.au)
     */

class DevBlog_MegaMenu_Block_ThreeColumns extends DevBlog_MegaMenu_Block_Menu
{
    /* Max number of brands to display */
    protected $numBrands = 10;

    /* Manufacturer option values are cached */
    protected static $manufacturerOptions = null;

    /**
     * Setup the three columns.
     */
    public function __construct()
    {
        $this->addMenuColumns(array(
            'getCategoryInfoColumnHtml',
            'getChildCategoriesColumnHtml',
            'getCategoryBrandsHtml',
        ));

        parent::__construct();
    }


    /**
     * Displays all the brands related to a manufacturer. We're statically caching attribute options because of the
     * performance overhead.
     *
     * @param $category
     * @return string $html
     */
    protected function getCategoryBrandsHtml($category)
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
                if($counter == self::$this->numBrands) {
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
