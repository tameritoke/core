<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Isotope eCommerce Workgroup 2009-2011
 * @author     Kamil Kuźmiński <kamil.kuzminski@gmail.com>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


class Wishlist extends Frontend
{

	/**
	 * Wishlist object
	 * @var object
	 */
	public $Wishlist;


	/**
	 * Initialize the wishlist object
	 */
	public function __construct()
	{
		parent::__construct();

		if (TL_MODE == 'FE')
		{
			$this->import('Isotope');
			$this->import('IsotopeWishlist');
			$this->IsotopeWishlist->initializeWishlist((int) $this->Isotope->Config->id, (int) $this->Isotope->Config->store_id);
		}
	}


	/**
	 * Generate a wishlist button
	 * @param array
	 */
	public function generateButton($arrButtons)
	{
		$arrButtons['add_to_wishlist'] = array
		(
			'label' => $GLOBALS['TL_LANG']['MSC']['buttonLabel']['add_to_wishlist'],
			'callback' => array('Wishlist', 'addToWishlist')
		);

		return $arrButtons;
	}


	/**
	 * Adds a particular product to wishlist
	 * @param object
	 * @param mixed
	 */
	public function addToWishlist($objProduct, $objModule=null)
	{
		$intQuantity = ($objModule->iso_use_quantity && intval($this->Input->post('quantity_requested')) > 0) ? intval($this->Input->post('quantity_requested')) : 1;

		if ($this->IsotopeWishlist->addProduct($objProduct, $intQuantity) !== false)
		{
			$_SESSION['ISO_CONFIRM'][] = $GLOBALS['TL_LANG']['MSC']['addedToWishlist'];
			$this->jumpToOrReload($objModule->iso_addProductJumpTo);
		}
	}
}

