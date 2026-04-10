<?php

namespace Vendi\SesOffload\Vendor\Aws\Handler\Guzzle;

use Exception;
use Vendi\SesOffload\Vendor\GuzzleHttp\Exception\ConnectException;
use Vendi\SesOffload\Vendor\GuzzleHttp\Exception\RequestException;
use Vendi\SesOffload\Vendor\GuzzleHttp\Utils;
use Vendi\SesOffload\Vendor\GuzzleHttp\Promise;
use Vendi\SesOffload\Vendor\GuzzleHttp\Client;
use Vendi\SesOffload\Vendor\GuzzleHttp\ClientInterface;
use Vendi\SesOffload\Vendor\GuzzleHttp\TransferStats;
use Vendi\SesOffload\Vendor\Psr\Http\Message\RequestInterface as Psr7Request;
/**
 * A request handler that sends PSR-7-compatible requests with Guzzle.
 */
class GuzzleHandler
{
    /** @var ClientInterface */
    private $client;
    /**
     * @param ClientInterface $client
     */
    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }
    /**
     * @param Psr7Request $request
     * @param array       $options
     *
     * @return Promise\Promise
     */
    public function __invoke(Psr7Request $request, array $options = [])
    {
        $request = $request->withHeader('User-Agent', $request->getHeaderLine('User-Agent') . ' ' . Utils::defaultUserAgent());
        return $this->client->sendAsync($request, $this->parseOptions($options))->otherwise(static function ($e) {
            $error = ['exception' => $e, 'connection_error' => $e instanceof ConnectException, 'response' => null];
            if ($e instanceof RequestException && $e->getResponse()) {
                $error['response'] = $e->getResponse();
            }
            return new Promise\RejectedPromise($error);
        });
    }
    private function parseOptions(array $options)
    {
        if (isset($options['http_stats_receiver'])) {
            $fn = $options['http_stats_receiver'];
            unset($options['http_stats_receiver']);
            $prev = isset($options['on_stats']) ? $options['on_stats'] : null;
            $options['on_stats'] = static function (TransferStats $stats) use ($fn, $prev) {
                if (is_callable($prev)) {
                    $prev($stats);
                }
                $transferStats = ['total_time' => $stats->getTransferTime()];
                $transferStats += $stats->getHandlerStats();
                $fn($transferStats);
            };
        }
        return $options;
    }
}
