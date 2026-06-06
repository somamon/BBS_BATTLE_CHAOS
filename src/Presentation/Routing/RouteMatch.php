<?php

declare(strict_types=1);

namespace App\Presentation\Routing;

final class RouteMatch
{
    /** @param array<string,string> $params 抽出したパスパラメータ */
    public function __construct(
        public readonly Route $route,
        public readonly array $params,
    ) {}
}