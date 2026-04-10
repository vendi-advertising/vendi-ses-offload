<?php

namespace Vendi\SesOffload\Vendor\Aws\Handler\GuzzleV6;

trigger_error(sprintf('Using the "%s" class is deprecated, use "%s" instead.', __NAMESPACE__ . '\GuzzleHandler', \Vendi\SesOffload\Vendor\Aws\Handler\Guzzle\GuzzleHandler::class), \E_USER_DEPRECATED);
class_alias(\Vendi\SesOffload\Vendor\Aws\Handler\Guzzle\GuzzleHandler::class, __NAMESPACE__ . '\GuzzleHandler');
