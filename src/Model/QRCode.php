<?php

namespace XD\QRCodeGenerator\Models;

use chillerlan\QRCode\QROptions;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form_FieldMap;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Parsers\URLSegmentFilter;
use XD\QRCodeGenerator\Image\QRImageWithLogo;
use XD\QRCodeGenerator\Options\LogoOptions;

/**
 * Class QRCode
 * @package XD\QRCodeGenerator\Models
 * @method SiteTree InternalLink()
 * @property String $Title
 * @property String $ExternalLink
 */
class QRCode extends DataObject
{

    private static $table_name = 'QRCode';

    private static $db = [
        'Title' => 'Varchar',
        'ExternalLink' => 'Varchar'
    ];

    private static $has_one = [
        'InternalLink' => SiteTree::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['InternalLinkID', 'ExternalLink']);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                TreeDropdownField::create('InternalLinkID', _t(__CLASS__ . '.InternalLink', 'Internal link'), SiteTree::class),
                TextField::create('ExternalLink', _t(__CLASS__ . '.ExternalLink', 'External link'))
            ]
        );

        if ($this->getLink()) {
            $qrCode = $this->generateQRCode();
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    LiteralField::create('QRCode', '<a href="' . $this->getQRLink() . '" target="_blank"><img src="' . $qrCode . '" alt="QR Code" width="500" height="500"><p style="padding-left:3rem;"></a>')
                ]
            );
        }

        return $fields;
    }

    public function getQRLink()
    {
        return Controller::join_links(Director::absoluteBaseURL(),'qr/' . $this->ID);
    }

    public function getLink()
    {
        return $this->InternalLinkID ? $this->InternalLink()->AbsoluteLink() : $this->ExternalLink;
    }

    public function getFileName()
    {
        if ($link = $this->getLink()) {
            $link = str_replace([':', '.', '/'], '-', $link);
            $filter = URLSegmentFilter::create();
            return $filter->filter($link) . $this->getFileExtension();
        }
    }

    public function getMimeType()
    {
        $extension = $this->getFileExtension();
        return $extension == '.png' ? 'image/png' : 'image/svg+xml';
    }

    public function getFileExtension()
    {
        return $this->getLogo() ? '.png' : '.svg';
    }

    public function getLogo()
    {
        $config = SiteConfig::get()->first();
        /* @var Image $logo */
        $logo = $config->QRCodeLogo();
        if ($config->QRCodeShowLogo && $logo->exists()) {
            return $logo;
        }
        return false;
    }

    public function downloadFile()
    {
        $filename = $this->getFileName();
        $mime = 'application/octet-stream'; // force download
        $tmp = '/tmp/' . $filename;

        ob_clean();
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $this->generateQRCode($tmp);
        $fp = fopen($tmp, 'rb');
        fpassthru($fp);
        fclose($fp);
        unlink($tmp);
        exit;

    }

    /**
     * Generate the correct headers and output the file data to the browser
     *
     * @param string $filename The name of the file to output
     * @param string $mime The mimetype of the file
     *
     * @return void
     */
    protected function returnObjData($filename, $mime, $writer)
    {
        // Manually return file data as PHPOffice does not appear to support streaming
        ob_clean();

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');

        //terminate php
        exit;
    }


    public function generateQRCode(string $file = null)
    {
        // See: https://www.twilio.com/blog/create-qr-code-in-php
        /* @var Image $logo */
        if ($logo = $this->getLogo()) {
            $options = new LogoOptions(
                [
                    'eccLevel' => \chillerlan\QRCode\QRCode::ECC_H,
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
                (new \chillerlan\QRCode\QRCode($options))->getMatrix($this->getQRLink())
            );

            if(Config::inst()->get(QRCode::class, 'use_public_assets')) {
                $logoFile = Director::publicFolder() . '/assets/' . $logo->getFilename();
            } else {
                $logoFile = Director::baseFolder() . '/assets/' . $logo->getFilename();
            }

            $qrcode = $qrOutputInterface->dump(
                $file,
                $logoFile
            );

            return $qrcode;
        } else {
            // no logo QR code

            $imageBase64 = !$file;

            $options = new QROptions(
                [
                    'eccLevel' => \chillerlan\QRCode\QRCode::ECC_L,
                    'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
                    'version' => 5,
                    'imageBase64' => $imageBase64,
                ]
            );

            return (new \chillerlan\QRCode\QRCode($options))->render($this->getQRLink(), $file);
        }

    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Title) {
            $this->Title = 'Barcode #' . $this->ID;
            if ($this->InternalLinkID) {
                $this->Title = $this->InternalLink()->MenuTitle;
            }
        }
    }

}
