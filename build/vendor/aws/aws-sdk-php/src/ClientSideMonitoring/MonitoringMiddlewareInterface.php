<?php

namespace Vendi\SesOffload\Vendor\Aws\ClientSideMonitoring;

use Vendi\SesOffload\Vendor\Aws\CommandInterface;
use Vendi\SesOffload\Vendor\Aws\Exception\AwsException;
use Vendi\SesOffload\Vendor\Aws\ResultInterface;
use Vendi\SesOffload\Vendor\GuzzleHttp\Psr7\Request;
use Vendi\SesOffload\Vendor\Psr\Http\Message\RequestInterface;
/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(RequestInterface $request);
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);
    public function __invoke(CommandInterface $cmd, RequestInterface $request);
}
