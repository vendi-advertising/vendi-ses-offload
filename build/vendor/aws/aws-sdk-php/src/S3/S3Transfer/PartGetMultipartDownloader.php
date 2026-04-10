<?php

namespace Vendi\SesOffload\Vendor\Aws\S3\S3Transfer;

use Vendi\SesOffload\Vendor\Aws\CommandInterface;
use Vendi\SesOffload\Vendor\Aws\Result;
use Vendi\SesOffload\Vendor\Aws\ResultInterface;
/**
 * Multipart downloader using the part get approach.
 */
final class PartGetMultipartDownloader extends AbstractMultipartDownloader
{
    /**
     * @inheritDoc
     */
    protected function getFetchCommandArgs(): array
    {
        $nextCommandArgs = $this->downloadRequestArgs;
        $nextCommandArgs['PartNumber'] = $this->currentPartNo;
        return $nextCommandArgs;
    }
    /**
     * @inheritDoc
     *
     * @param Result $result
     *
     * @return void
     */
    protected function computeObjectDimensions(ResultInterface $result): void
    {
        if (!empty($result['PartsCount'])) {
            $this->objectPartsCount = $result['PartsCount'];
        } else {
            $this->objectPartsCount = 1;
        }
        $this->objectSizeInBytes = self::computeObjectSizeFromContentRange($result['ContentRange'] ?? "");
    }
}
