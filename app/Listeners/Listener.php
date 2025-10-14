<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Listeners;

use App\Services\Service as AppService;
use App\Traits\Service as ServiceTrait;
use App\Traits\Auth as AuthTrait;
use Phalcon\Mvc\User\Plugin as UserPlugin;

class Listener extends UserPlugin
{

    use ServiceTrait, AuthTrait;

    public function getLogger($channel = 'listen')
    {
        $appService = new AppService();

        return $appService->getLogger($channel);
    }

}
