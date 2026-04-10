<?php

namespace Vendi\SesOffload\Vendor\Aws\Endpoint\UseFipsEndpoint\Exception;

use Vendi\SesOffload\Vendor\Aws\HasMonitoringEventsTrait;
use Vendi\SesOffload\Vendor\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for useFipsRegion
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
