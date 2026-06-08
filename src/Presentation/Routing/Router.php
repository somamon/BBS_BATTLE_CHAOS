<?php

declare(strict_types=1);

namespace App\Presentation\Routing;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class Router
{
    /** @var array<string, array<string, Route>> 静的ルート [method][path] => Route */
    private array $static = [];
    /** @var array<int, Route> 動的ルート（登録順） */
    private array $dynamic = [];

    /**
     * @param (callable(class-string):object)|null $resolver
     *        コントローラ生成方法。null なら new。php-di を使うなら fn($c) => $container->get($c)
     */
    public function __construct(
        private readonly mixed $resolver = null,
    ) {}

    public function add(string $method, string $path, array $handler, array $mw = []): void
    {
        $method = strtoupper($method);
        $path   = $this->normalize($path);
        $route  = new Route($method, $path, $handler, $mw);

        if ($route->isStatic) {
            $this->static[$method][$path] = $route;
        } else {
            $this->dynamic[] = $route;
        }
    }

    public function get(string $path, array $handler, array $mw = []): void
    {
        $this->add('GET', $path, $handler, $mw);
    }

    public function post(string $path, array $handler, array $mw = []): void
    {
        $this->add('POST', $path, $handler, $mw);
    }

    /** 末尾スラッシュ除去（"/" だけは残す） */
    private function normalize(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * パス＋メソッドからルートを解決。
     * @throws NotFoundException どのパスにも一致しない
     * @throws MethodNotAllowedException パスは一致するがメソッド不一致
     */
    public function resolve(string $method, string $path): RouteMatch
    {
        $method = strtoupper($method);
        $path   = $this->normalize($path);

        // 1) 静的ルート：O(1) で先引き（静的が常に動的より優先される）
        if (isset($this->static[$method][$path])) {
            return new RouteMatch($this->static[$method][$path], []);
        }

        // 2) 動的ルート：登録順に正規表現マッチ
        $allowed = [];   // パスは一致したが method 違いのものを集める（405用）
        foreach ($this->dynamic as $route) {
            if (preg_match($route->regex, $path, $m)) {
                if ($route->method === $method) {
                    // 名前付きキャプチャだけ取り出す（数値キーは捨てる）
                    $params = [];
                    foreach ($route->paramNames as $name) {
                        $params[$name] = $m[$name];
                    }
                    return new RouteMatch($route, $params);
                }
                $allowed[] = $route->method;
            }
        }

        // 3) 静的側でもパス一致・メソッド違いを拾う（405の取りこぼし防止）
        foreach ($this->static as $m2 => $routes) {
            if (isset($routes[$path])) {
                $allowed[] = $m2;
            }
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowed)));
        }
        throw new NotFoundException();
    }

    /** 解決 → ミドルウェア → コントローラ実行 → Response */
    public function dispatch(Request $request): Response
    {
        $match   = $this->resolve($request->method(), $request->path());
        $request = $request->withParams($match->params);

        // 最内：コントローラ呼び出し
        $core = function (Request $req) use ($match): Response {
            [$class, $action] = $match->route->handler;
            $controller = $this->resolver ? ($this->resolver)($class) : new $class();
            $result = $controller->$action($req);
            // 文字列が返ってきたら 200 HTML に正規化
            return $result instanceof Response ? $result : Response::html((string) $result);
        };

        // ミドルウェアを外→内に合成（array_reduce で内側から包む）
        $pipeline = array_reduce(
            array_reverse($match->route->middlewares),
            function (callable $next, string $mwName): callable {
                $middleware = MiddlewareFactory::make($mwName, $this->resolver); // 名前 → インスタンス（DI解決を渡す）
                return fn(Request $req) => $middleware->handle($req, $next);
            },
            $core
        );

        return $pipeline($request);
    }
}