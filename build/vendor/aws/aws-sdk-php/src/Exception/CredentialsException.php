<?php

namespace Vendi\SesOffload\Vendor\Aws\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
class CredentialsException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
