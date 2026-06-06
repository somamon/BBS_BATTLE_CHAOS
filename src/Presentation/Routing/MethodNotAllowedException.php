<?php

declare(strict_types=1);

namespace App\Presentation\Routing;

class MethodNotAllowedException extends \RuntimeException
{
    /** @param string[] $allowed 許可されているHTTPメソッド */
    public function __construct(public readonly array $allowed)
    {
        parent::__construct('Method Not Allowed');
    }
}