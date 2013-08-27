<?php

/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009-2012 Isotope eCommerce Workgroup
 *
 * @package    Isotope
 * @link       http://www.isotopeecommerce.com
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Isotope;

/**
 * Class Isotope\Environment
 *
 * Provide information about the current working environment
 * @copyright  Isotope eCommerce Workgroup 2009-2013
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 */
class Environment extends \System
{

    /**
     * True if frontend preview is active
     * @var bool
     */
    protected $blnIsFrontendPreview = false;

    /**
     * True if user can see unpublished elements
     * @var bool
     */
    protected $blnCanSeeUnpublished = false;

    /**
     * Logged in frontend member
     * @var \MemberModel
     */
    protected $objMember;

    /**
     * Logged in backend user
     * @var \UserModel
     */
    protected $objUser;


    /**
     * Construct object and set defaults from Contao environment
     */
    public function __construct()
    {
        parent::__construct();

        // Check if frontend user is logged in
        if (FE_USER_LOGGED_IN === true) {
            $objUser = FrontendUser::getInstance();
            $this->objMember = \MemberModel::findByPk($objUser->id);
        }

        // Check if a backend user is logged in
        $objUser = BackendUser::getInstance();
        if ($objUser->id > 0) {
            $this->objUser = \UserModel::findByPk($objUser->id);
        }

        $this->blnCanSeeUnpublished = (BE_USER_LOGGED_IN === true);
        $this->blnIsFrontendPreview = (FE_PREVIEW === true);
    }

    /**
     * Return true if a backend user is logged in
     * @return  bool
     */
    public function isBackendLoggedIn()
    {
        return (null !== $this->objUser);
    }

    /**
     * Return true if a frontend member is logged in
     * @return  bool
     */
    public function isFrontendLoggedIn()
    {
        return (null !== $this->objMember);
    }

    /**
     * Return true if frontend preview is active
     * @return  bool
     */
    public function isFrontendPreview()
    {
        return $this->blnIsFrontendPreview;
    }

    /**
     * Return true if unpublished should be shown in frontend preview
     * @return  bool
     */
    public function canSeeUnpublished()
    {
        return $this->blnCanSeeUnpublished;
    }

    /**
     * Return true if we're in the install script
     * @return  bool
     */
    public function isInstallScript()
    {
        return (strpos(\Environment::get('script'), 'install.php') !== false);
    }

    /**
     * Return true if we're in postsale script
     * @return  bool
     */
    public function isPostsaleScript()
    {
        return (strpos(\Environment::get('script'), 'postsale.php') !== false);
    }

    /**
     * Return true if we're in cron script
     * @return  bool
     */
    public function isCronScript()
    {
        return (strpos(\Environment::get('script'), 'cron.php') !== false);
    }

    /**
     * Get logged in frontend member
     * @return  \MemberModel
     */
    public function getLoggedInMember()
    {
        return $this->objMember;
    }

    /**
     * Get logged in backend user
     * @return  \UserModel
     */
    public function getLoggedInUser()
    {
        return $this->objUser;
    }

    /**
     * Set if frontend preview is active
     * @param   bool
     * @return  Environment
     */
    public function setIsFrontendPreview($blnStatus)
    {
        $this->blnIsFrontendView = (bool) $blnStatus;

        return $this;
    }

    /**
     * Set if visitor can see unpublished elements
     * @param   bool
     * @return  Environment
     */
    public function setCanSeeUnpublished($blnStatus)
    {
        $this->blnCanSeeUnpublished = (bool) $blnStatus;

        return $this;
    }

    /**
     * Set logged in frontend member
     * @param   \MemberModel
     * @return  Environment
     */
    public function setLoggedInMember(\MemberModel $objMember)
    {
        $this->objMember = $objMember;

        return $this;
    }

    /**
     * Set logged in backend user
     * @param   \UserModel
     * @return  Environment
     */
    public function setLoggedInUser(\UserModel $objUser)
    {
        $this->objUser = $objUser;

        return $this;
    }
}
