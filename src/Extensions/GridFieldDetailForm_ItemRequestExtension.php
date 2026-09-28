<?php

namespace XD\QRCodeGenerator\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements;
use XD\QRCodeGenerator\Models\QRCode;

/**
 * Adds a "Download QR image" action to the QRCode edit form.
 *
 * @package XD\QRCodeGenerator\Extensions
 * @property GridFieldDetailForm_ItemRequest|GridFieldDetailForm_ItemRequestExtension $owner
 */
class GridFieldDetailForm_ItemRequestExtension extends Extension
{

    private static $allowed_actions = [
        'downloadQRImage'
    ];

    /**
     * The download action is an <a> (needed for the no-ajax download). The admin
     * styles .btn-toolbar anchors with the link colour, which overrides btn-info's
     * white text and makes it blue-on-blue. Force white so it reads as a normal button.
     */
    private const DOWNLOAD_BUTTON_CSS = <<<'CSS'
.cms .btn-toolbar a.btn-info,
.cms .btn-toolbar a.btn-info:hover,
.cms .btn-toolbar a.btn-info:focus,
.cms .btn-toolbar a.btn-info:active { color: #fff !important; }
CSS;

    public function updateFormActions(FieldList $actions)
    {
        $record = $this->owner->getRecord();
        // This extension would run on every GridFieldDetailForm, so ensure you ignore contexts where
        // you are managing a DataObject you don't care about
        if (!$record->exists()) {
            return;
        }

        if ($record instanceof QRCode) {
            Requirements::customCSS(self::DOWNLOAD_BUTTON_CSS, 'xd-qr-download-button');

            $classes = [
                "btn",
                "btn-info",
                "font-icon-p-download",
                "no-ajax" // Class to disable ajax
            ];

            $button = HTML::createTag(
                "a",
                [
                    'class' => implode(" ", $classes),
                    'href' => $this->owner->Link('downloadQRImage')
                ],
                _t(__CLASS__ . '.DownloadQRImage', 'Download QR image')
            );
            $field = LiteralField::create('DownloadQRImage', $button );
            // $field = new LiteralField('previewLink','<div class="presentation-preview-link"><a class="font-icon-p-download btn btn-outline-dark" href="'.$previewLink.'" target="_blank">'._t(__CLASS__.'.DownloadQRImage','Download QR image').'</a></div>');
            // $field = FormAction::create('downloadQRImage',_t(__CLASS__.'.DownloadQRImage','Download QR image'))->addExtraClass('no-ajax');
            $actions->insertAfter('action_doDelete', $field);
        }
    }

    public function downloadQRImage()
    {
        /* @var QRCode $QRCode */
        $QRCode = $this->owner->getRecord();
        return $QRCode->downloadFile();
    }


}