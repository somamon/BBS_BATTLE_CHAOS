# API設計

> 本書は [`20_リデザイン_朽ちると投資.md`](20_リデザイン_朽ちると投資.md) §5（ユースケース）/ §6（画面・ルート）に準拠した HTTP エンドポイント仕様。仕様・名称・パスは正典から取り、それを超えない。

## 1. 方針

- **フロントコントローラ + 自作ルーター**。`public/index.php` が全リクエストを受け、`App\Presentation\Routing\Router` がメソッド＋パスでコントローラへ振り分ける。
- **基本はサーバサイドHTML**（Plates テンプレート、SSR）。一覧・詳細・マイページ・ランキング・リザルトはすべて HTML を返す。
- **POST はフォーム送信（`application/x-www-form-urlencoded`）＋ CSRF 必須**。成功時は原則 PRG（Post/Redirect/Get）でリダイレクト。
- **一部 JSON 可**。同一エンドポイントでも `Accept: application/json` 指定時や HTMX ポーリング用途では JSON / HTML片を返してよい（MVP の主経路はフォーム＋リダイレクト）。
- レスポンス生成は `App\Presentation\Http\Response` の `html()` / `json()` / `redirect()` / `error()` を用いる。

詳細なルーター実装・ミドルウェアは [`05_アーキテクチャ.md`](05_アーキテクチャ.md)、DBスキーマは [`20_リデザイン_朽ちると投資.md`](20_リデザイン_朽ちると投資.md) §4 を参照。

---

## 2. エンドポイント一覧

正典 §6 のルート表に、認証ガード／用途を加えたもの。`{id}` はスレッドの ULID。

| メソッド | パス | 認証 | 用途 |
|---|---|---|---|
| GET | `/threads` | 匿名可 | スレ一覧（HPバー・勢い・時価総額）。現在の世界フェーズを併せて表示 |
| GET | `/thread/{id}` | 匿名可 | スレ詳細＋レス＋投資パネル |
| POST | `/threads` | 匿名可 | スレ作成 |
| POST | `/thread/{id}/posts` | 匿名可 | レス投稿 |
| POST | `/thread/{id}/invest` | **要登録** | 投資（`amount`） |
| GET | `/register` | — | ユーザー登録フォーム |
| POST | `/register` | — | ユーザー登録実行 |
| GET | `/login` | — | ログインフォーム |
| POST | `/login` | — | ログイン実行 |
| POST | `/logout` | 要登録 | ログアウト |
| GET | `/me` | **要登録** | マイページ（所持金・保有株・評価額・含み損益） |
| GET | `/ranking` | 匿名可 | 資産ランキング（`money + Σ株評価額` 降順） |
| GET | `/result` | 匿名可 | 終局リザルト（勝者・最終ランキング、§8 終局時） |

> いずれの GET（一覧・詳細・マイページ・ランキング）でも、アクセス時に世界フェーズの遅延遷移判定（正典 §2.5）と終局判定（正典 §8）を軽く行う。cron は使わない。

---

## 3. 共通仕様

### 3.1 CSRF

- セッションに 1 本のトークンを保持（`App\Presentation\Http\Csrf`）。`Csrf::token()` で取得、`Csrf::field()` で `<input type="hidden" name="_csrf">` を出力。
- **全 POST で `_csrf` を検証**。ルート定義のミドルウェア（`['csrf']`）が `$_SESSION['_csrf']` と入力 `_csrf` を突き合わせる。
- 不一致・欠落時は `Response::error(403, 'CSRFトークンが不正です')`。

### 3.2 認証ガード

- セッションに `user_id` を保持（ログイン時に格納、ログアウトで破棄）。
- **要登録のエンドポイント**（`POST /thread/{id}/invest`, `GET /me`, `POST /logout`）は未登録（`user_id` なし）でアクセスした場合 **`/login` へ 302 リダイレクト**。
- 匿名可のエンドポイントは未登録でも通す。スレ立て・レスは匿名識別子（IPハッシュ `author_hash`）で記録し、登録済みなら `author_id` / `creator_id` も紐付ける（正典 §4）。

### 3.3 エラー方針

- 異常系は `Response::error($status, $message)`。`message` はエスケープして `<h1>{status}</h1><p>{message}</p>` を返す（XSS 安全）。
- フォーム由来のバリデーションエラー（422）は、SSR ではフォームを再描画してエラー文言を添えてもよい（PRG を崩さない範囲で実装裁量）。

| ステータス | 状況 |
|---|---|
| 400 | パラメータ欠落・形式不正（`amount` 非数値等） |
| 403 | CSRF 不正 |
| 404 | スレッド不在 |
| 422 | バリデーション／業務制約違反（残高不足・`dead`・`amount<MIN_INVEST`・メール重複等） |

### 3.4 日時・数値の扱い

- API 表現・JSON 出力の日時は **ISO 8601**（例 `2026-06-06T12:34:56+09:00`）。DB は `DATETIME`。
- **HP は数値**として扱う。表示用の現在HPは遅延減衰（正典 §2.1）で算出した整数。保存値 `stored_hp`（DB の `hp`）と区別し、レスポンスには算出後の現在HPを載せる。
- 株評価額・含み損益・時価総額は整数丸めで表示してよい（内部計算は実数）。

---

## 4. ページ系エンドポイント詳細

### GET /threads — スレ一覧

- **認証**: 匿名可。
- **レスポンス（成功 200, HTML）**: 世界フェーズ（boom/calm/storm/crash と倍率）ヘッダー、`alive` スレ一覧。各スレは現在HP（遅延計算）・HPバー・勢い（`post_count`）・時価総額（＝現在HP）・`mutation_level` バッジ。`dead` は除外（正典 §5 一覧/詳細）。
- ヘッダーは未登録なら「登録して投資」ボタン、登録済みなら所持金を常時表示。

### GET /thread/{id} — スレ詳細

- **認証**: 匿名可。
- **パラメータ**: `id`（path, ULID）。
- **レスポンス（成功 200, HTML）**: タイトル・現在HP（HPバー）・`mutation_level`・`total_shares`・時価総額、レス一覧（`alive` の現在HP表示）、レス投稿フォーム、投資パネル。投資パネルは登録済みのみ操作可（未登録は「登録して投資」導線）。
- **失敗**: スレ不在 → `Response::error(404, 'スレッドが見つかりません')`。`status='dead'` の場合は閲覧可だが投資・レス不可の旨を表示。

### GET /register, GET /login — フォーム

- **認証**: 不要。
- **レスポンス（200, HTML）**: 登録／ログインフォーム（`_csrf` 埋め込み）。既ログイン状態でアクセスした場合は `/me` または `/threads` へ 302 してよい。

### GET /me — マイページ

- **認証**: 要登録（未登録は `/login` へ 302）。
- **レスポンス（200, HTML）**: 所持金、保有株一覧（スレごとの `shares` / 持ち分比率 / 評価額＝`(my_shares ÷ total_shares) × 現在HP`）、総資産、含み損益（正典 §2.3, §5 マイページ）。

### GET /ranking — 資産ランキング

- **認証**: 匿名可。
- **レスポンス（200, HTML）**: 全ユーザーの `money + Σ株評価額` を集計し降順表示（`name` で表示）。

### GET /result — 終局リザルト

- **認証**: 匿名可。
- **用途**: 終局条件（全スレ `dead`、または全ユーザー所持金合計 < `MIN_INVEST`）成立時の確定ランキングと勝者（総資産トップ）を表示（正典 §8）。
- **レスポンス（200, HTML）**: 勝者・最終ランキング・ラウンド情報。終局していない場合は `/ranking` へ 302 してよい（実装裁量）。

---

## 5. 書き込み系エンドポイント詳細

### POST /threads — スレ作成

- **認証**: 匿名可。
- **リクエスト**

| パラメータ | 型 | 必須 | 説明 |
|---|---|---|---|
| `title` | string | Yes | スレタイトル |
| `_csrf` | string | Yes | CSRFトークン |

- **処理**: thread を作成（HP満タン `THREAD_INIT_HP`、`max_hp=THREAD_MAX_HP`、`total_shares=0`、`mutation_level=0`、`status='alive'`）。登録者なら `creator_id` を記録（正典 §5 スレ作成）。
- **成功**: `302` → `Location: /thread/{新規スレID}`。
- **失敗**: `title` 空・規定長超過 → `422`。CSRF 不正 → `403`。

### POST /thread/{id}/posts — レス投稿

- **認証**: 匿名可。
- **リクエスト**

| パラメータ | 型 | 必須 | 説明 |
|---|---|---|---|
| `content` | string | Yes | 本文 |
| `_csrf` | string | Yes | CSRFトークン |

- **処理**: スレ `alive` 確認 → post 作成（`hp=POST_INIT_HP`）→ `thread.post_count++`（正典 §5 レス投稿）。匿名は `author_hash`、登録者は `author_id` も付与。
- **成功**: `302` → `Location: /thread/{id}`（PRG）。HTMX 経路では新規レスのHTML片を `200` で返してよい。
- **失敗**: スレ不在 → `404`。スレ `dead` → `422`（例: `このスレッドは朽ちています`）。`content` 空 → `422`。CSRF 不正 → `403`。

### POST /thread/{id}/invest — 投資（経済の心臓）

正典 §2.2 / §2.6 を 1 トランザクションで実行する中核 API。

- **認証**: **要登録**（未登録は `/login` へ 302）。
- **リクエスト**

| パラメータ | 型 | 必須 | 説明 |
|---|---|---|---|
| `amount` | int | Yes | 投資額（`MIN_INVEST` 以上、所持金以下） |
| `_csrf` | string | Yes | CSRFトークン |

リクエスト例:

```
POST /thread/01J.../invest
Content-Type: application/x-www-form-urlencoded

amount=120&_csrf=<token>
```

#### 処理（1トランザクション・正典 §2.2）

すべて `InvestService::invest()` 内の単一トランザクションで実行し、いずれか失敗時はロールバック:

1. 対象スレを行ロックし、遅延減衰を確定（現在HPを算出→`hp` 書戻し→`last_decay_at=now`、正典 §2.1）。`status` 再判定。
2. 残高・`alive`・`amount≥MIN_INVEST` を検証。
3. 出金 `users.money -= amount`。
4. 配分（正典 §2.2）:
   - HP回復 50% → `hp += 0.5×amount`（`max_hp` 上限、超過は sink へ）。
   - 配当 30% → **新株発行前の既存株主**へ持ち分比率で分配（投資者本人は対象外）。既存株主不在なら配当分は sink に合流。変異スレは配当に `MUTATION_DIV_BONUS` を掛けて厚く分配し、その分 sink を減らす（正典 §2.6）。
   - sink 20%（＋上記合流分）は経済から消滅。
5. 株発行 `holdings += amount`、`thread.total_shares += amount`。
6. 変異判定: `total_shares` が `MUTATION_TIERS` の閾値を超えたら `mutation_level++` し `max_hp` を引き上げ（正典 §2.6）。
7. `investments` に監査ログ（`amount` / `to_hp` / `to_dividend` / `to_sink`）を記録。

#### 成功レスポンス

- **`302` → `Location: /thread/{id}`**（PRG）。リダイレクト先の詳細でHP上昇・株保有・変異演出を反映。
- 配分結果（充当HP・配当総額・sink）と変異発生有無はフラッシュ（セッション）で詳細画面に表示する。
- JSON 要求時は配分内訳を返してよい:

```json
{
  "thread_id": "01J...",
  "amount": 120,
  "to_hp": 60,
  "to_dividend": 36,
  "to_sink": 24,
  "shares_issued": 120,
  "thread": { "hp": 480, "total_shares": 620, "mutation_level": 1 },
  "mutated": true,
  "money": 380
}
```

`mutated: true` の場合、UI は「変異種」バッジ等の演出を表示（正典 §2.6）。

#### 失敗レスポンス

| 状況 | ステータス | 例 |
|---|---|---|
| 未登録 | `302 → /login` | 認証ガード（§3.2） |
| `amount` 欠落・非数値 | `400` | `投資額が不正です` |
| `amount < MIN_INVEST` または所持金超過（残高不足） | `422` | `所持金が不足しています` |
| スレ不在 | `404` | `スレッドが見つかりません` |
| スレ `dead` | `422` | `このスレッドは朽ちており投資できません` |
| CSRF 不正 | `403` | `CSRFトークンが不正です` |

---

## 6. 認証系エンドポイント詳細

### POST /register — ユーザー登録

- **リクエスト**: `email`, `name`, `password`, `_csrf`。
- **処理**: 形式検証 → `email` 重複確認 → `password_hash()` で保存 → users 作成（`money=INITIAL_MONEY=500`）→ セッションに `user_id` 紐付け（正典 §5）。
- **成功**: `302` → `/me`（または `/threads`）。
- **失敗**: メール重複・形式不正 → `422`。CSRF 不正 → `403`。

### POST /login — ログイン

- **リクエスト**: `email`, `password`, `_csrf`。
- **処理**: `email` で引き → `password_verify()` → セッションに `user_id` 保存（正典 §5）。
- **成功**: `302` → `/me`（または直前ページ）。
- **失敗**: メール不在・パス不一致 → `422`（情報秘匿のため両者を同一メッセージにしてよい）。CSRF 不正 → `403`。

### POST /logout — ログアウト

- **認証**: 要登録。
- **リクエスト**: `_csrf`。
- **処理**: セッションの `user_id` を破棄。
- **成功**: `302` → `/threads`。

---

## 7. ステータスコードまとめ

| コード | 用途 |
|---|---|
| 200 | HTML ページ表示・HTMX/JSON 片の返却 |
| 302 | PRG リダイレクト・認証ガードによる `/login` 転送 |
| 400 | パラメータ欠落・型不正 |
| 403 | CSRF トークン不正 |
| 404 | スレッド不在 |
| 422 | 業務制約違反（残高不足・`dead`・`amount<MIN_INVEST`・メール重複・認証失敗） |
| 500 | 予期せぬサーバエラー（トランザクション失敗時はロールバックの上 500） |
