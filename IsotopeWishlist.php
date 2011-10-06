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
 * @author     Yanick Witschi <yanick.witschi@certo-net.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


class IsotopeWishlist extends IsotopeProductCollection
{

	/**
	 * Cookie hash value
	 * @var string
	 */
	protected $strHash = '';

	/**
	 * Name of the current table
	 * @var string
	 */
	protected $strTable = 'tl_iso_wishlist';

	/**
	 * Name of the child table
	 * @var string
	 */
	protected $ctable = 'tl_iso_wishlist_items';

	/**
	 * Name of the temporary wishlist cookie
	 * @var string
	 */
	protected $strCookie = 'ISOTOPE_TEMP_WISHLIST';


	/**
	 * Import a front end user object
	 */
	public function __construct()
	{
		parent::__construct();

		if (FE_USER_LOGGED_IN)
		{
			$this->import('FrontendUser', 'User');
		}
	}


	/**
	 * Load current wishlist
	 * @param integer
	 * @param integer
	 */
	public function initializeWishlist($intConfig, $intStore)
	{
		$time = time();
		$this->strHash = $this->Input->cookie($this->strCookie);

		// Check to see if the user is logged in
		if (!FE_USER_LOGGED_IN || !$this->User->id)
		{
			if (!strlen($this->strHash))
			{
				$this->strHash = sha1(session_id() . (!$GLOBALS['TL_CONFIG']['disableIpCheck'] ? $this->Environment->ip : '') . $intConfig . $this->strCookie);
				$this->setCookie($this->strCookie, $this->strHash, $time+$GLOBALS['TL_CONFIG']['iso_cartTimeout'], $GLOBALS['TL_CONFIG']['websitePath']);
			}

			$objWishlist = $this->Database->execute("SELECT * FROM tl_iso_wishlist WHERE session='{$this->strHash}' AND store_id=$intStore");
		}
		else
		{
			$objWishlist = $this->Database->execute("SELECT * FROM tl_iso_wishlist WHERE pid={$this->User->id} AND store_id=$intStore");
		}

		// Create new wishlist
		if ($objWishlist->numRows)
		{
			$this->setFromRow($objWishlist, $this->strTable, 'id');
			$this->tstamp = $time;
		}
		else
		{
			$this->setData(array
			(
				'pid'			=> ($this->User->id ? $this->User->id : 0),
				'session'		=> ($this->User->id ? '' : $this->strHash),
				'tstamp'		=> time(),
				'store_id'		=> $intStore,
			));
		}

		// Temporary wishlist available, move to this wishlist. Must be after creating a new wishlist!
 		if (FE_USER_LOGGED_IN && strlen($this->strHash))
 		{
			$objWishlist = new IsotopeWishlist();

			if ($objWishlist->findBy('session', $this->strHash))
			{
				$this->transferFromCollection($objWishlist, false);
				$objWishlist->delete();
			}

			// Delete cookie
			$this->setCookie($this->strCookie, '', ($time - 3600), $GLOBALS['TL_CONFIG']['websitePath']);
 		}
	}


	public function addProduct(IsotopeProduct $objProduct, $intQuantity)
	{
		if (version_compare(ISO_VERSION, '1.3', '<'))
		{
			// Make sure collection is in DB before adding product
			if (!$this->blnRecordExists)
			{
				$this->findBy('id', $this->save());
			}
		}

		return parent::addProduct($objProduct, $intQuantity);
	}


	public function getSurcharges()
	{
		if (isset($this->arrCache['surcharges']))
			return $this->arrCache['surcharges'];

		$this->import('Isotope');

		$arrPreTax = $arrPostTax = $arrTaxes = array();

		$arrSurcharges = array();
		if (isset($GLOBALS['ISO_HOOKS']['checkoutSurcharge']) && is_array($GLOBALS['ISO_HOOKS']['checkoutSurcharge']))
		{
			foreach ($GLOBALS['ISO_HOOKS']['checkoutSurcharge'] as $callback)
			{
				$this->import($callback[0]);
				$arrSurcharges = $this->{$callback[0]}->{$callback[1]}($arrSurcharges);
			}
		}

		foreach( $arrSurcharges as $arrSurcharge )
		{
			if ($arrSurcharge['before_tax'])
			{
				$arrPreTax[] = $arrSurcharge;
			}
			else
			{
				$arrPostTax[] = $arrSurcharge;
			}
		}

		$arrProducts = $this->getProducts();
		foreach( $arrProducts as $pid => $objProduct )
		{
			$fltPrice = $objProduct->tax_free_total_price;
			foreach( $arrPreTax as $tax )
			{
				if (isset($tax['products'][$objProduct->cart_id]))
				{
					$fltPrice += $tax['products'][$objProduct->cart_id];
				}
			}

			$arrTaxIds = array();
			$arrTax = $this->Isotope->calculateTax($objProduct->tax_class, $fltPrice);

			if (is_array($arrTax))
			{
				foreach ($arrTax as $k => $tax)
				{
					if (array_key_exists($k, $arrTaxes))
					{
						$arrTaxes[$k]['total_price'] += $tax['total_price'];

						if (is_numeric($arrTaxes[$k]['price']) && is_numeric($tax['price']))
						{
							$arrTaxes[$k]['price'] += $tax['price'];
						}
					}
					else
					{
						$arrTaxes[$k] = $tax;
					}

					$taxId = array_search($k, array_keys($arrTaxes)) + 1;
					$arrTaxes[$k]['tax_id'] = $taxId;
					$arrTaxIds[] = $taxId;
				}
			}


			$strTaxId = implode(',', $arrTaxIds);
			if ($objProduct->tax_id != $strTaxId)
			{
				$this->updateProduct($objProduct, array('tax_id'=>$strTaxId));
			}
		}


		foreach( $arrPreTax as $i => $arrSurcharge )
		{
			if (!$arrSurcharge['tax_class'])
				continue;

			$arrTaxIds = array();
			$arrTax = $this->Isotope->calculateTax($arrSurcharge['tax_class'], $arrSurcharge['total_price'], $arrSurcharge['before_tax']);

			if (is_array($arrTax))
			{
				foreach ($arrTax as $k => $tax)
				{
					if (array_key_exists($k, $arrTaxes))
					{
						$arrTaxes[$k]['total_price'] += $tax['total_price'];

						if (is_numeric($arrTaxes[$k]['price']) && is_numeric($tax['price']))
						{
							$arrTaxes[$k]['price'] += $tax['price'];
						}
					}
					else
					{
						$arrTaxes[$k] = $tax;
					}

					$taxId = array_search($k, array_keys($arrTaxes)) + 1;
					$arrTaxes[$k]['tax_id'] = $taxId;
					$arrTaxIds[] = $taxId;
				}
			}

			$arrPreTax[$i]['tax_id'] = implode(',', $arrTaxIds);
		}

		$this->arrCache['surcharges'] = array_merge($arrPreTax, $arrTaxes, $arrPostTax);
		return $this->arrCache['surcharges'];
	}
}

