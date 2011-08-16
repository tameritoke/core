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


class HasteForm extends Frontend
{

	/**
	 * Form ID
	 * @var string
	 */
	protected $strFormId;
	
	/**
	 * Validation status
	 * @var boolean
	 */
	protected $blnValid;
	
	/**
	 * Fields
	 * @var array
	 */
	protected $arrFields = array();
	
	/**
	 * Widgets
	 * @var array
	 */
	protected $arrWidgets = array();
	
	/**
	 * Configuratoin
	 * @var array
	 */
	protected $arrConfiguration = array();
	

	/**
	 * Initialize the object
	 * @param integer
	 * @param array
	 */
	public function __construct($intId, $arrFields)
	{
		parent::__construct();
		
		$this->strFormId = 'form_' . $intId;
		$this->arrFields = $arrFields;
		
		$this->arrConfiguration['action'] = ampersand($this->Environment->request);
		$this->arrConfiguration['submit'] = $GLOBALS['TL_LANG']['MSC']['submit'];
	}
	
	
	/**
	 * Set an object property
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrConfiguration[$strKey] = $varValue;
	}
	
	
	/**
	 * Return an object property
	 * @param string
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'fields':
				return $this->arrFields;
				break;
			
			case 'widgets':
				return $this->arrWidgets;
				break;
				
			default:
				return $this->arrConfiguration[$strKey];
				break;
		}
	}
	
	
	/**
	 * Validate the form
	 * @return boolean
	 */
	public function validate()
	{
		if ($this->Input->post('FORM_SUBMIT') == $this->strFormId)
		{
			$this->blnValid = true;
		}
	
		// Initialize widgets
		foreach ($this->arrFields as $arrField)
		{
			$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']];
			
			// Continue if the class is not defined
			if (!$this->classFileExists($strClass))
			{
				continue;
			}
			
			$arrField['eval']['required'] = $arrField['eval']['mandatory'];
			$objWidget = new $strClass($this->prepareForWidget($arrField, $arrField['name'], $arrField['value']));
			
			// Validate the widget
			if ($this->Input->post('FORM_SUBMIT') == $this->strFormId)
			{
				$objWidget->validate();
				
				if ($objWidget->hasErrors())
				{
					$this->blnValid = false;
				}
			}
			
			$this->arrWidgets[$arrField['name']] = $objWidget;
		}
		
		return $this->blnValid;
	}
	
	
	/**
	 * Get a prticular widget data
	 * @param string
	 * @return mixed
	 */
	public function fetchSingle($strWidget)
	{
		if (array_key_exists($strWidget, $this->arrWidgets))
		{
			return $this->arrWidgets[$strWidget]->value;
		}
		
		return null;
	}
	
	
	/**
	 * Get data from all widgets	 
	 */
	public function fetchAll()
	{
		$arrData = array();
	
		foreach (array_keys($this->arrWidgets) as $widget)
		{
			$arrData[$widget] = $this->fetchSingle($widget);
		}
		
		return $arrData;
	}

	
	/**
	 * Add captcha field
	 */
	public function addCaptcha()
	{
		if (!isset($this->arrFields['captcha']))
		{
			$this->arrFields['captcha'] = array
			(
				'name'      => 'captcha',
				'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval'      => array('mandatory'=>true)
			);
		}
	}
	

	/**
	 * Add form to a template
	 * @param object
	 */
	public function addFormToTemplate($objTemplate)
	{		
		$objTemplate->fields = $this->arrWidgets;
		$objTemplate->submit = $this->arrConfiguration['submit'];
		$objTemplate->action = $this->arrConfiguration['action'];
		$objTemplate->formId = $this->strFormId;
		$objTemplate->hasError = $this->blnValid;
	}
}

