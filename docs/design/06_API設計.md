# API設計

## ルーティング概要

バニラPHPのため、シンプルなフロントコントローラーパターンで実装。
`public/index.php` が全リクエストを受け、ルーターに振り分ける。

---

## エンドポイント一覧

### ページ（HTML返却）

| メソッド | パス | 説明 | 対応UseCase |
|---|---|---|---|
| GET | / | トップページ（スレ一覧＋世界状態） | ListThreads, GetWorldState |
| GET | /thread/{id} | スレッド詳細ページ | GetThread |
| GET | /thread/create | スレ作成フォーム | — |

### API（HTML片 or JSON返却、HTMX用）

| メソッド | パス | 説明 | 対応UseCase |
|---|---|---|---|
| POST | /api/thread | スレッド作成 | CreateThread |
| POST | /api/post | 投稿作成 | CreatePost |
| GET | /api/thread/{id}/state | スレッド状態取得（ポーリング用） | GetThread |
| GET | /api/thread/{id}/posts | スレッド内投稿一覧取得 | GetThread |
| GET | /api/world/state | 世界状態取得（ポーリング用） | GetWorldState |
| GET | /api/events/recent | 直近イベント取得 | GetRecentEvents |
| GET | /api/challenge/current | 現在のお題取得 | GetCurrentChallenge |

---

## エンドポイント詳細

### GET /

トップページ。

**レスポンス**: HTML（SSR）

**表示内容**

- 世界状態パネル（天候、危険度、フェーズ）
- 現在のお題
- 直近イベントログ（最新5件）
- スレッド一覧（HP・状態付き）
- スレ作成リンク

---

### GET /thread/{id}

スレッド詳細ページ。

**パラメータ**

| 名前 | 位置 | 型 | 説明 |
|---|---|---|---|
| id | path | string | スレッドID (ULID) |

**レスポンス**: HTML（SSR）

**表示内容**

- スレッドタイトル
- HPバー
- 汚染ゲージ
- 天候表示
- 投稿一覧
- 投稿フォーム
- リアルタイムイベント表示エリア

**HTMX属性（ポーリング）**

```
スレ状態: hx-get="/api/thread/{id}/state" hx-trigger="every 3s"
投稿一覧: hx-get="/api/thread/{id}/posts" hx-trigger="every 3s"
世界状態: hx-get="/api/world/state" hx-trigger="every 3s"
```

---

### POST /api/thread

スレッド作成。

**リクエスト**

| パラメータ | 型 | 必須 | 説明 |
|---|---|---|---|
| title | string | Yes | スレッドタイトル（1〜100文字） |
| _csrf | string | Yes | CSRFトークン |

Content-Type: `application/x-www-form-urlencoded`

**レスポンス（成功）**

HTTPステータス: 302
Location: `/thread/{新規スレッドID}`

**レスポンス（バリデーションエラー）**

HTTPステータス: 422

```html
<div class="error">タイトルは1〜100文字で入力してください</div>
```

---

### POST /api/post

投稿作成。ゲームのメインアクション。

**リクエスト**

| パラメータ | 型 | 必須 | 説明 |
|---|---|---|---|
| thread_id | string | Yes | 投稿先スレッドID |
| content | string | Yes | 投稿内容（1〜1000文字） |
| _csrf | string | Yes | CSRFトークン |

Content-Type: `application/x-www-form-urlencoded`

**レスポンス（成功）**

HTTPステータス: 200

```html
<!-- HTMX が受け取り、投稿一覧とイベントログを更新 -->
<div id="post-result" class="post-result">
  <div class="new-post">
    <!-- 新しい投稿のHTML -->
  </div>
  <div class="events" id="event-flash">
    <!-- 発生したイベントのHTML（あれば） -->
    <div class="event event--meteor">
      <span class="event-icon">☄️</span>
      <span class="event-message">隕石が落下！スレHPが30減少した！</span>
    </div>
  </div>
</div>
```

**レスポンス（投稿不可）**

HTTPステータス: 403

```html
<div class="error">このスレッドは崩壊しています</div>
```

**HTMX連携**

投稿フォームの属性:

```
hx-post="/api/post"
hx-target="#post-list"
hx-swap="beforeend"
hx-on::after-request="this.reset()"
```

---

### GET /api/thread/{id}/state

スレッド状態のみ返却。ポーリング用。

**レスポンス**

HTTPステータス: 200

```html
<div id="thread-state">
  <div class="hp-bar">
    <div class="hp-bar__fill" style="width: 73%"></div>
    <span class="hp-bar__text">HP: 73/100</span>
  </div>
  <div class="corruption-gauge">
    <div class="corruption-gauge__fill" style="width: 42%"></div>
    <span class="corruption-gauge__text">汚染: 42%</span>
  </div>
  <div class="thread-weather">天候: 赤い雨</div>
  <div class="thread-status">状態: active</div>
</div>
```

---

### GET /api/world/state

世界状態返却。ポーリング用。

**レスポンス**

HTTPステータス: 200

```html
<div id="world-state">
  <div class="world-weather">天候: 嵐</div>
  <div class="world-danger">危険度: 67</div>
  <div class="world-anomaly">異常度: 34</div>
  <div class="world-phase phase--chaos">フェーズ: chaos</div>
</div>
```

---

### GET /api/events/recent

直近イベント取得。

**パラメータ**

| 名前 | 位置 | 型 | デフォルト | 説明 |
|---|---|---|---|---|
| limit | query | int | 20 | 取得件数 |

**レスポンス**

HTTPステータス: 200

```html
<div id="event-log">
  <div class="event event--earthquake">
    <time>23:45:12</time>
    <span>地震が発生！全スレッドにダメージ！</span>
  </div>
  <div class="event event--red_rain">
    <time>23:42:08</time>
    <span>赤い雨が降り始めた…</span>
  </div>
  <!-- ... -->
</div>
```

---

### GET /api/challenge/current

現在有効なお題を取得。

**レスポンス**

HTTPステータス: 200

```html
<div id="current-challenge">
  <div class="challenge">
    <div class="challenge__rule">漢字禁止</div>
    <div class="challenge__desc">漢字を使った投稿は禁止です</div>
    <div class="challenge__penalty">違反: スレHP -10</div>
    <div class="challenge__timer">残り: 23:45</div>
  </div>
</div>
```

---

## エラーハンドリング

| HTTPステータス | 状況 | レスポンス |
|---|---|---|
| 400 | バリデーションエラー | エラーメッセージHTML |
| 403 | 操作不許可（崩壊スレへの投稿等） | エラーメッセージHTML |
| 404 | リソース不在 | 404ページHTML |
| 429 | レートリミット超過 | エラーメッセージHTML |
| 500 | サーバーエラー | エラーページHTML |

---

## CSRF対策

- セッションベースのトークン生成
- 全POSTリクエストで `_csrf` パラメータ検証
- トークン不一致時は 403 返却

---

## レートリミット

| 対象 | 制限 |
|---|---|
| 投稿（POST /api/post） | 同一IP: 10秒に1回 |
| スレ作成（POST /api/thread） | 同一IP: 60秒に1回 |
| ポーリング（GET /api/*） | 制限なし |

実装方式: セッション or ファイルベースで最終リクエスト時刻を記録。
