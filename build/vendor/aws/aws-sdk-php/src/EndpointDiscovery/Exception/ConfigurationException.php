<?php

namespace Vendi\SesOffload\Vendor\Aws\EndpointDiscovery\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for endpoint discovery
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
