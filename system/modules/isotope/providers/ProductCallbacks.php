<?php

/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2008-2012 Isotope eCommerce Workgroup
 *
 * @package    Isotope
 * @link       http://www.isotopeecommerce.com
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Isotope;

use Isotope\Model\ProductType;


/**
 * Class ProductCallbacks
 *
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 */
class ProductCallbacks extends \Backend
{

    /**
     * Current object instance (Singleton)
     * @var object
     */
    protected static $objInstance;

    /**
     * paste_button_callback Provider
     * @var mixed
     */
    protected $PasteProductButton;

    /**
     * Product type cache
     * @var array
     */
    protected $arrProductTypes;


    /**
     * Cache number of downloads per product
     * @var array
     */
    protected $arrDownloads;


    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone() {}


    /**
     * Import a back end user and Isotope objects
     */
    protected function __construct()
    {
        parent::__construct();

        $this->import('BackendUser', 'User');
    }


    /**
     * Instantiate the Isotope object
     * @return object
     */
    public static function getInstance()
    {
        if (!is_object(static::$objInstance))
        {
            static::$objInstance = new static();

            static::$objInstance->arrProductTypes = array();
            $blnDownloads = false;
            $blnVariants = false;
            $blnAdvancedPrices = false;

            if (($objProductTypes = ProductType::findAllUsed()) !== null) {
                while ($objProductTypes->next())
                {
                    $objType = $objProductTypes->current();
                    static::$objInstance->arrProductTypes[$objProductTypes->id] = $objType;

                    if ($objType->hasDownloads())
                    {
                        $blnDownloads = true;
                    }

                    if ($objType->hasVariants())
                    {
                        $blnVariants = true;
                    }

                    if ($objType->hasAdvancedPrices())
                    {
                        $blnAdvancedPrices = true;
                    }
                }
            }

            // If no downloads are enabled in any product type, we do not need the option
            if (!$blnDownloads)
            {
                unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['operations']['downloads']);
            }
            else
            {
                // Cache number of downloads
                static::$objInstance->arrDownloads = array();

                $objDownloads = static::$objInstance->Database->query("SELECT pid, COUNT(id) AS total FROM tl_iso_downloads GROUP BY pid");

                while ($objDownloads->next())
                {
                    static::$objInstance->arrDownloads[$objDownloads->pid] = $objDownloads->total;
                }
            }

            // Disable all variant related operations
            if (!$blnVariants)
            {
                unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['global_operations']['toggleVariants']);
                unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['operations']['generate']);
            }

            // Disable prices button if not enabled in any product type
            if (!$blnAdvancedPrices)
            {
                unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['operations']['prices']);
            }

            // Disable related categories if none are defined
            if (static::$objInstance->Database->query("SELECT COUNT(id) AS total FROM tl_iso_related_categories")->total == 0)
            {
                unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['operations']['related']);
            }
        }

        return static::$objInstance;
    }



    ///////////////////////
    //  !onload_callback
    ///////////////////////


    /**
     * Apply advanced filters to product list view
     * @return void
     */
    public function applyAdvancedFilters()
    {
        $session = $this->Session->getData();

        // Store filter values in the session
        foreach ($_POST as $k=>$v)
        {
            if (substr($k, 0, 4) != 'iso_')
            {
                continue;
            }

            // Reset the filter
            if ($k == \Input::post($k))
            {
                unset($session['filter']['tl_iso_products'][$k]);
            }
            // Apply the filter
            else
            {
                $session['filter']['tl_iso_products'][$k] = \Input::post($k);
            }
        }

        $this->Session->setData($session);

        if (!isset($session['filter']['tl_iso_products']))
        {
            return;
        }

        $arrProducts = null;

        // Filter the products
        foreach ($session['filter']['tl_iso_products'] as $k=>$v)
        {
            if (substr($k, 0, 4) != 'iso_')
            {
                continue;
            }

            switch ($k)
            {
                // Show products with or without images
                case 'iso_noimages':
                    $objProducts = \Database::getInstance()->execute("SELECT id FROM tl_iso_products WHERE language='' AND images " . ($v ? "IS NULL" : "IS NOT NULL"));
                    $arrProducts = is_array($arrProducts) ? array_intersect($arrProducts, $objProducts->fetchEach('id')) : $objProducts->fetchEach('id');
                    break;

                // Show products with or without category
                case 'iso_nocategory':
                    $objProducts = \Database::getInstance()->execute("SELECT id FROM tl_iso_products p WHERE pid=0 AND language='' AND (SELECT COUNT(*) FROM tl_iso_product_categories c WHERE c.pid=p.id)" . ($v ? "=0" : ">0"));
                    $arrProducts = is_array($arrProducts) ? array_intersect($arrProducts, $objProducts->fetchEach('id')) : $objProducts->fetchEach('id');
                    break;

                // Show new products
                case 'iso_new':
                    $date = 0;

                    switch ($v)
                    {
                        case 'new_today':
                            $date = strtotime('-1 day');
                            break;

                        case 'new_week':
                            $date = strtotime('-1 week');
                            break;

                        case 'new_month':
                            $date = strtotime('-1 month');
                            break;
                    }

                    $objProducts = \Database::getInstance()->prepare("SELECT id FROM tl_iso_products WHERE language='' AND dateAdded>=?")->execute($date);
                    $arrProducts = is_array($arrProducts) ? array_intersect($arrProducts, $objProducts->fetchEach('id')) : $objProducts->fetchEach('id');
                    break;

                case 'iso_pages':
                    // Filter the products by pages
                    if (!empty($v) && is_array($v))
                    {
                        $objProducts = \Database::getInstance()->execute("SELECT id FROM tl_iso_products p WHERE pid=0 AND language='' AND id IN (SELECT pid FROM tl_iso_product_categories c WHERE c.pid=p.id AND c.page_id IN (" . implode(array_map('intval', $v)) . "))");
                        $arrProducts = is_array($arrProducts) ? array_intersect($arrProducts, $objProducts->fetchEach('id')) : $objProducts->fetchEach('id');
                    }

                default:
                    // !HOOK: add custom advanced filters
                    if (isset($GLOBALS['ISO_HOOKS']['applyAdvancedFilters']) && is_array($GLOBALS['ISO_HOOKS']['applyAdvancedFilters']))
                    {
                        foreach ($GLOBALS['ISO_HOOKS']['applyAdvancedFilters'] as $callback)
                        {
                            $objCallback = \System::importStatic($callback[0]);
                            $arrReturn = $objCallback->$callback[1]($k);

                            if (is_array($arrReturn))
                            {
                                $arrProducts = is_array($arrProducts) ? array_intersect($arrProducts, $arrReturn) : $arrReturn;
                                break;
                            }
                        }
                    }

                    \System::log('Advanced product filter "' . $k . '" not found.', __METHOD__, TL_ERROR);
                    break;
            }
        }

        if (is_array($arrProducts) && empty($arrProducts))
        {
            $arrProducts = array(0);
        }

        $GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root'] = $arrProducts;
    }


    /**
     * Check permissions for that entry
     * @return void
     */
    public function checkPermission()
    {
        if (\Input::get('act') != '' && (\Input::get('mode') == '' || is_numeric(\Input::get('mode'))))
        {
            $GLOBALS['TL_DCA']['tl_iso_products']['config']['closed'] = false;
        }

        // Hide "add variant" button if no products with variants enabled exist
        if (\Database::getInstance()->query("SELECT COUNT(*) AS total FROM tl_iso_products p LEFT JOIN tl_iso_producttypes t ON p.type=t.id WHERE t.variants='1'")->total == 0)
        {
            unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['global_operations']['new_variant']);
        }

        $session = $this->Session->getData();

        $arrProducts = \Isotope\Backend::getAllowedProductIds();

        // Method will return true if no limits should be applied (e.g. user is admin)
        if (true === $arrProducts)
        {
            return;
        }

        // Filter by product type and group permissions
        if (empty($arrProducts))
        {
            unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['global_operations']['new_variant']);
            unset($session['CLIPBOARD']['tl_iso_products']);
            $session['CURRENT']['IDS'] = array();
            $GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['filter'][] = array('id=?', 0);

            if (false === $arrProducts)
            {
                unset($GLOBALS['TL_DCA']['tl_iso_products']['list']['global_operations']['new_product']);
            }
        }
        else
        {
            // Maybe another function has already set allowed product IDs
            if (is_array($GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root']))
            {
                $arrProducts = array_intersect($GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root'], $arrProducts);
            }

            $GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root'] = $arrProducts;

            // Set allowed product IDs (edit multiple)
            if (is_array($session['CURRENT']['IDS']))
            {
                $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root']);
            }

            // Set allowed clipboard IDs
            if (is_array($session['CLIPBOARD']['tl_iso_products']['id']))
            {
                $session['CLIPBOARD']['tl_iso_products']['id'] = array_intersect($session['CLIPBOARD']['tl_iso_products']['id'], $GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root'], \Database::getInstance()->query("SELECT id FROM tl_iso_products WHERE pid=0")->fetchEach('id'));

                if (empty($session['CLIPBOARD']['tl_iso_products']['id']))
                {
                    unset($session['CLIPBOARD']['tl_iso_products']);
                }
            }

            // Overwrite session
            $this->Session->setData($session);

            if (\Input::get('id') > 0 && !in_array(\Input::get('id'), $GLOBALS['TL_DCA']['tl_iso_products']['list']['sorting']['root']))
            {
                \System::log('Cannot access product ID '.\Input::get('id'), __METHOD__, TL_ERROR);
                \Controller::redirect('contao/main.php?act=error');
            }
        }
    }


    /**
     * Build palette for the current product type/variant
     * @param object
     * @return void
     */
    public function buildPaletteString($dc)
    {
        $this->loadDataContainer('tl_iso_attributes');

        if (\Input::get('act') == '' && \Input::get('key') == '' || \Input::get('act') == 'select') {
            return;
        }

        $arrFields = &$GLOBALS['TL_DCA']['tl_iso_products']['fields'];
        $arrAttributes = &$GLOBALS['TL_DCA']['tl_iso_products']['attributes'];

        // Unset foreign key to activate options_callback
        unset($arrFields['type']['foreignKey']);

        // Set default product type
        $arrFields['type']['default'] = (int) \Database::getInstance()->execute("SELECT id FROM tl_iso_producttypes WHERE fallback='1'" . ($this->User->isAdmin ? '' : (" AND id IN (" . implode(',', $this->User->iso_product_types) . ")")))->id;

        // Set default tax class
        // @todo this should be done in an oncreate callback.
        //$arrFields['tax_class']['default'] = (int) \Database::getInstance()->execute("SELECT id FROM tl_iso_tax_class WHERE fallback='1'")->id;


        $arrTypes = $this->arrProductTypes;
        $blnVariants = false;
        $act = \Input::get('act');
        $blnSingleRecord = $act === 'edit' || $act === 'show';

        if (\Input::get('id') > 0) {
            $objProduct = \Database::getInstance()->prepare("SELECT p1.pid, p1.type, p2.type AS parent_type FROM tl_iso_products p1 LEFT JOIN tl_iso_products p2 ON p1.pid=p2.id WHERE p1.id=?")->execute(\Input::get('id'));

            if ($objProduct->numRows) {
                $arrTypes = array($this->arrProductTypes[($objProduct->pid > 0 ? $objProduct->parent_type : $objProduct->type)]);

                if ($objProduct->pid > 0 || $act != 'edit') {
                    $blnVariants = true;
                }
            }
        }

        foreach ($arrTypes as $objType)
        {
	        if(!is_object($objType))
	        {
		    	continue;
	        }
	        
            // Enable advanced prices
            if ($blnSingleRecord && $objType->hasAdvancedPrices()) {
                $arrFields['prices']['exclude'] = $arrFields['price']['exclude'];
                $arrFields['prices']['attributes'] = $arrFields['price']['attributes'];
                $arrFields['price'] = $arrFields['prices'];
                unset($arrFields['tax_class']);
            }

            // Register callback to version/restore a price
            else {
                $GLOBALS['TL_DCA']['tl_iso_products']['config']['onversion_callback'][] = array('Isotope\ProductCallbacks', 'versionPriceAndTaxClass');
                $GLOBALS['TL_DCA']['tl_iso_products']['config']['onrestore_callback'][] = array('Isotope\ProductCallbacks', 'restorePriceAndTaxClass');
            }

            $arrInherit = array();
            $arrPalette = array();

            if ($blnVariants) {
                $arrConfig = deserialize($objType->variant_attributes, true);
                $arrEnabled = $objType->getVariantAttributes();
            } else {
                $arrConfig = deserialize($objType->attributes, true);
                $arrEnabled = $objType->getAttributes();
            }

            // Go through each enabled field and build palette
            foreach ($arrEnabled as $name) {

                // Do not show customer defined fields
                if (null !== $arrAttributes[$name] && $arrAttributes[$name]->isCustomerDefined()) {
                    continue;
                }

                // Variant fields can only be edited in variant mode
                if (null !== $arrAttributes[$name] && $arrAttributes[$name]->isVariantOption() && !$blnVariants) {
                    continue;
                }

                // Field cannot be edited in variant
                if ($blnVariants && $arrAttributes[$name]->inherit) {
                    continue;
                }

                $arrPalette[$arrConfig[$name]['legend']][] = $name;

                // Apply product type attribute config
                if ($arrConfig[$name]['tl_class'] != '') {
                    $arrFields[$name]['eval']['tl_class'] = $arrConfig[$name]['tl_class'];
                }

                if ($arrConfig[$name]['mandatory'] > 0) {
                    $arrFields[$name]['eval']['mandatory'] = $arrConfig[$name]['mandatory'] == 1 ? false : true;
                }

                if ($blnVariants && !$arrAttributes[$name]->isVariantOption() && !in_array($name, array('price', 'published', 'start', 'stop'))) {
                    $arrInherit[$name] = Isotope::formatLabel('tl_iso_products', $name);
                }
            }

            $arrLegends = array();

            // Build
            foreach ($arrPalette as $legend=>$fields) {
                $arrLegends[] = '{' . $legend . '},' . implode(',', $fields);
            }

            // Set inherit options
            $arrFields['inherit']['options'] = $arrInherit;

            // Add palettes
            $GLOBALS['TL_DCA']['tl_iso_products']['palettes'][$objType->id] = ($blnVariants ? 'inherit,' : '') . implode(';', $arrLegends);
        }

        if ($act !== 'edit') {
            $arrFields['inherit']['exclude'] = true;
            $arrFields['prices']['exclude'] = true;
        }

        // Remove non-active fields from multi-selection
        if ($blnVariants && !$blnSingleRecord) {
            $arrInclude = call_user_func_array('array_merge', $arrPalette);

            foreach ($arrFields as $name => $config) {
                if ($arrFields[$name]['attributes']['legend'] != '' && !in_array($name, $arrInclude)) {
                    $arrFields[$name]['exclude'] = true;
                }
            }
        }
    }


    /**
     * Load the default product type
     * @param object
     * @return void
     */
    public function loadDefaultProductType($dc)
    {
        if (\Input::get('act') !== 'create')
        {
            return;
        }

        if (($intProductTypeId = \Isotope\Backend::getProductTypeForGroup($this->Session->get('iso_products_gid'))) !== false)
        {
            $GLOBALS['TL_DCA']['tl_iso_products']['fields']['type']['default'] = $intProductTypeId;
        }
    }


    /**
     * Add a script that will handle "move all" action
     */
    public function addMoveAllFeature()
    {
        if (\Input::get('act') == 'select' && !\Input::get('id'))
        {
            $GLOBALS['TL_MOOTOOLS'][] = "
<script>
window.addEvent('domready', function() {
  $('cut').addEvents({
    'click': function(e) {
      e.preventDefault();
      Isotope.openModalGroupSelector({'width':765,'title':'".specialchars($GLOBALS['TL_LANG']['MSC']['groupPicker'])."','url':'system/modules/isotope/public/group.php?do=".\Input::get('do')."&amp;table=tl_iso_groups&amp;field=gid&amp;value=".$this->Session->get('iso_products_gid')."','action':'moveProducts','trigger':$(this)});
    },
    'closeModal': function() {
      var form = $('tl_select'),
          hidden = new Element('input', { type:'hidden', name:'cut' }).inject(form.getElement('.tl_formbody'), 'top');
      form.submit();
    }
  });
});
</script>";
        }
    }


    /**
     * Change the displayed columns in the variants view
     * @todo should only show variant columns of the current product type
     * @todo use $GLOBALS['ISO_CONFIG']['variant_options']
     */
    public function changeVariantColumns()
    {
        if (!\Input::get('id'))
        {
            return;
        }

        $arrColumns = array();

        // Collect only variant-specific fields
        foreach ($GLOBALS['TL_DCA']['tl_iso_products']['fields'] as $strName=>$arrField)
        {
            if ($arrField['eval']['variant_option'])
            {
                $arrColumns[] = $strName;
            }
        }

        if (!empty($arrColumns))
        {
            $arrDefault = array('images', 'name');

            // Limit the number of columns if there are more than 3
            if (count($arrColumns) > 3)
            {
                $GLOBALS['TL_DCA']['tl_iso_products']['list']['label']['fields'] = $arrDefault;
                $GLOBALS['TL_DCA']['tl_iso_products']['list']['label']['variantFields'] = $arrColumns;
                return;
            }

            $GLOBALS['TL_DCA']['tl_iso_products']['list']['label']['fields'] = array_merge($arrDefault, $arrColumns);
        }
    }



    ///////////////////////
    //  !oncopy_callback
    ///////////////////////


    /**
     * Update sorting of product in categories when duplicating, move new product to the bottom
     * @param integer
     * @param object
     * @link http://www.contao.org/callbacks.html#oncopy_callback
     */
    public function updateCategorySorting($insertId, $dc)
    {
        $objCategories = \Database::getInstance()->query("SELECT c1.*, MAX(c2.sorting) AS max_sorting FROM tl_iso_product_categories c1 LEFT JOIN tl_iso_product_categories c2 ON c1.page_id=c2.page_id WHERE c1.pid=" . (int) $insertId . " GROUP BY c1.page_id");

        while ($objCategories->next())
        {
            \Database::getInstance()->query("UPDATE tl_iso_product_categories SET sorting=" . ($objCategories->max_sorting + 128) . " WHERE id=" . $objCategories->id);
        }
    }



    /////////////////////////
    //  !onsubmit_callback
    /////////////////////////


    /**
     * Store the date when the product has been added
     * @param DataContainer
     * @return void
     */
    public function storeDateAdded(\DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord || $dc->activeRecord->dateAdded > 0)
        {
            return;
        }

        \Database::getInstance()->prepare("UPDATE tl_iso_products SET dateAdded=? WHERE id=?")->execute(time(), $dc->id);
    }


    /////////////////////////
    //  !onversion_callback
    /////////////////////////


    /**
     * Save categories history when creating new version of a product
     * @param   string
     * @param   int
     * @param   \DataContainer
     */
    public function versionProductCategories($strTable, $intId, $dc)
    {
        if ($strTable != 'tl_iso_products') {
            return;
        }

        $arrCategories = \Database::getInstance()->query("SELECT * FROM tl_iso_product_categories WHERE pid=$intId")->fetchAllAssoc();

        $this->createSubtableVersion($strTable, $intId, 'tl_iso_product_categories', $arrCategories);
    }

    /**
     * Save prices history when creating a new version of a product
     * @param   string
     * @param   int
     * @param   \DataContainer
     */
    public function versionPriceAndTaxClass($strTable, $intId, $dc)
    {
        if ($strTable != 'tl_iso_products') {
            return;
        }

        $arrData = array('prices'=>array(), 'tiers'=>array());

        $objPrices = \Database::getInstance()->query("SELECT * FROM tl_iso_prices WHERE pid=$intId");

        if ($objPrices->numRows) {
            $objTiers = \Database::getInstance()->query("SELECT * FROM tl_iso_price_tiers WHERE pid IN (" . implode(',', $objPrices->fetchEach('id')) . ")");

            $arrData['prices'] = $objPrices->fetchAllAssoc();
            $arrData['tiers'] = $objTiers->fetchAllAssoc();
        }

        $this->createSubtableVersion($strTable, $intId, 'tl_iso_prices', $arrData);
    }


    /////////////////////////
    //  !onrestore_callback
    /////////////////////////

    /**
     * Restore categories when restoring a product
     * @param   int
     * @param   string
     * @param   array
     * @param   int
     */
    public function restoreProductCategories($intId, $strTable, $arrData, $intVersion)
    {
        if ($strTable != 'tl_iso_products') {
            return;
        }

        $arrData = $this->findSubtableVersion('tl_iso_product_categories', $intId, $intVersion);

        if (null !== $arrData) {
            \Database::getInstance()->query("DELETE FROM tl_iso_product_categories WHERE pid=$intId");

            foreach ($arrData as $arrRow) {
                \Database::getInstance()->prepare("INSERT INTO tl_iso_product_categories %s")->set($arrRow)->executeUncached();
            }
        }
    }

    /**
     * Restore pricing information when restoring a product
     * @param   int
     * @param   string
     * @param   array
     * @param   int
     */
    public function restorePriceAndTaxClass($intId, $strTable, $arrData, $intVersion)
    {
        if ($strTable != 'tl_iso_products') {
            return;
        }

        $arrData = $this->findSubtableVersion('tl_iso_prices', $intId, $intVersion);

        if (null !== $arrData) {
            \Database::getInstance()->query("DELETE FROM tl_iso_price_tiers WHERE pid IN (SELECT id FROM tl_iso_prices WHERE pid=$intId)");
            \Database::getInstance()->query("DELETE FROM tl_iso_prices WHERE pid=$intId");

            foreach ($arrData['prices'] as $arrRow) {
                \Database::getInstance()->prepare("INSERT INTO tl_iso_prices %s")->set($arrRow)->executeUncached();
            }

            foreach ($arrData['tiers'] as $arrRow) {
                \Database::getInstance()->prepare("INSERT INTO tl_iso_price_tiers %s")->set($arrRow)->executeUncached();
            }
        }
    }



    //////////////////////
    //  !panel_callback
    //////////////////////


    /**
     * Generate product filter buttons and return them as HTML
     * @return string
     */
    public function generateFilterButtons()
    {
        $session = $this->Session->getData();
        $arrPages = (array) $session['filter']['tl_iso_products']['iso_pages'];

        return '
<div class="tl_filter iso_filter tl_subpanel">
<input type="button" id="groupFilter" class="tl_submit' . ($this->Session->get('iso_products_gid') ? ' active' : '') . '" onclick="Backend.getScrollOffset();Isotope.openModalGroupSelector({\'width\':765,\'title\':\''.specialchars($GLOBALS['TL_LANG']['tl_iso_products']['groups'][0]).'\',\'url\':\'system/modules/isotope/public/group.php?do='.\Input::get('do').'&amp;table=tl_iso_groups&amp;field=gid&amp;value='.$this->Session->get('iso_products_gid').'\',\'action\':\'filterGroups\'});return false" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['filterByGroups']).'">
<input type="button" id="pageFilter" class="tl_submit' . (!empty($arrPages) ? ' active' : '') . '" onclick="Backend.getScrollOffset();Isotope.openModalPageSelector({\'width\':765,\'title\':\''.specialchars($GLOBALS['TL_LANG']['MOD']['page'][0]).'\',\'url\':\'contao/page.php?do='.\Input::get('do').'&amp;table=tl_iso_products&amp;field=pages&amp;value='.implode(',', $arrPages).'\',\'action\':\'filterPages\'});return false" value="'.specialchars($GLOBALS['TL_LANG']['MSC']['filterByPages']).'">
</div>';
    }


    /**
     * Generate advanced filter panel and return them as HTML
     * @return string
     */
    public function generateAdvancedFilters()
    {
        $session = $this->Session->getData();

        // Filters
        $arrFilters = array
        (
            'iso_noimages' => array
            (
                'name'    => 'iso_noimages',
                'label'   => $GLOBALS['TL_LANG']['tl_iso_products']['filter_noimages'],
                'options' => array(''=>$GLOBALS['TL_LANG']['MSC']['no'], 1=>$GLOBALS['TL_LANG']['MSC']['yes'])
            ),
            'iso_nocategory' => array
            (
                'name'    => 'iso_nocategory',
                'label'   => $GLOBALS['TL_LANG']['tl_iso_products']['filter_nocategory'],
                'options' => array(''=>$GLOBALS['TL_LANG']['MSC']['no'], 1=>$GLOBALS['TL_LANG']['MSC']['yes'])
            ),
            'iso_new' => array
            (
                'name'    => 'iso_new',
                'label'   => $GLOBALS['TL_LANG']['tl_iso_products']['filter_new'],
                'options' => array('new_today'=>$GLOBALS['TL_LANG']['tl_iso_products']['filter_new_today'], 'new_week'=>$GLOBALS['TL_LANG']['tl_iso_products']['filter_new_week'], 'new_month'=>$GLOBALS['TL_LANG']['tl_iso_products']['filter_new_month'])
            )
        );

        $strBuffer = '
<div class="tl_filter iso_filter tl_subpanel">
<strong>' . $GLOBALS['TL_LANG']['tl_iso_products']['filter'] . '</strong>' . "\n";

        // Generate filters
        foreach ($arrFilters as $arrFilter)
        {
            $strOptions = '
  <option value="' . $arrFilter['name'] . '">' . $arrFilter['label'] . '</option>
  <option value="' . $arrFilter['name'] . '">---</option>' . "\n";

            // Generate options
            foreach ($arrFilter['options'] as $k=>$v)
            {
                $strOptions .= '  <option value="' . $k . '"' . (($session['filter']['tl_iso_products'][$arrFilter['name']] === (string) $k) ? ' selected' : '') . '>' . $v . '</option>' . "\n";
            }

            $strBuffer .= '<select name="' . $arrFilter['name'] . '" id="' . $arrFilter['name'] . '" class="tl_select' . (isset($session['filter']['tl_iso_products'][$arrFilter['name']]) ? ' active' : '') . '">
' . $strOptions . '
</select>' . "\n";
        }

        return $strBuffer . '</div>';
    }


    //////////////////////
    //  !label_callback
    //////////////////////


    /**
     * Generate a product label and return it as HTML string
     * @param array
     * @param string
     * @param object
     * @param array
     * @return string
     */
    public function getRowLabel($row, $label, $dc, $args)
    {
        $arrImages = deserialize($row['images']);
        $args[0] = '&nbsp;';

        // Add an image
        if (is_array($arrImages) && !empty($arrImages))
        {
            foreach ($arrImages as $image)
            {
                $strImage = 'isotope/' . strtolower(substr($image['src'], 0, 1)) . '/' . $image['src'];

                if (!is_file(TL_ROOT . '/' . $strImage))
                {
                    continue;
                }

                $size = @getimagesize(TL_ROOT . '/' . $strImage);

                $args[0] = sprintf('<a href="%s" onclick="Backend.openModalImage({\'width\':%s,\'title\':\'%s\',\'url\':\'%s\'});return false"><img src="%s" alt="%s" align="left"></a>',
                                    $strImage, $size[0], str_replace("'", "\\'", $row['name']), $strImage,
                                    \Image::get($strImage, 50, 50, 'crop'), $image['alt']);
                break;
            }
        }

        // Add a variants link
        if (!$row['pid'])
        {
            $args[1] = sprintf('<a href="%s" title="%s">%s</a>', ampersand(\Environment::get('request')) . '&amp;id=' . $row['id'], specialchars($GLOBALS['TL_LANG']['tl_iso_products']['showVariants']), $row['name']);
        }

        // Limit the number of columns
        if ($row['pid'] && isset($GLOBALS['TL_DCA']['tl_iso_products']['list']['label']['variantFields']))
        {
            $attributes = array();

            foreach ($GLOBALS['TL_DCA']['tl_iso_products']['list']['label']['variantFields'] as $field)
            {
                $attributes[] = '<strong>' . $GLOBALS['TL_DCA']['tl_iso_products']['fields'][$field]['label'][0] . ':</strong>&nbsp;' . $GLOBALS['TL_DCA']['tl_iso_products']['fields'][$field]['options'][$row[$field]];
            }

            $args[1] .= '<br>' . implode(', ', $attributes);
        }

        return $args;
    }



    ///////////////////////////////////////////
    //  !button_callback (global_operations)
    ///////////////////////////////////////////


    /**
     * Hide "product groups" button for non-admins
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param array
     * @return string
     */
    public function groupsButton($href, $label, $title, $class, $attributes, $table, $root)
    {
        if (!$this->User->isAdmin && (!is_array($this->User->iso_groupp) || empty($this->User->iso_groupp)))
        {
            return '';
        }

        return '<a href="' . $this->addToUrl('&amp;' . $href) . '" class="header_icon" title="' . specialchars($title) . '"' . $attributes . '>' . specialchars($label) . '</a>';
    }



    ///////////////////////////////////////////
    //  !button_callback (operations)
    ///////////////////////////////////////////


    /**
     * Hide variant buttons for product types without variant support
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function variantsButton($row, $href, $label, $title, $icon, $attributes)
    {
        if ($row['pid'] > 0 || null === $this->arrProductTypes[$row['type']] || !$this->arrProductTypes[$row['type']]->hasVariants())
        {
            return '';
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
    }


    /**
     * Hide "related" button for variants
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function relatedButton($row, $href, $label, $title, $icon, $attributes)
    {
        if ($row['pid'] > 0)
        {
            return '';
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
    }


    /**
     * Show/hide the downloads button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function downloadsButton($row, $href, $label, $title, $icon, $attributes)
    {
        if (null === $this->arrProductTypes[$row['type']] || !$this->arrProductTypes[$row['type']]->hasDownloads())
        {
            return '';
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars(sprintf($GLOBALS['TL_DCA']['tl_iso_products']['list']['operations']['downloads']['label'][2], (int) $this->arrDownloads[$row['id']]) . $title).'"'.$attributes.'>'.$this->generateImage($icon, $label) .'</a> ';
    }


    /**
     * Show/hide the prices button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function pricesButton($row, $href, $label, $title, $icon, $attributes)
    {
        if (null === $this->arrProductTypes[$row['type']] || !$this->arrProductTypes[$row['type']]->hasAdvancedPrices())
        {
            return '';
        }

        $arrAttributes = $row['pid'] > 0 ? $this->arrProductTypes[$row['type']]->getVariantAttributes() : $this->arrProductTypes[$row['type']]->getAttributes();

        if (!in_array('price', $arrAttributes))
        {
            return '';
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
    }



    ////////////////////////
    //  !options_callback
    ////////////////////////


    /**
     * Returns all allowed product types as array
     * @param DataContainer
     * @return array
     */
    public function getProductTypes(\DataContainer $dc)
    {
        $objUser = \BackendUser::getInstance();
        $arrTypes = $objUser->iso_product_types;

        if (!$objUser->isAdmin && (!is_array($arrTypes) || empty($arrTypes)))
        {
            $arrTypes = array(0);
        }

        $arrProductTypes = array();
        $objProductTypes = \Database::getInstance()->execute("SELECT id,name FROM tl_iso_producttypes WHERE tstamp>0" . ($objUser->isAdmin ? '' : (" AND id IN (" . implode(',', $arrTypes) . ")")) . " ORDER BY name");

        while ($objProductTypes->next())
        {
            $arrProductTypes[$objProductTypes->id] = $objProductTypes->name;
        }

        return $arrProductTypes;
    }



    /////////////////////
    //  !load_callback
    /////////////////////


    /**
     * Load page IDs from tl_iso_product_categories table
     * @param   mixed
     * @param   DataContainer
     * @return  mixed
     */
    public function loadProductCategories($varValue, \DataContainer $dc)
    {
        $objCategories = \Database::getInstance()->execute("SELECT * FROM tl_iso_product_categories WHERE pid={$dc->id}");

        $this->initializeSubtableVersion($dc->table, $dc->id, 'tl_iso_product_categories', $objCategories->fetchAllAssoc());

        return $objCategories->fetchEach('page_id');
    }

    /**
     * Load price from prices subtable
     * @param   mixed
     * @param   DataContainer
     * @return  mixed
     */
    public function loadPrice($varValue, \DataContainer $dc)
    {
        $objPrice = \Database::getInstance()->query("SELECT t.id, p.id AS pid, t.price FROM tl_iso_prices p LEFT JOIN tl_iso_price_tiers t ON p.id=t.pid AND t.min=1 WHERE p.pid={$dc->id} AND p.config_id=0 AND p.member_group=0 AND p.start='' AND p.stop=''");

        if (!$objPrice->numRows) {
            return '0.00';
        }

        return $objPrice->price;
    }

    /**
     * Load tax class from prices subtable
     * @param   mixed
     * @param   DataContainer
     * @return  mixed
     */
    public function loadTaxClass($varValue, \DataContainer $dc)
    {
        return (int) \Database::getInstance()->query("SELECT tax_class FROM tl_iso_prices WHERE pid={$dc->id} AND config_id=0 AND member_group=0 AND start='' AND stop=''")->tax_class;
    }



    /////////////////////
    //  !save_callback
    /////////////////////


    /**
     * Save page ids to tl_iso_product_categories table. This allows to retrieve all products associated to a page.
     * @param   mixed
     * @param   DataContainer
     * @return  mixed
     */
    public function saveProductCategories($varValue, \DataContainer $dc)
    {
        $arrIds = deserialize($varValue);

        if (is_array($arrIds) && !empty($arrIds))
        {
            $time = time();

            if (\Database::getInstance()->query("DELETE FROM tl_iso_product_categories WHERE pid={$dc->id} AND page_id NOT IN (" . implode(',', $arrIds) . ")")->affectedRows > 0) {
                $dc->createNewVersion = true;
            }

            $objPages = \Database::getInstance()->execute("SELECT page_id FROM tl_iso_product_categories WHERE pid={$dc->id}");
            $arrIds = array_diff($arrIds, $objPages->fetchEach('page_id'));

            if (!empty($arrIds)) {
                foreach ($arrIds as $id) {
                    $sorting = (int) \Database::getInstance()->executeUncached("SELECT MAX(sorting) AS sorting FROM tl_iso_product_categories WHERE page_id=$id")->sorting + 128;
                    \Database::getInstance()->query("INSERT INTO tl_iso_product_categories (pid,tstamp,page_id,sorting) VALUES ({$dc->id}, $time, $id, $sorting)");
                }

                $dc->createNewVersion = true;
            }
        }
        else
        {
            if (\Database::getInstance()->query("DELETE FROM tl_iso_product_categories WHERE pid={$dc->id}")->affectedRows > 0) {
                $dc->createNewVersion = true;
            }
        }

        return '';
    }

    /**
     * Save price to the prices subtable
     * @param   mixed
     * @param   DataContainer
     * @return  mixed
     */
    public function savePrice($varValue, \DataContainer $dc)
    {
        $time = time();
        $objPrice = \Database::getInstance()->query("SELECT t.id, p.id AS pid, t.price FROM tl_iso_prices p LEFT JOIN tl_iso_price_tiers t ON p.id=t.pid AND t.min=1 WHERE p.pid={$dc->id} AND p.config_id=0 AND p.member_group=0 AND p.start='' AND p.stop=''");

        // Price tier record already exists, update it
        if ($objPrice->numRows && $objPrice->id > 0) {

            if ($objPrice->price != $varValue) {
                \Database::getInstance()->prepare("UPDATE tl_iso_price_tiers SET tstamp=$time, price=? WHERE id=?")->executeUncached($varValue, $objPrice->id);

                $dc->createNewVersion = true;
            }

        } else {

            $intPrice = $objPrice->pid;

            // Neither price tier nor price record exist, must add both
            if (!$objPrice->numRows) {
                $intPrice = \Database::getInstance()->query("INSERT INTO tl_iso_prices (pid,tstamp) VALUES ($dc->id, $time)")->insertId;
            }

            \Database::getInstance()->prepare("INSERT INTO tl_iso_price_tiers (pid,tstamp,min,price) VALUES ($intPrice, $time, 1, ?)")->executeUncached($varValue);

            $dc->createNewVersion = true;
        }

        return '';
    }

    /**
     * Save tax_class to the prices subtable
     * @param   mixed
     * @param   DataContainer
     * @return  mixed
     */
    public function saveTaxClass($varValue, \DataContainer $dc)
    {
        $time = time();
        $objPrice = \Database::getInstance()->query("SELECT id, tax_class FROM tl_iso_prices WHERE pid={$dc->id} AND config_id=0 AND member_group=0 AND start='' AND stop=''");

        if ($objPrice->numRows == 0) {

            \Database::getInstance()->prepare("INSERT INTO tl_iso_prices (pid,tstamp,tax_class) VALUES ($dc->id, $time, ?)")->executeUncached($varValue);

            $dc->createNewVersion = true;

        } elseif ($objPrice->tax_class != $varValue) {

            \Database::getInstance()->prepare("UPDATE tl_iso_prices SET tstamp=$time, tax_class=? WHERE id=?")->executeUncached($varValue, $objPrice->id);

            $dc->createNewVersion = true;
        }

        return '';
    }


    /**
     * Autogenerate a product alias if it has not been set yet
     * @param mixed
     * @param DataContainer
     * @return string
     * @throws Exception
     */
    public function generateAlias($varValue, \DataContainer $dc)
    {
        $autoAlias = false;

        // Generate alias if there is none
        if ($varValue == '')
        {
            $autoAlias = true;
            $varValue = standardize(\Input::post('name'));

            if ($varValue == '')
            {
                $varValue = standardize(\Input::post('sku'));
            }

            if ($varValue == '')
            {
                $varValue = strlen($dc->activeRecord->name) ? standardize($dc->activeRecord->name) : standardize($dc->activeRecord->sku);
            }

            if ($varValue == '')
            {
                $varValue = $dc->id;
            }
        }

        $objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_iso_products WHERE id=? OR alias=?")
                                   ->execute($dc->id, $varValue);

        // Check whether the product alias exists
        if ($objAlias->numRows > 1)
        {
            if (!$autoAlias)
            {
                throw new OverflowException(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '.' . $dc->id;
        }

        return $varValue;
    }

    /**
     * Create initial version record if it does not exist
     * @param   string
     * @param   int
     * @param   string
     * @param   array
     */
    protected function initializeSubtableVersion($strTable, $intId, $strSubtable, $arrData)
    {
        $objVersion = \Database::getInstance()->prepare("SELECT COUNT(*) AS count FROM tl_version WHERE fromTable=? AND pid=?")
                                     ->limit(1)
                                     ->executeUncached($strSubtable, $intId);

        if ($objVersion->count < 1)
        {
            $this->createSubtableVersion($strTable, $intId, $strSubtable, $arrData);
        }
    }

    /**
     * Create a new subtable version record
     * @param   string
     * @param   int
     * @param   string
     * @param   array
     */
    protected function createSubtableVersion($strTable, $intId, $strSubtable, $arrData)
    {
        $objVersion = \Database::getInstance()->prepare("SELECT * FROM tl_version WHERE pid=? AND fromTable=? ORDER BY version DESC")
                                     ->limit(1)
                                     ->executeUncached($intId, $strTable);

        // Parent table must have a version
        if ($objVersion->numRows == 0) {
            return;
        }

        \Database::getInstance()->prepare("UPDATE tl_version SET active='' WHERE pid=? AND fromTable=?")
                       ->execute($intId, $strSubtable);

        \Database::getInstance()->prepare("INSERT INTO tl_version (pid, tstamp, version, fromTable, username, userid, description, editUrl, active, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)")
                       ->execute($objVersion->pid, $objVersion->tstamp, $objVersion->version, $strSubtable, $objVersion->username, $objVersion->userid, $objVersion->description, $objVersion->editUrl, serialize($arrData));
    }

    /**
     * Find a subtable version record
     * @param   string
     * @param   int
     * @param   string
     */
    protected function findSubtableVersion($strTable, $intPid, $intVersion)
    {
        $objVersion = \Database::getInstance()->prepare("SELECT data FROM tl_version WHERE fromTable=? AND pid=? AND version=?")
                                     ->limit(1)
                                     ->execute($strTable, $intPid, $intVersion);

        if (!$objVersion->numRows) {
            return null;
        }

        $arrData = deserialize($objVersion->data);

        if (!is_array($arrData)) {
            return null;
        }

        return $arrData;
    }
}
