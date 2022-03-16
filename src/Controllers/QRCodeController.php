<?php

namespace XD\QRCodeGenerator\Controllers;

use SilverStripe\Control\Controller;
use XD\QRCodeGenerator\Models\QRCode;

class QRCodeController extends Controller
{

    private static $url_handlers = [
        'qrcode/$*' => 'index',
    ];

    private static $allowed_actions = [
        'index',
    ];

    public function index()
    {
        $params = $this->getURLParams();
        if ($id = $params['ID']) {
            if ($qr = QRCode::get()->byID($id)) {
                return $this->redirect($qr->getLink(), 301);
            }
        }
        return $this->redirectBack();
    }

}