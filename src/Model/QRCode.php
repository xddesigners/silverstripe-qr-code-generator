<?php

namespace XD\QRCodeGenerator\Models;

use chillerlan\QRCode\QROptions;
use LeKoala\CmsActions\CustomLink;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
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
        'ExternalLink' => 'Varchar',
        'Token' => 'Varchar(16)'
    ];

    private static $has_one = [
        'InternalLink' => SiteTree::class
    ];

    private static $indexes = [
        'Token' => true, // non-unique: lookup speed; uniqueness enforced in generateToken()
    ];

    /**
     * Use an opaque, non-guessable token in the QR URL (/qr/<token>) instead of the
     * sequential record ID (/qr/<id>). On by default so new sites get non-enumerable
     * URLs. Sites that have already PRINTED ID-based codes must opt out to keep them
     * working (token mode resolves tokens only):
     *   XD\QRCodeGenerator\Models\QRCode:
     *     use_token: false
     */
    private static $use_token = true;

    /**
     * Length of the generated base62 token. Keep it short — a longer URL makes the
     * QR denser/harder to scan. 8 chars ≈ 2.2e14 combinations.
     */
    private static $token_length = 8;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['InternalLinkID', 'ExternalLink', 'Token']);

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

    /**
     * Add a "Download QR image" button to the edit form via silverstripe-cms-actions.
     * CustomLink routes to downloadFile() on this record; setNoAjax so the browser
     * navigates to the streamed file (a normal download) rather than an AJAX call.
     */
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if ($this->getLink()) {
            $download = CustomLink::create('downloadFile', _t(__CLASS__ . '.DownloadQRImage', 'Download QR image'));
            $download->setNoAjax(true);
            $download->setButtonIcon('export');
            $actions->push($download);
        }

        return $actions;
    }

    public function getQRLink()
    {
        $segment = ($this->config()->get('use_token') && $this->Token) ? $this->Token : $this->ID;
        return Controller::join_links(Director::absoluteBaseURL(), 'qr/' . $segment);
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

    public function downloadFile(): HTTPResponse
    {
        $filename = $this->getFileName();

        // Write to a unique temp file (not a predictable /tmp/<name>), read it back,
        // and return a proper HTTPResponse instead of raw header()/exit.
        $tmp = tempnam(sys_get_temp_dir(), 'qrcode');
        $this->generateQRCode($tmp);
        $data = file_get_contents($tmp);
        unlink($tmp);

        $response = HTTPResponse::create($data);
        $response->addHeader('Content-Type', 'application/octet-stream'); // force download
        $response->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->addHeader('Cache-Control', 'max-age=0');

        return $response;
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


    public function generateQRCode(?string $file = null)
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

            if (Director::publicDir()) {
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
        if (!$this->Token) {
            $this->Token = $this->generateToken();
        }
    }

    /**
     * Generate a unique, non-guessable base62 token of the configured length.
     */
    protected function generateToken(): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $max = strlen($alphabet) - 1;
        $length = max(4, (int) $this->config()->get('token_length'));

        do {
            $token = '';
            for ($i = 0; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, $max)];
            }
        } while (QRCode::get()->filter('Token', $token)->exclude('ID', (int) $this->ID)->exists());

        return $token;
    }

    /**
     * Backfill tokens for records created before the Token field existed.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $count = 0;
        foreach (QRCode::get()->filter('Token', '') as $qr) {
            $qr->write(); // onBeforeWrite generates the token
            $count++;
        }

        if ($count > 0) {
            DB::alteration_message("Generated tokens for {$count} QRCode record(s)", 'changed');
        }
    }

}
