<?php

namespace Vendi\SesOffload\Vendor\Aws\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
class UnresolvedEndpointException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
