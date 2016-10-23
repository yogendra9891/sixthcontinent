<?php

namespace Transaction\CommercialPromotionBundle\Document;



/**
 * Transaction\CommercialPromotionBundle\Document\AmilonCardCP
 */
class AmilonCardCP
{
    /**
     * @var $id
     */
    protected $id;

    /**
     * @var string $retailer_name
     */
    protected $retailer_name;

    /**
     * @var string $retailer_code
     */
    protected $retailer_code;

    /**
     * @var string $retailer_image
     */
    protected $retailer_image;

    /**
     * @var string $short_description
     */
    protected $short_description;

    /**
     * @var string $merchant_short_description
     */
    protected $merchant_short_description;

    /**
     * @var string $merchant_long_description
     */
    protected $merchant_long_description;

    /**
     * @var Transaction\CommercialPromotionBundle\Document\SingleCardCP
     */
    protected $single_card_cp = array();

    public function __construct()
    {
        $this->single_card_cp = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set id
     *
     * @param custom_id $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return custom_id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add singleCardCp
     *
     * @param Transaction\CommercialPromotionBundle\Document\SingleCardCP $singleCardCp
     */
    public function addSingleCardCp(\Transaction\CommercialPromotionBundle\Document\SingleCardCP $singleCardCp)
    {
        $this->single_card_cp[] = $singleCardCp;
    }

    /**
     * Remove singleCardCp
     *
     * @param Transaction\CommercialPromotionBundle\Document\SingleCardCP $singleCardCp
     */
    public function removeSingleCardCp(\Transaction\CommercialPromotionBundle\Document\SingleCardCP $singleCardCp)
    {
        $this->single_card_cp->removeElement($singleCardCp);
    }

    /**
     * Get singleCardCp
     *
     * @return Doctrine\Common\Collections\Collection $singleCardCp
     */
    public function getSingleCardCp()
    {
        return $this->single_card_cp;
    }

    /**
     * Set retailerName
     *
     * @param string $retailerName
     * @return self
     */
    public function setRetailerName($retailerName)
    {
        $this->retailer_name = $retailerName;
        return $this;
    }

    /**
     * Get retailerName
     *
     * @return string $retailerName
     */
    public function getRetailerName()
    {
        return $this->retailer_name;
    }

    /**
     * Set retailerCode
     *
     * @param string $retailerCode
     * @return self
     */
    public function setRetailerCode($retailerCode)
    {
        $this->retailer_code = $retailerCode;
        return $this;
    }

    /**
     * Get retailerCode
     *
     * @return string $retailerCode
     */
    public function getRetailerCode()
    {
        return $this->retailer_code;
    }

    /**
     * Set retailerImage
     *
     * @param string $retailerImage
     * @return self
     */
    public function setRetailerImage($retailerImage)
    {
        $this->retailer_image = $retailerImage;
        return $this;
    }

    /**
     * Get retailerImage
     *
     * @return string $retailerImage
     */
    public function getRetailerImage()
    {
        return $this->retailer_image;
    }

    /**
     * Set shortDescription
     *
     * @param string $shortDescription
     * @return self
     */
    public function setShortDescription($shortDescription)
    {
        $this->short_description = $shortDescription;
        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string $shortDescription
     */
    public function getShortDescription()
    {
        return $this->short_description;
    }

    /**
     * Set merchantShortDescription
     *
     * @param string $merchantShortDescription
     * @return self
     */
    public function setMerchantShortDescription($merchantShortDescription)
    {
        $this->merchant_short_description = $merchantShortDescription;
        return $this;
    }

    /**
     * Get merchantShortDescription
     *
     * @return string $merchantShortDescription
     */
    public function getMerchantShortDescription()
    {
        return $this->merchant_short_description;
    }

    /**
     * Set merchantLongDescription
     *
     * @param string $merchantLongDescription
     * @return self
     */
    public function setMerchantLongDescription($merchantLongDescription)
    {
        $this->merchant_long_description = $merchantLongDescription;
        return $this;
    }

    /**
     * Get merchantLongDescription
     *
     * @return string $merchantLongDescription
     */
    public function getMerchantLongDescription()
    {
        return $this->merchant_long_description;
    }
    /**
     * @var float $citizen_aff_charge
     */
    protected $citizen_aff_charge;

    /**
     * @var float $shop_aff_charge
     */
    protected $shop_aff_charge;

    /**
     * @var float $friends_follower_charge
     */
    protected $friends_follower_charge;

    /**
     * @var float $buyer_charge
     */
    protected $buyer_charge;

    /**
     * @var float $sixc_charge
     */
    protected $sixc_charge;

    /**
     * @var float $all_country_charge
     */
    protected $all_country_charge;


    /**
     * Set citizenAffCharge
     *
     * @param float $citizenAffCharge
     * @return self
     */
    public function setCitizenAffCharge($citizenAffCharge)
    {
        $this->citizen_aff_charge = $citizenAffCharge;
        return $this;
    }

    /**
     * Get citizenAffCharge
     *
     * @return float $citizenAffCharge
     */
    public function getCitizenAffCharge()
    {
        return $this->citizen_aff_charge;
    }

    /**
     * Set shopAffCharge
     *
     * @param float $shopAffCharge
     * @return self
     */
    public function setShopAffCharge($shopAffCharge)
    {
        $this->shop_aff_charge = $shopAffCharge;
        return $this;
    }

    /**
     * Get shopAffCharge
     *
     * @return float $shopAffCharge
     */
    public function getShopAffCharge()
    {
        return $this->shop_aff_charge;
    }

    /**
     * Set friendsFollowerCharge
     *
     * @param float $friendsFollowerCharge
     * @return self
     */
    public function setFriendsFollowerCharge($friendsFollowerCharge)
    {
        $this->friends_follower_charge = $friendsFollowerCharge;
        return $this;
    }

    /**
     * Get friendsFollowerCharge
     *
     * @return float $friendsFollowerCharge
     */
    public function getFriendsFollowerCharge()
    {
        return $this->friends_follower_charge;
    }

    /**
     * Set buyerCharge
     *
     * @param float $buyerCharge
     * @return self
     */
    public function setBuyerCharge($buyerCharge)
    {
        $this->buyer_charge = $buyerCharge;
        return $this;
    }

    /**
     * Get buyerCharge
     *
     * @return float $buyerCharge
     */
    public function getBuyerCharge()
    {
        return $this->buyer_charge;
    }

    /**
     * Set sixcCharge
     *
     * @param float $sixcCharge
     * @return self
     */
    public function setSixcCharge($sixcCharge)
    {
        $this->sixc_charge = $sixcCharge;
        return $this;
    }

    /**
     * Get sixcCharge
     *
     * @return float $sixcCharge
     */
    public function getSixcCharge()
    {
        return $this->sixc_charge;
    }

    /**
     * Set allCountryCharge
     *
     * @param float $allCountryCharge
     * @return self
     */
    public function setAllCountryCharge($allCountryCharge)
    {
        $this->all_country_charge = $allCountryCharge;
        return $this;
    }

    /**
     * Get allCountryCharge
     *
     * @return float $allCountryCharge
     */
    public function getAllCountryCharge()
    {
        return $this->all_country_charge;
    }
    /**
     * @var string $web_site
     */
    protected $web_site;


    /**
     * Set webSite
     *
     * @param string $webSite
     * @return self
     */
    public function setWebSite($webSite)
    {
        $this->web_site = $webSite;
        return $this;
    }

    /**
     * Get webSite
     *
     * @return string $webSite
     */
    public function getWebSite()
    {
        return $this->web_site;
    }
    /**
     * @var int $validity_month
     */
    protected $validity_month;

    /**
     * @var int $combinable_card
     */
    protected $combinable_card;

    /**
     * @var string $contact_card
     */
    protected $contact_card;

    /**
     * @var int $point_of_sale
     */
    protected $point_of_sale;


    /**
     * Set validityMonth
     *
     * @param int $validityMonth
     * @return self
     */
    public function setValidityMonth($validityMonth)
    {
        $this->validity_month = $validityMonth;
        return $this;
    }

    /**
     * Get validityMonth
     *
     * @return int $validityMonth
     */
    public function getValidityMonth()
    {
        return $this->validity_month;
    }

    /**
     * Set combinableCard
     *
     * @param int $combinableCard
     * @return self
     */
    public function setCombinableCard($combinableCard)
    {
        $this->combinable_card = $combinableCard;
        return $this;
    }

    /**
     * Get combinableCard
     *
     * @return int $combinableCard
     */
    public function getCombinableCard()
    {
        return $this->combinable_card;
    }

    /**
     * Set contactCard
     *
     * @param string $contactCard
     * @return self
     */
    public function setContactCard($contactCard)
    {
        $this->contact_card = $contactCard;
        return $this;
    }

    /**
     * Get contactCard
     *
     * @return string $contactCard
     */
    public function getContactCard()
    {
        return $this->contact_card;
    }

    /**
     * Set pointOfSale
     *
     * @param int $pointOfSale
     * @return self
     */
    public function setPointOfSale($pointOfSale)
    {
        $this->point_of_sale = $pointOfSale;
        return $this;
    }

    /**
     * Get pointOfSale
     *
     * @return int $pointOfSale
     */
    public function getPointOfSale()
    {
        return $this->point_of_sale;
    }
    /**
     * @var string $website_preview
     */
    protected $website_preview;


    /**
     * Set websitePreview
     *
     * @param string $websitePreview
     * @return self
     */
    public function setWebsitePreview($websitePreview)
    {
        $this->website_preview = $websitePreview;
        return $this;
    }

    /**
     * Get websitePreview
     *
     * @return string $websitePreview
     */
    public function getWebsitePreview()
    {
        return $this->website_preview;
    }
}
