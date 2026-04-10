<?php

namespace Vendi\SesOffload\Vendor\Aws\S3\S3Transfer\Utils;

use Vendi\SesOffload\Vendor\Aws\S3\S3Transfer\Progress\AbstractTransferListener;
abstract class AbstractDownloadHandler extends AbstractTransferListener
{
    protected const READ_BUFFER_SIZE = 8192;
    /**
     * Returns the handler result.
     * - For FileDownloadHandler it may return the file destination.
     * - For StreamDownloadHandler it may return an instance of StreamInterface
     *   containing the content of the object.
     *
     * @return mixed
     */
    abstract public function getHandlerResult(): mixed;
    /**
     * To control whether the download handler supports
     * concurrency.
     *
     * @return bool
     */
    abstract public function isConcurrencySupported(): bool;
}
