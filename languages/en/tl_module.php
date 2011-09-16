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


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_jumpTo'] 				= array('Shopping Wishlist Jump to page', 'This setting defines to which page a user will be redirected when requesting a form to send his wishlist by e-mail.');
$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_form'] 					= array('Form', 'Choose the form you would like to display.');
$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_clearList'] 				= array('Clear the wishlist after the e-mail has been sent', 'Activate this checkbox if you would like to delete the wishlist after the e-mail has been sent.');
$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_definedRecipients'] 		= array('List of recipients', 'Enter a comma-separated list of e-mail addresses. Leave the field empty if you don\'t want to add recipients.');
$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_recipientFromFormField'] = array('Send the e-mail to an address entered in the form', 'Activate this checkbox to choose the form field containing the e-mail address.');
$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_formField'] 				= array('e-mail form fields', 'You can only choose form fields validating for a correct e-mail address.');