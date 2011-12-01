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
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


class Favorites extends Frontend
{

	/**
	 * Favorites object
	 * @var object
	 */
	public $Favorites;


	/**
	 * Initialize the favorites object
	 */
	public function __construct()
	{
		parent::__construct();

		if (TL_MODE == 'FE')
		{
			$this->import('Isotope');
			$this->import('IsotopeFavorites');
			$this->IsotopeFavorites->initializeFavorites((int) $this->Isotope->Config->id, (int) $this->Isotope->Config->store_id);
		}
	}


	/**
	 * Generate a favorites button
	 * @param array
	 */
	public function generateButton($arrButtons)
	{
		$arrButtons['add_to_favorites'] = array
		(
			'label' => $GLOBALS['TL_LANG']['MSC']['buttonLabel']['add_to_favorites'],
			'callback' => array('Favorites', 'addToFavorites')
		);

		return $arrButtons;
	}


	/**
	 * Adds a particular product to favorites
	 * @param object
	 * @param mixed
	 */
	public function addToFavorites($objProduct, $objModule=null)
	{
		$intQuantity = ($objModule->iso_use_quantity && intval($this->Input->post('quantity_requested')) > 0) ? intval($this->Input->post('quantity_requested')) : 1;

		if ($this->IsotopeFavorites->addProduct($objProduct, $intQuantity) !== false)
		{
			$_SESSION['ISO_CONFIRM'][] = $GLOBALS['TL_LANG']['MSC']['addedToFavorites'];
			$this->jumpToOrReload($objModule->iso_addProductJumpTo);
		}
	}
}

