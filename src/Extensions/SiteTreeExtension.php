<?php

namespace XD\QRCodeGenerator\Extensions;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;
use XD\QRCodeGenerator\Image\QRImageWithLogo;
use XD\QRCodeGenerator\Options\LogoOptions;

/**
 * Class SiteTreeExtension
 * @package XD\QRCodeGenerator
 * @property SiteTree|SiteTreeExtension $owner
 */
class SiteTreeExtension extends DataExtension{

    private static $has_one = [
        'QRCode' => 'Image'
    ];

    private static $owns = [
        'QRCode'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        $link = $this->generateQRCode();

        $fields->addFieldsToTab('Root.QRCode',[
            LiteralField::create('QRCode', '<img src="'. $link .'" alt="QR Code" width="500" height="500"><p style="padding-left:3rem;"><a href="' . $this->owner->AbsoluteLink() . '" target="_blank">' . $this->owner->AbsoluteLink() .'</a></p>')
        ]);
    }

    public function generateQRCode(){
        // See: https://www.twilio.com/blog/create-qr-code-in-php
        $config = SiteConfig::get()->first();
        /* @var Image $logo */
        $logo = $config->QRCodeLogo();
        if( $config->QRCodeShowLogo && $logo->exists() ){
            $options = new LogoOptions(
                [
                    'eccLevel' => QRCode::ECC_H,
                    'imageBase64' => true,
                    'imageTransparent' => false,
                    'logoSpaceHeight' => 17,
                    'logoSpaceWidth' => 17,
                    'scale' => 26,
                    'version' => 7,
                ]
            );

            $qrOutputInterface = new QRImageWithLogo(
                $options,
                (new QRCode($options))->getMatrix($this->owner->AbsoluteLink())
            );

            $logoFile = Director::baseFolder() . '/assets/' . $logo->getFilename();

            $qrcode = $qrOutputInterface->dump(
                null,
                $logoFile
            );

            return $qrcode;
        } else {
            // no logo QR code
            $options = new QROptions(
                [
                    'eccLevel' => QRCode::ECC_L,
                    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                    'version' => 5,
                ]
            );

            return (new QRCode($options))->render($this->owner->AbsoluteLink());
        }

    }

}