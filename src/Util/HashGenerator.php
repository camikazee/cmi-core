<?php

declare(strict_types=1);

namespace Core\Util;

/**
 * Class HashGenerator.
 */
class HashGenerator
{
    public static function generate(): string
    {
        return md5(uniqid());
    }
}
