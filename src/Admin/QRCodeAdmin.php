<?php

namespace XD\QRCodeGenerator\Admin;

use SilverStripe\Admin\ModelAdmin;
use XD\QRCodeGenerator\Models\QRCode;

class QRCodeAdmin extends ModelAdmin{

    private static $managed_models = [
        QRCode::class
    ];

    private static $menu_title = 'QRCodes';

    private static $url_segment = 'qr-codes';

    private static $menu_icon_class = 'font-icon-mobile';

    private static $menu_priority = -1;

}