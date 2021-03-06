<?php

namespace Transaction\CommercialPromotionBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Transaction\CommercialPromotionBundle\Interfaces\ICPCustomization;
use Transaction\CommercialPromotionBundle\Document\VoucherCP;
use Transaction\CommercialPromotionBundle\Document\ImagesCP;
use Transaction\CommercialPromotionBundle\Document\TagsCP;

/**
 * VoucherCP
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VoucherCPRepository extends DocumentRepository implements ICPCustomization {

    public static $IMG_TYPE = "COMMERCIAL_PROMOTION";

    public function saveCustomization($voucher_card, $param) {

        $voucher_cardCP = new VoucherCP;
        $voucher_cardCP->setId($voucher_card->getId());
        $voucher_cardCP->setDescription($param["description"]);
        $voucher_cardCP->setUrlConfirmation($param["url_confirmation"]);
        $voucher_cardCP->setHtmlPage($param["html_page"]);

        if (isset($param["tag_friends"]) && $param["tag_friends"] > 0) {
            foreach ($param["tag_friends"] as $value) {
                $tag = new TagsCP;
                $tag->setUserId($value["id"]);
                $tag->setName($value["name"]);
                $voucher_cardCP->addTagsCp($tag);
            }
        }

        if (isset($param["imageurl"])) {
            $images = explode(",", $param["imageurl"]);
            for ($index = 0; $index < count($images); $index ++) {
                $image_cp = new ImagesCP;
                $image_cp->setImageType(self::$IMG_TYPE);
                $image_cp->setReal($images[$index]);
                $image_cp->setThumb($images[$index]);
                $voucher_cardCP->addImagesCp($image_cp);
            }
        }


        $dm = $this->getDocumentManager();
        $dm->persist($voucher_cardCP);

        $dm->flush();
        return $voucher_cardCP;
    }

    /**
     * 
     * @param type $id_commercial_promotion
     * @return type
     * @throws type
     */
    public function getCustomizationOffer($id_commercial_promotion) {
        $result = array();
        $id_commercial_promotion = (int)$id_commercial_promotion;
        $voucher_card_document = $this->getDocumentManager()
                ->getRepository('CommercialPromotionBundle:VoucherCP')
                ->findOneById($id_commercial_promotion);
        if ($voucher_card_document != null ) {
            $result["description"] = $voucher_card_document->getDescription();
            $result["url_confirmation"] = $voucher_card_document->getUrlConfirmation();
            $result["html_page"] = $voucher_card_document->getHtmlPage();
            $result["id"] = $voucher_card_document->getId();
            $images_result = array();
            foreach ($voucher_card_document->getImagesCp() as $images_result_data) {
                $img_id = $images_result_data->getId();
                $img_type = $images_result_data->getImageType();
                $img_real = $images_result_data->getReal();
                $thumb = $images_result_data->getThumb();
                $result["images"][] = array(
                    'id' => $img_id,
                    'img_type' => $img_type,
                    'img_real' => $img_real,
                    'thumb' => $thumb
                );
            }
        }else{
            $result= array("me"=>"not working");
        }

        return $result;
    }

    // ... do something, like pass the $product object into a template
}
