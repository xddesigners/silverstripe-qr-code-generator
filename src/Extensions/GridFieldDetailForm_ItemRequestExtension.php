<?php

namespace XD\QRCodeGenerator\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\HTML;
use XD\QRCodeGenerator\Models\QRCode;

/**
 * Class SiteConfigExtension
 * @package XD\QRCodeGenerator\Extensions
 * @property GridFieldDetailForm_ItemRequest|GridFieldDetailForm_ItemRequestExtension $owner
 */
class GridFieldDetailForm_ItemRequestExtension extends Extension
{

    private static $allowed_actions = [
        'downloadQRImage'
    ];

    public function updateFormActions(FieldList $actions)
    {
        $record = $this->owner->getRecord();
        // This extension would run on every GridFieldDetailForm, so ensure you ignore contexts where
        // you are managing a DataObject you don't care about
        if (!$record->exists()) {
            return;
        }

        if ($record instanceof QRCode) {
            $classes = [
                "btn",
                "btn-outline-dark",
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
        $QRCode->downloadFile();
    }


}