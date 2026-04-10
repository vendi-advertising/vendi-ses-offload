<?php

namespace Vendi\SesOffload\Vendor\Aws\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
class TokenException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
