<?php

namespace Vendi\SesOffload\Vendor\Aws\DefaultsMode\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration mode
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
