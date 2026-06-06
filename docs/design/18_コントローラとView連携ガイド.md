# 18. コントローラ → View → Response 連携ガイド

「URLアクセス → どのコントローラを呼ぶか（ルーティング）」のその先、
**コントローラが View を描画して `Response` を返すまで** を一枚にまとめたもの。

- ルーティング自体は [15_自作ルーティング実装ガイド.md](15_自作ルーティング実装ガイド.md) を参照。
- 本書はその続き＝「呼ばれたコントローラが最終的に HTML を返す部分」だけを扱う。

---

## 0. 全体像（1行で）

```
URL → Router がコントローラを選ぶ → コントローラが View を描画 → Response::html() で返す → send() で出力
                                      └────────── 本書の範囲 ──────────┘
```

ポイントは3つ。

1. **コントローラは echo しない。`Response` を「返す」だけ。**
2. **HTML は View（テンプレート）が作る。コントローラは HTML を書かない。**
3. **実際にブラウザへ出すのは `Response::send()` が最後に1回だけ。**

---

## 1. 「Viewを返す」とは具体的に何か

PHPでは「テンプレートを実行すると `echo` で出力される」。
これを **その場で出力せず、文字列として受け取って `Response` に詰める** のが「Viewを返す」の正体。

文字列化の方法は2通り。本プロジェクトは **(B) plates を採用**（[13_導入ライブラリリファレンス.md](13_導入ライブラリリファレンス.md#L82)）。

| 方式 | 仕組み | 本プロジェクト |
|---|---|---|
| (A) 自作 | `ob_start()` 〜 `ob_get_clean()` で出力を文字列に捕まえる（[11](11_PHPリファレンス.md#L553)/[12](12_PHPリファレンス補遺.md#L883)） | 原理理解用 |
| (B) plates | `League\Plates\Engine` の `render()` が文字列を返す | **これを使う** |

> (A)は原理。`render('thread/index', $data)` の内部でやっていることが(A)、と理解すればよい。

---

## 2. 現状コード → 目標コードの差分

### 2.1 現状（直接echo・要修正）

`src/Presentation/Controller/PlaygroundController.php` は今こうなっている：

```php
public function index(): void
{
    header('Content-Type: text/html; charset=UTF-8');  // ← コントローラが直接ヘッダ送信
    echo <<<'HTML'                                       // ← コントローラが直接HTML出力
    ...長大なHTML...
    HTML;
}
```

問題点：

- 出力タイミングがコントローラに散らばる → `Response::send()` の一元管理から外れる
- HTMLがコントローラに埋め込まれている → View分離できていない
- 戻り値が `void` → テストで戻り値を検証できない

### 2.2 目標（Viewを描画してResponseを返す）

```php
public function index(): Response
{
    $html = view('playground/index');   // View を文字列化
    return Response::html($html);        // Response に詰めて返す（送信はしない）
}
```

差分のキモ：

| 項目 | 現状 | 目標 |
|---|---|---|
| 戻り値 | `void` | `Response` |
| HTML | コントローラ内に直書き | `View/playground/index.php` に分離 |
| ヘッダ送信 | コントローラが `header()` | `Response::send()` に集約 |
| 出力 | コントローラが `echo` | `index.php` 末尾の `$response->send()` 1箇所 |

---

## 3. 部品をそろえる

### 3.1 View エンジンを使えるようにする（plates）

テンプレート置き場は `src/Presentation/View`（[16_責務マップ.md](16_責務マップ.md#L39)）。
グローバルに1個だけ生成して使い回す薄いヘルパーを用意すると、コントローラが綺麗になる。

`src/Presentation/View/view.php`（ヘルパー）

```php
<?php

declare(strict_types=1);

use League\Plates\Engine;

/**
 * テンプレートを描画して HTML文字列を返す（echoしない）。
 * 例: view('thread/index', ['threads' => $threads])
 */
function view(string $template, array $data = []): string
{
    static $engine = null;
    if ($engine === null) {
        // View ディレクトリを基準にする
        $engine = new Engine(__DIR__);
    }
    return $engine->render($template, $data);
}
```

> `static $engine` で初回だけ生成し、以降は使い回す（毎回 new しない）。
> DI（php-di）導入後は Engine をコンテナ管理に移すが、まずはこれで十分。

### 3.2 テンプレート（View）を書く

`src/Presentation/View/thread/index.php`

```php
<?php $this->layout('layout', ['title' => $title]) ?>

<h1><?= $this->e($title) ?></h1>
<ul>
  <?php foreach ($threads as $t): ?>
    <li><?= $this->e($t['name']) ?></li>
  <?php endforeach ?>
</ul>
```

`src/Presentation/View/layout.php`（共通レイアウト）

```php
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><title><?= $this->e($title) ?></title></head>
<body>
  <?= $this->section('content') ?>
</body>
</html>
```

> - `$this->e(...)` … **HTMLエスケープ（XSS対策）。出力は必ずこれを通す。**
> - `$this->layout(...)` … 共通レイアウトを指定。`$this->section('content')` に子の中身が入る。
> - **テンプレートに `namespace` は書かない**（現状の `View/Thread/index.php` には誤って `namespace` が付いている。plates は素のPHPテンプレートとして実行するので不要）。

### 3.3 コントローラを「Responseを返す」形にする

`src/Presentation/Controller/ThreadController.php`

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

class ThreadController
{
    public function index(Request $request): Response
    {
        // 本来はここで UseCase を呼んでデータを取得する
        $threads = [/* ... UseCase の戻り値 ... */];

        $html = view('thread/index', [
            'title'   => 'スレッド一覧',
            'threads' => $threads,
        ]);

        return Response::html($html);   // 返すだけ。送信しない。
    }
}
```

---

## 4. つながりの全体フロー

```
ブラウザ  GET /thread
  → public/index.php
  → Router::dispatch()                     ……… 15章の範囲
       └ ThreadController::index(Request)   ←─ ここから本書
            ├ UseCase でデータ取得
            ├ view('thread/index', $data)   … テンプレート実行 → HTML文字列
            └ return Response::html($html)  … Responseに詰めて返す
  → $response->send()                       … http_response_code + header + echo（1回だけ）
  → ブラウザにHTML表示
```

ここで分かる役割分担：

| 層 | やること | やらないこと |
|---|---|---|
| Controller | UseCase呼ぶ・Viewにデータ渡す・Response返す | HTMLを書く / echoする / headerを送る |
| View | データをHTMLに整形・エスケープ | DBアクセス / ビジネスロジック |
| Response | ステータス/ヘッダ/本文を保持し `send()` で出力 | 画面の中身を組み立てる |

---

## 5. パターン別の返し方

| ケース | 書き方 |
|---|---|
| 通常ページ（HTML全体） | `return Response::html(view('thread/index', $data));` |
| HTMX部分更新（レイアウトなし） | partialテンプレートを描画して `Response::html(...)`。`$this->layout()` を書かなければレイアウトは付かない |
| JSON API | `return Response::json(['id' => $id]);` |
| リダイレクト | `return Response::redirect('/thread');` |
| エラー画面 | `return Response::error(404, 'Not Found');` |

`Response` の各ファクトリの中身は [15章 Responseクラス](15_自作ルーティング実装ガイド.md#L252) を参照。

---

## 6. 移行手順（現状コードからの直し方）

1. `composer require league/plates`（[composer.json](../../composer.json) には記載済み。`vendor` 未生成なら `composer install`）
2. `src/Presentation/Http/Response.php` を [15章](15_自作ルーティング実装ガイド.md#L252) どおり実装（**現状未実装**）
3. `src/Presentation/View/view.php` ヘルパー作成（§3.1）
4. `src/Presentation/View/layout.php` 作成（§3.2）
5. 既存 `View/Thread/index.php` から `namespace` 行を削除し、テンプレートとして整える
6. `PlaygroundController` の直書きHTMLを `View/playground/index.php` に切り出し、`return Response::html(view('playground/index'))` に変更
7. `ThreadController::index()` を §3.3 の形に修正（現状の `return index;` は壊れているので置き換え）
8. ルーティング経由で `curl -i http://localhost:18723/thread` し、200・HTMLが返るか確認

---

## 7. よくある間違い

| 症状 | 原因 | 対処 |
|---|---|---|
| `headers already sent` 警告 | コントローラやテンプレートで先に `echo`/`header()` している | 出力は `Response::send()` だけにする |
| HTMLがそのまま画面に出る（タグが文字列で見える） | エスケープ漏れ or Content-Type未設定 | `Response::html()` を使う（`text/html`が付く） |
| `<?= $x ?>` でXSSできてしまう | `$this->e()` を通していない | 出力は必ず `$this->e(...)` |
| テンプレートで `Class not found` | テンプレートに `namespace` を書いている | テンプレートから `namespace` を削除 |

---

## 関連

- ルーティング本体（URL→コントローラ）: [15_自作ルーティング実装ガイド.md](15_自作ルーティング実装ガイド.md)
- plates の使い方: [13_導入ライブラリリファレンス.md](13_導入ライブラリリファレンス.md#L82)
- 出力バッファの原理（`ob_start`）: [11](11_PHPリファレンス.md#L553) / [12_PHPリファレンス補遺.md](12_PHPリファレンス補遺.md#L883)
- 責務マップ（どのファイルが何を持つか）: [16_責務マップ.md](16_責務マップ.md)
