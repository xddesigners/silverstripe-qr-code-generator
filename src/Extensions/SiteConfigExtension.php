<?php

namespace XD\QRCodeGenerator\Extensions;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class SiteConfigExtension
 * @package XD\QRCodeGenerator\Extensions
 * @property SiteConfig|SiteConfigExtension $owner
 * @method Image QRCodeLogo
 */
class SiteConfigExtension extends DataExtension{

    private static $db = [
        'QRCodeShowLogo' => 'Boolean',
    ];

    private static $has_one = [
        'QRCodeLogo' => Image::class,
    ];

    private static $owns = [
        'QRCodeLogo'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        $fields->addFieldsToTab(
            'Root.QrCodeSettings',
            [
                CheckboxField::create('QRCodeShowLogo',_t(__CLASS__.'.QRCodeShowLogo','Show QR Code with logo')),
                UploadField::create('QRCodeLogo',_t(__CLASS__.'.QRCodeLogo','QRCode logo'))
            ]
        );



    }

}