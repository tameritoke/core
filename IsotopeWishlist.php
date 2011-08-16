<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
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
 * @copyright  Kamil Kuzminski 2011 
 * @author     Kamil Kuzminski <http://qzminski.com> 
 * @package    IsotopeWishlist 
 * @license    GNU/LGPL 
 * @filesource
 */


/**
 * Class IsotopeWishlist 
 *
 * @copyright  Kamil Kuzminski 2011 
 * @author     Kamil Kuzminski <http://qzminski.com> 
 * @package    Controller
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
	
	public function getSurcharges()
	{
		return array();
	}
}

?>