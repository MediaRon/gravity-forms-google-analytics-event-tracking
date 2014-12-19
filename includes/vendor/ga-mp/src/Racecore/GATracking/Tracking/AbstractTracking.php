<?php
namespace Racecore\GATracking\Tracking;

/**
 * Google Analytics Measurement PHP Class
 * Licensed under the 3-clause BSD License.
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * Google Documentation
 * https://developers.google.com/analytics/devguides/collection/protocol/v1/
 *
 * @author  Marco Rieger
 * @email   Rieger(at)racecore.de
 * @git     https://github.com/ins0
 * @url     http://www.racecore.de
 * @package Racecore\GATracking\Tracking
 */
abstract class AbstractTracking
{
    // document referrer
    /** @var String */
    private $documentReferrer;

    // campaign
    /** @var String */
    private $campaignName, $campaignSource, $campaignMedium, $campaignContent, $campaignID, $campaignKeyword;

    // adwords id
    /** @var String */
    private $adwordsID;

    // display ads id
    /** @var String */
    private $displayAdsID;

    // screen resolution
    /** @var String */
    private $screenResolution;

    // viewport size
    /** @var String */
    private $viewportSize;

    // document encoding
    /** @var String */
    private $documentEncoding;

    // screen colors
    /** @var String */
    private $screenColors;

    // user language
    /** @var String */
    private $userLanguage;

    // java enabled
    /** @var boolean|string */
    private $javaEnabled = null;

    // flash version
    /** @var String */
    private $flashVersion;

    // document location
    /** @var String */
    private $documentLocation;

    // document host
    /** @var String */
    private $documentHost;

    // document path
    /** @var String */
    private $documentPath;

    // document title
    /** @var String */
    private $documentTitle;

    // app name
    /** @var String */
    private $appName;

    // app version
    /** @var String */
    private $appVersion;

    // experiment id
    /** @var String */
    private $experimentID;

    // experiment variant
    /** @var String */
    private $experimentVariant;

    // content description
    /** @var String */
    private $contentDescription;

    // link id
    /** @var String */
    private $linkID;

    // custom dimensions
    /** @var Array */
    private $customDimension = array();

    // custom metric
    /** @var Array */
    private $customMetric = array();

    // productId
    /** @var string */
    private $productId;

    /**
     * Get the transfer Paket from current Event
     *
     * @return array
     */
    abstract public function createPackage();

    /**
     * Returns the Paket for Event Tracking
     *
     * @return array
     * @throws \Racecore\GATracking\Exception\MissingTrackingParameterException
     * @deprecated
     */
    public function getPaket()
    {
        return $this->createPackage();
    }

    /**
     * @return array
     */
    public function getPackage()
    {
        $package = array_merge($this->createPackage(), array(
            // campaign
            'cn' => $this->getCampaignName(),
            'cs' => $this->getCampaignSource(),
            'cm' => $this->getCampaignMedium(),
            'ck' => $this->getCampaignKeyword(),
            'cc' => $this->getCampaignContent(),
            'ci' => $this->getCampaignID(),

            // other
            'dr' => $this->getDocumentReferrer(),
            'gclid' => $this->getAdwordsID(),
            'dclid' => $this->getDisplayAdsID(),

            // system info
            'sr' => $this->getScreenResolution(),
            'sd' => $this->getScreenColors(),
            'vp' => $this->getViewportSize(),
            'de' => $this->getDocumentEncoding(),
            'ul' => $this->getUserLanguage(),
            'je' => $this->getJavaEnabled(),
            'fl' => $this->getFlashVersion(),

            // Content Information
            'dl' => $this->getDocumentLocation(),
            'dh' => $this->getDocumentHost(),
            'dp' => $this->getDocumentPath(),
            'dt' => $this->getDocumentTitle(),
            'cd' => $this->getContentDescription(),
            'linkid' => $this->getLinkID(),

            // app tracking
            'an' => $this->getAppName(),
            'av' => $this->getAppVersion(),

            // enhanced e-commerce



            // content experiments
            'xid' => $this->getExperimentID(),
            'xvar' => $this->getExperimentVariant(),
        ));

        $package = $this->addCustomParameters($package);

        // remove all unused
        $package = array_filter($package, 'strlen');

        return $package;
    }

    /**
     * @param array $package
     * @return array
     */
    private function addCustomParameters( Array $package )
    {
        // add custom metric params
        foreach( $this->customMetric as $id => $value )
        {
            $package['cm' . (int) $id ] = $value;
        }

        // add custom dimension params
        foreach( $this->customDimension as $id => $value )
        {
            $package['cd' . (int) $id ] = $value;
        }

        return $package;
    }

    /**
     * @param null $id
     * @param $value
     */
    public function setCustomDimension($id = null, $value)
    {
        $this->customDimension[$id] = $value;
    }

    /**
     * @param null $id
     * @param $value
     */
    public function setCustomMetric($id = null, $value)
    {
        $this->customMetric[$id] = $value;
    }

    /**
     * @param String $contentDescription
     */
    public function setContentDescription($contentDescription)
    {
        $this->contentDescription = $contentDescription;
    }

    /**
     * @return String
     */
    public function getContentDescription()
    {
        return $this->contentDescription;
    }

    /**
     * @param String $linkID
     */
    public function setLinkID($linkID)
    {
        $this->linkID = $linkID;
    }

    /**
     * @return String
     */
    public function getLinkID()
    {
        return $this->linkID;
    }

    /**
     * @param String $adwordsID
     */
    public function setAdwordsID($adwordsID)
    {
        $this->adwordsID = $adwordsID;
    }

    /**
     * @return String
     */
    public function getAdwordsID()
    {
        return $this->adwordsID;
    }

    /**
     * @param String $appName
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
    }

    /**
     * @return String
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * @param String $appVersion
     */
    public function setAppVersion($appVersion)
    {
        $this->appVersion = $appVersion;
    }

    /**
     * @return String
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param String $campaignContent
     */
    public function setCampaignContent($campaignContent)
    {
        $this->campaignContent = $campaignContent;
    }

    /**
     * @return String
     */
    public function getCampaignContent()
    {
        return $this->campaignContent;
    }

    /**
     * @param String $campaignID
     */
    public function setCampaignID($campaignID)
    {
        $this->campaignID = $campaignID;
    }

    /**
     * @return String
     */
    public function getCampaignID()
    {
        return $this->campaignID;
    }

    /**
     * @param String $campaignKeyword
     */
    public function setCampaignKeyword($campaignKeyword)
    {
        $this->campaignKeyword = $campaignKeyword;
    }

    /**
     * @deprecated Use setCampaignKeyword
     * @param $campaignKeyword
     */
    public function setCampaignKeywords($campaignKeyword)
    {
        if( is_array($campaignKeyword) )
        {
            return $this->setCampaignKeyword(implode(',', $campaignKeyword));
        }

        $this->setCampaignKeyword($campaignKeyword);
    }

    /**
     * @return Array
     */
    public function getCampaignKeyword()
    {
        return $this->campaignKeyword;
    }

    /**
     * @param String $campaignMedium
     */
    public function setCampaignMedium($campaignMedium)
    {
        $this->campaignMedium = $campaignMedium;
    }

    /**
     * @return String
     */
    public function getCampaignMedium()
    {
        return $this->campaignMedium;
    }

    /**
     * @param String $campaignName
     */
    public function setCampaignName($campaignName)
    {
        $this->campaignName = $campaignName;
    }

    /**
     * @return String
     */
    public function getCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * @param String $campaignSource
     */
    public function setCampaignSource($campaignSource)
    {
        $this->campaignSource = $campaignSource;
    }

    /**
     * @return String
     */
    public function getCampaignSource()
    {
        return $this->campaignSource;
    }

    /**
     * @param String $displayAdsID
     */
    public function setDisplayAdsID($displayAdsID)
    {
        $this->displayAdsID = $displayAdsID;
    }

    /**
     * @return String
     */
    public function getDisplayAdsID()
    {
        return $this->displayAdsID;
    }

    /**
     * @param String $documentEncoding
     */
    public function setDocumentEncoding($documentEncoding)
    {
        $this->documentEncoding = $documentEncoding;
    }

    /**
     * @return String
     */
    public function getDocumentEncoding()
    {
        return $this->documentEncoding;
    }

    /**
     * @param String $documentHost
     */
    public function setDocumentHost($documentHost)
    {
        $this->documentHost = $documentHost;
    }

    /**
     * @return String
     */
    public function getDocumentHost()
    {
        return $this->documentHost;
    }

    /**
     * @param String $documentLocation
     */
    public function setDocumentLocation($documentLocation)
    {
        $this->documentLocation = $documentLocation;
    }

    /**
     * @return String
     */
    public function getDocumentLocation()
    {
        return $this->documentLocation;
    }

    /**
     * @param String $documentPath
     */
    public function setDocumentPath($documentPath)
    {
        $this->documentPath = $documentPath;
    }

    /**
     * @return String
     */
    public function getDocumentPath()
    {
        return $this->documentPath;
    }

    /**
     * @param String $documentReferrer
     */
    public function setDocumentReferrer($documentReferrer)
    {
        $this->documentReferrer = $documentReferrer;
    }

    /**
     * @return String
     */
    public function getDocumentReferrer()
    {
        return $this->documentReferrer;
    }

    /**
     * @param String $documentTitle
     */
    public function setDocumentTitle($documentTitle)
    {
        $this->documentTitle = $documentTitle;
    }

    /**
     * @return String
     */
    public function getDocumentTitle()
    {
        return $this->documentTitle;
    }

    /**
     * @param String $experimentID
     */
    public function setExperimentID($experimentID)
    {
        $this->experimentID = $experimentID;
    }

    /**
     * @return String
     */
    public function getExperimentID()
    {
        return $this->experimentID;
    }

    /**
     * @param String $experimentVariant
     */
    public function setExperimentVariant($experimentVariant)
    {
        $this->experimentVariant = $experimentVariant;
    }

    /**
     * @return String
     */
    public function getExperimentVariant()
    {
        return $this->experimentVariant;
    }

    /**
     * @param String $flashVersion
     */
    public function setFlashVersion($flashVersion)
    {
        $this->flashVersion = $flashVersion;
    }

    /**
     * @return String
     */
    public function getFlashVersion()
    {
        return $this->flashVersion;
    }

    /**
     * @param boolean $javaEnabled
     */
    public function setJavaEnabled($javaEnabled)
    {
        $this->javaEnabled = (bool) $javaEnabled;
    }

    /**
     * @return boolean
     */
    public function getJavaEnabled()
    {
        if( $this->javaEnabled === null ){
            return null;
        }

        return $this->javaEnabled ? '1' : '0';
    }

    /**
     * @param String $screenColors
     */
    public function setScreenColors($screenColors)
    {
        $this->screenColors = $screenColors;
    }

    /**
     * @return String
     */
    public function getScreenColors()
    {
        return $this->screenColors;
    }

    /**
     * @param $width
     * @param $height
     */
    public function setScreenResolution($width, $height)
    {
        $this->screenResolution = $width . 'x' . $height;
    }

    /**
     * @return String
     */
    public function getScreenResolution()
    {
        return $this->screenResolution;
    }

    /**
     * @param String $userLanguage
     */
    public function setUserLanguage($userLanguage)
    {
        $this->userLanguage = $userLanguage;
    }

    /**
     * @return String
     */
    public function getUserLanguage()
    {
        return $this->userLanguage;
    }

    /**
     * @param $width
     * @param $height
     */
    public function setViewportSize($width, $height)
    {
        $this->viewportSize = $width . 'x' . $height;
    }

    /**
     * @return String
     */
    public function getViewportSize()
    {
        return $this->viewportSize;
    }

    /**
     * @param string $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

}