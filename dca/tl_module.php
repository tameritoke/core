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
 * Add a palette to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['iso_wishlist']	     = '{title_legend},name,headline,type;{redirect_legend},iso_cart_jumpTo,iso_wishlist_jumpTo,iso_continueShopping;{template_legend},iso_includeMessages,iso_cart_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['iso_wishlistemail'] = '{title_legend},name,headline,type;{config_legend},iso_mail_customer,iso_wishlist_form,iso_wishlist_clearList,iso_wishlist_definedRecipients,iso_wishlist_recipientFromFormField;{redirect_legend},jumpTo;{template_legend},iso_includeMessages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['iso_productreader']		= '{title_legend},name,headline,type;{config_legend},iso_use_quantity;{redirect_legend},iso_addProductJumpTo,iso_wishlist_jumpTo;{template_legend:hide},iso_includeMessages,iso_reader_layout,iso_buttons;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
/**
 * Selectors
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'iso_wishlist_recipientFromFormField';

/**
 * Subpalettes
 */
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['iso_wishlist_recipientFromFormField'] = 'iso_wishlist_formField';


/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_wishlist_jumpTo'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_jumpTo'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'explanation'             => 'jumpTo',
	'eval'                    => array('fieldType'=>'radio')
);
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_wishlist_form'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_form'],
	'exclude'                 => true,
	'inputType'               => 'radio',
	'options_callback'		  => array('tl_module_iso_wishlist', 'getForms'),
	'eval'                    => array('submitOnChange'=>true, 'mandatory'=>true)
);
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_wishlist_clearList'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_clearList'],
	'exclude'                 => true,
	'inputType'               => 'checkbox'
);
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_wishlist_definedRecipients'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_definedRecipients'],
	'exclude'                 => true,
	'inputType'               => 'text'
);
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_wishlist_recipientFromFormField'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_recipientFromFormField'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true)
);
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_wishlist_formField'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_wishlist_formField'],
	'exclude'                 => true,
	'inputType'               => 'radio',
	'options_callback'		  => array('tl_module_iso_wishlist', 'getEmailFormFields'),
	'eval'                    => array('mandatory'=>true)
);

$GLOBALS['TL_DCA']['tl_module']['fields']['iso_continueShopping'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['iso_continueShopping'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'					  => array('tl_class'=>'w50 m12'),
);

class tl_module_iso_wishlist extends Backend
{
	/**
	 * Initialize the object
	 */
	public function __construct()
	{
		parent::__construct();
	}
	

	/**
	 * Get the forms
	 * @param DataContainer
	 * @return array
	 */
	public function getForms(DataContainer $dc)
	{
		$objForms = $this->Database->execute('SELECT id,title FROM tl_form');
		if(!$objForms->numRows)
		{
			return array();
		}
		
		$arrForms = array();
		while($objForms->next())
		{
			$arrForms[$objForms->id] = $objForms->title;
		}
		
		return $arrForms;		
	}
	
	
	/**
	 * Get the email form fields of a form
	 * @param DataContainer
	 * @return array
	 */
	public function getEmailFormFields(DataContainer $dc)
	{
		if(!$dc->activeRecord)
		{
			return array();
		}
		
		$objFormFields = $this->Database->prepare('SELECT id,name,label FROM tl_form_field WHERE pid=? AND rgxp=?')->execute($dc->activeRecord->iso_wishlist_form, 'email');
		if(!$objFormFields->numRows)
		{
			return array();
		}
		
		$arrFormFields = array();
		while($objFormFields->next())
		{
			$arrFormFields[$objFormFields->id] = $objFormFields->label . ' <span style="color:#ccc;">[' . $objFormFields->name . ']</span>';
		}
		
		return $arrFormFields;		
	}
}