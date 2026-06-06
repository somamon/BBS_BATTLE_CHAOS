<?php

declare(strict_types=1);

namespace App\Presentation\Routing;

final class Route
{
    public readonly string $regex;
    /** @var string[] */
    public readonly array $paramNames;
    public readonly bool $isStatic;

    /**
     * @param array{0:class-string,1:string} $handler [Controller::class, 'action']
     * @param string[] $middlewares
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $handler,
        public readonly array $middlewares = [],
    ) {
        [$this->regex, $this->paramNames, $this->isStatic] = self::compile($path);
    }

    /**
     * パスパターンを正規表現へコンパイル。
     * "/thread/{id}" → "#^/thread/(?P<id>[^/]+)$#"
     * @return array{0:string,1:string[],2:bool}
     */
    private static function compile(string $path): array
    {
        $paramNames = [];

        // セグメントごとに処理：固定部は preg_quote、{name} は名前付きキャプチャに変換
        $segments = explode('/', $path);
        $converted = array_map(function (string $seg) use (&$paramNames) {
            // {name} または {name:正規表現}
            if (preg_match('#^\{(\w+)(?::(.+))?\}$#', $seg, $m)) {
                $paramNames[] = $m[1];
                $pattern = $m[2] ?? '[^/]+';
                return '(?P<' . $m[1] . '>' . $pattern . ')';
            }
            return preg_quote($seg, '#');
        }, $segments);

        $regex = '#^' . implode('/', $converted) . '$#';
        $isStatic = $paramNames === [];

        return [$regex, $paramNames, $isStatic];
    }
}