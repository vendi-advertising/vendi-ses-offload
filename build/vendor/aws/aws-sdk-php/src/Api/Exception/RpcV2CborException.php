<?php

namespace Vendi\SesOffload\Vendor\Aws\Api\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
class RpcV2CborException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
