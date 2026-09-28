<?php

namespace XD\QRCodeGenerator\Controllers;

use SilverStripe\Control\Controller;
use XD\QRCodeGenerator\Models\QRCode;

class QRCodeController extends Controller
{

    private static $url_handlers = [
        'qr/$*' => 'index',
    ];

    private static $allowed_actions = [
        'index',
    ];

    public function index()
    {
        $params = $this->getURLParams();
        if ($key = $params['ID'] ?? null) {
            // Token mode resolves by token only (non-enumerable); legacy mode by ID.
            $qr = QRCode::config()->get('use_token')
                ? QRCode::get()->find('Token', $key)
                : QRCode::get()->byID($key);

            if ($qr) {
                return $this->redirect($qr->getLink(), 301);
            }
        }
        return $this->redirectBack();
    }

}