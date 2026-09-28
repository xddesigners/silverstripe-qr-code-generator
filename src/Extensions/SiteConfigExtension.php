<?php

namespace XD\QRCodeGenerator\Extensions;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class SiteConfigExtension
 * @package XD\QRCodeGenerator\Extensions
 * @property SiteConfig|SiteConfigExtension $owner
 * @method Image QRCodeLogo
 */
class SiteConfigExtension extends Extension{

    private static $db = [
        'QRCodeShowLogo' => 'Boolean',
    ];

    private static $has_one = [
        'QRCodeLogo' => Image::class,
    ];

    private static $owns = [
        'QRCodeLogo'
    ];

    protected function updateCMSFields(FieldList $fields)
    {
        $logoField = UploadField::create('QRCodeLogo', _t(__CLASS__ . '.QRCodeLogo', 'QRCode logo'));
        // The logo is composited with imagecreatefrompng(), so only PNG is supported.
        $logoField->getValidator()->setAllowedExtensions(['png']);
        $logoField->setDescription(_t(__CLASS__ . '.QRCodeLogoDesc', 'PNG only. A square logo works best.'));

        $fields->addFieldsToTab(
            'Root.QrCodeSettings',
            [
                CheckboxField::create('QRCodeShowLogo',_t(__CLASS__.'.QRCodeShowLogo','Show QR Code with logo')),
                $logoField
            ]
        );
    }

}