<?php

declare(strict_types=1);

return [
    // 共通・ナビ
    'nav.overview' => '概要',
    'nav.threads'  => 'スレ一覧',
    'nav.ranking'  => 'ランキング',
    'nav.result'   => '結果',
    'nav.mypage'   => 'マイページ',
    'header.market' => '相場',
    'header.cash'  => '所持金',
    'auth.logout'  => 'ログアウト',
    'auth.register' => '登録',
    'auth.login'   => 'ログイン',
    'auth.google'  => 'Googleでログイン / 新規登録',
    'lang.other'   => 'English',
    'common.to_login' => 'ログインへ',
    'common.back_to_threads' => '← スレ一覧へ',

    // フッター / 法務
    'footer.terms' => '利用規約',
    'footer.privacy' => 'プライバシーポリシー',
    'footer.contact' => 'お問い合わせ',

    // お問い合わせ
    'contact.title' => 'お問い合わせ',
    'contact.intro' => 'ご質問・不具合の報告・アカウントに関するお問い合わせはこちらから。返信が必要な場合は、届くメールアドレスをご記入ください。',
    'contact.name' => 'お名前（任意）',
    'contact.email' => 'メールアドレス',
    'contact.message' => 'お問い合わせ内容',
    'contact.submit' => '送信する',
    'contact.done.title' => '送信しました',
    'contact.done.body' => 'お問い合わせを受け付けました。内容を確認のうえ、必要に応じてご記入のメールアドレスへ返信します。',
    'footer.disclaimer' => '本サービスはゲームです。ゲーム内通貨・株に財産的価値はなく、換金できません。',
    'legal.updated' => '最終更新: {date}',
    'legal.terms.title' => '利用規約',
    'legal.privacy.title' => 'プライバシーポリシー',

    // 退会（アカウント削除）
    'account.delete.title' => '退会（アカウント削除）',
    'account.delete.link' => '退会する（アカウントを削除）',
    'account.delete.lead' => '退会すると、以下のデータが削除されます。',
    'account.delete.item_account' => 'アカウント情報（メールアドレス・表示名・パスワード）',
    'account.delete.item_assets' => '所持金・保有株・投資の記録',
    'account.delete.item_posts' => '匿名で書いた投稿は、本人と紐づく情報を除去したうえで残ります。',
    'account.delete.warning' => 'この操作は取り消せません。削除されたデータは復元できません。',
    'account.delete.confirm_label' => '上記を理解し、退会してアカウントを削除することに同意します。',
    'account.delete.submit' => '退会する',
    'account.delete.cancel' => 'やめる（マイページへ戻る）',

    // 相場フェーズ
    'phase.boom'  => 'ブーム相場',
    'phase.calm'  => '平穏相場',
    'phase.storm' => '荒れ相場',
    'phase.crash' => '暴落相場',
    'phase.unknown' => '不明',

    // 投稿レベル / 状態
    'level.0' => '新規',
    'level.1' => '注目',
    'level.2' => '人気',
    'level.3' => '殿堂入り',
    'status.alive' => '生存',
    'status.dead'  => '消滅',

    // トップ（home）
    'home.tagline' => '面白い投稿を見抜いて“投資する”＝株を買う。早く見抜いた目利きが伸びる匿名掲示板バトル。',
    'home.what.title' => 'どんなサイト？',
    'home.what.p1' => 'ここは普通の掲示板ではありません。誰でも匿名でスレ立て・レスができ、登録ユーザーは「面白い」と思ったレスに<strong>投資して株を買えます</strong>。',
    'home.what.p2' => '後から投資が集まるほどそのレスの<strong>株価が上がる</strong>ので、まだ誰も気づいていない名レスを<strong>早く見抜いて仕込んだ人ほど資産が増える</strong>——「目利き」が主役のゲームです。',
    'home.layers.title' => '2つの層',
    'home.layers.anon.head' => '匿名掲示板層（誰でも）',
    'home.layers.anon.body' => '匿名でスレ立て・レス。お金も登録も不要。スレもレスも時間で<strong>朽ちて消える</strong>。',
    'home.layers.investor.head' => '投資家層（要登録）',
    'home.layers.investor.body' => '初期資金 <strong>{money}</strong> でレスの株を買う。後から買う人が増えるほど株価が上がり、早く仕込んだ株が値上がりする。',
    'home.loop.title' => '遊び方（中核ループ）',
    'home.loop.s1' => '匿名でレスを書く（無料・無報酬）',
    'home.loop.s2' => '面白いレスに<strong>投資する＝株を買う</strong>（要登録）',
    'home.loop.s3' => '後続の投資が増えるほど<strong>株価が上がる</strong>',
    'home.loop.s4' => '早く買った株が値上がりして<strong>資産が増える</strong>',
    'home.loop.s5' => '累計投資でレスが進化（<span class="badge">新規</span> → <span class="badge">注目</span> → <span class="badge">人気</span> → <span class="badge">殿堂入り</span>）',
    'home.loop.s6' => '終局時の<strong>総資産（所持金＋保有株の評価額）でランキング</strong>',
    'home.loop.note' => '勝つのは、良いレスを“早く”見抜いた目利き。',
    'home.rules.title' => '押さえておくルール',
    'home.rules.r1' => '<strong>早い者勝ち</strong>：株価は累計投資額で上がる（ボンディングカーブ）。後から買うほど割高。',
    'home.rules.r2' => '<strong>朽ちる</strong>：スレもレスもHPを持ち、放置すると時間で減って消滅（dead）。dead の株は紙くず。',
    'home.rules.r3' => '<strong>相場の天候</strong>：世界フェーズ（ブーム/平穏/荒れ/暴落）でHPの減りやすさが変わる。今の相場はヘッダーで確認できます。',
    'home.rules.r4' => '<strong>レベルで延命</strong>：投資が集まったレスは耐久(max HP)が上がり、名作ほど長生きする。',
    'home.rules.r5' => '<strong>含み損益</strong>：保有株の評価額は株価×鮮度(HP)。朽ちかけを買うと即含み損になることも。',
    'home.npc.title' => 'NPC投資家について',
    'home.npc.p1' => '人がまだ少ないうちは、運営が置く<strong>NPC投資家（自動で動くプログラム）</strong>が投稿に投資して相場を動かします。あなたが良いレスを早く見抜いて仕込めば、後から NPC が買い増して<strong>株価が上がり、含み益</strong>になります。ひとり・少人数でも「目利き」が成立するための仕組みです。',
    'home.npc.l1' => 'NPC は相場を動かす<strong>賑やかし役</strong>で、<strong>ランキングには参加しません</strong>。順位は実プレイヤー同士で競います。',
    'home.npc.l2' => '稼働するのは<strong>登録ユーザーが {limit} 人以下のとき</strong>だけ。人が集まるほど NPC は控えめになります。',
    'home.npc.l3' => '人が少ない時間帯は<strong>スレ/レスが朽ちる速度もゆっくり</strong>になり、過疎でも遊びやすくしています。',
    'home.npc.note' => 'NPC は学習型AIではなく、賑やかし＆練習相手のプログラムです。実プレイヤーが増えるほど、本物の目利き勝負になります。',
    'home.start.title' => 'はじめる',
    'home.start.threads' => 'スレ一覧を見る',
    'home.start.peek' => 'まずは覗いてみる（匿名OK）',
    'home.start.register' => '新規登録して投資する',

    // ログイン
    'login.title' => 'ログイン',
    'login.email' => 'メールアドレス',
    'login.password' => 'パスワード',
    'login.submit' => 'ログイン',
    'login.no_account' => 'アカウントがありませんか？',
    'login.register_link' => '新規登録',
    'login.unverified_pre' => 'メール未確認の方は',
    'login.resend_link' => '確認メールを再送',
    'login.forgot_link' => 'パスワードをお忘れですか？',

    // 新規登録
    'register.title' => '新規登録',
    'register.email' => 'メールアドレス',
    'register.name' => '表示名',
    'register.password' => 'パスワード',
    'register.password_hint' => '8文字以上で設定してください。',
    'register.submit' => '登録する',
    'register.have_account' => 'すでにアカウントをお持ちですか？',
    'register.agree' => '<a href="/terms" target="_blank" rel="noopener">利用規約</a>と<a href="/privacy" target="_blank" rel="noopener">プライバシーポリシー</a>に同意します。',

    // 確認メール送信完了
    'verify_sent.title' => '確認メールを送信しました',
    'verify_sent.body' => '<strong>{email}</strong> 宛に確認メールを送信しました。',
    'verify_sent.note1' => 'メール内のリンク（24時間有効）を開くと登録が完了し、そのままログインします。',
    'verify_sent.note2' => 'リンクを開くまではログインできません。',
    'verify_sent.resend_pre' => 'メールが届かない場合は',
    'verify_sent.resend_post' => 'できます。',

    // メール確認失敗
    'verify_result.title' => 'メール確認',
    'verify_result.retry_pre' => 'お手数ですが、もう一度',
    'verify_result.retry_link' => '新規登録',
    'verify_result.retry_post' => 'からやり直してください。',

    // 再送フォーム
    'resend.title' => '確認メールの再送',
    'resend.intro' => '登録に使ったメールアドレスを入力してください。未確認の場合のみ、確認メールを再送します。',
    'resend.email' => 'メールアドレス',
    'resend.submit' => '確認メールを再送する',

    // 再送完了
    'resend_done.title' => '確認メールを再送しました',
    'resend_done.body' => '<strong>{email}</strong> が未確認のアカウントとして登録されている場合、確認メールを再送しました。',
    'resend_done.note' => 'メール内のリンク（24時間有効）を開くと登録が完了します。古いリンクは無効になります。',

    // パスワード再設定（申請）
    'forgot.title' => 'パスワードの再設定',
    'forgot.intro' => '登録に使ったメールアドレスを入力してください。アカウントがある場合のみ、再設定用のリンクをお送りします。',
    'forgot.email' => 'メールアドレス',
    'forgot.submit' => '再設定メールを送る',

    // パスワード再設定（申請完了）
    'forgot_done.title' => '再設定メールを送信しました',
    'forgot_done.body' => '<strong>{email}</strong> のアカウントが存在する場合、パスワード再設定用のメールを送信しました。',
    'forgot_done.note' => 'メール内のリンク（1時間有効）を開いて、新しいパスワードを設定してください。古いリンクは無効になります。',

    // パスワード再設定（新パスワード入力）
    'reset.title' => '新しいパスワードの設定',
    'reset.password' => '新しいパスワード',
    'reset.submit' => 'パスワードを変更する',

    // スレ作成
    'thread_create.title' => '新しいスレッド',
    'thread_create.label' => 'タイトル',
    'thread_create.submit' => '立てる',

    // スレ一覧
    'threads.new' => '新しいスレッドを立てる',
    'threads.graveyard' => '墓場（朽ちたスレ）',
    'threads.empty' => 'まだ生存しているスレッドがありません。',
    'threads.lang_note' => '※いま表示しているのは日本語のスレッドです。言語はヘッダーから切り替えできます。',
    'pager.prev' => '← 前へ',
    'pager.next' => '次へ →',
    'pager.page' => '{page} / {total} ページ',
    'threads.board_hp' => '板HP',
    'threads.replies' => '{n}レス',

    // スレ詳細
    'show.board_hp' => '板HP',
    'show.invest_hint' => '面白いレスを見極めて株を買おう。早く買うほど株価が安い。',
    'show.replies_heading' => 'レス（{n}）',
    'show.no_replies' => 'まだレスがありません。最初の1レスを書こう。',
    'show.name_anon' => '名無しさん',
    'show.price' => '株価',
    'show.total_invested' => '累計投資',
    'show.total_shares' => '総株数',
    'show.holding' => '保有 {shares}株（評価額 {val}）',
    'show.invest_btn' => 'このレスに投資',
    'show.login_to_invest_pre' => '投資するには',
    'show.login_to_invest_post' => 'してください（レスは匿名で書けます）。',
    'show.write_reply' => 'レスを書く',
    'show.content_label' => '本文',
    'show.content_placeholder' => '本文を入力',
    'show.submit_reply' => '書き込む',

    // 墓場
    'dead.title' => '墓場',
    'dead.empty' => 'まだ朽ちたスレッドはありません。',
    'dead.died' => '{from} 〜 {to} に朽ちた',

    // ランキング
    'ranking.title' => '総資産ランキング',
    'ranking.empty' => 'まだランキングデータがありません。',
    'ranking.rank' => '順位',
    'ranking.name' => '表示名',
    'ranking.cash' => '所持金',
    'ranking.shares' => '株評価額',
    'ranking.total' => '総資産',

    // マイページ
    'me.title' => 'マイページ',
    'me.cash' => '所持金',
    'me.share_value' => '株評価額',
    'me.total' => '総資産',
    'me.holdings_title' => '保有株（投稿単位）',
    'me.empty' => 'まだ株を保有していません。気になるレスに投資してみよう。',
    'me.col_post' => '投稿',
    'me.col_lv' => 'Lv',
    'me.col_shares' => '株数',
    'me.col_price' => '株価',
    'me.col_value' => '評価額',
    'me.col_pnl' => '含み損益',
    'me.col_status' => '状態',

    // 結果
    'result.world_end' => '世界の終わり',
    'result.reason.all_dead' => '全てのスレッドが朽ち果てました',
    'result.reason.no_money' => '市場の資金が尽きました',
    'result.reason.over' => 'ゲーム終了',
    'result.ongoing_title' => 'ゲームは進行中です。',
    'result.ongoing_note' => '世界はまだ終わっていません。今のランキングはこちら。',
    'result.round' => 'ラウンド #{n}',
    'result.reset_note' => '終局しました。運営による初期化後、新しいラウンドが始まります（所持金・スレ・株はリセット）。',
    'result.final_ranking' => '最終ランキング',
    'result.current_ranking' => '現在のランキング',
    'result.no_players' => '参加者がいません。',

    // フラッシュ / メッセージ
    'flash.invest' => '{amount} を投資 → {shares}株を取得（株価¥{price}）。HP回復 {toHp}。投稿HP: {hp}{level}',
    'flash.invest_leveled' => '／「{label}」へ進化！',
    'flash.invest_failed' => '投資できませんでした: {msg}',
    'flash.verified' => 'メールアドレスを確認しました。ようこそ！',
    'flash.password_reset' => 'パスワードを変更しました。ログインしました。',
    'flash.logged_in' => 'ログインしました。',
    'flash.account_deleted' => '退会が完了しました。ご利用ありがとうございました。',

    // エラー（例外）メッセージ
    'err.invalid_credentials' => 'メールアドレスまたはパスワードが正しくありません',
    'err.email_unverified' => 'メールアドレスが未確認です。確認メールのリンクから登録を完了してください',
    'err.invalid_token' => '確認リンクが無効か、有効期限が切れています',
    'err.too_many_attempts' => '試行回数が多すぎます。しばらく時間をおいて再度お試しください',
    'err.google_failed' => 'Googleログインに失敗しました。お手数ですが、もう一度お試しください',
    'err.google_email_unverified' => 'Googleアカウントのメールアドレスが未確認のため、ログインできません',
    'err.invest_not_found' => '投稿が見つかりません',
    'err.invest_dead' => 'この投稿は朽ちており投資できません',
    'err.insufficient_funds' => '所持金が足りません',
    'err.invest_invalid_amount' => '投資額が不正です',
    'err.invest_too_small' => '投資額が小さすぎて株を取得できません',
    'err.all_fields' => 'すべての項目を入力してください',
    'err.must_agree' => '登録には、利用規約・プライバシーポリシーへの同意が必要です',

    // 入力検証
    'validation.email.required' => 'メールアドレスを入力してください',
    'validation.email.too_long' => 'メールアドレスが長すぎます',
    'validation.email.invalid' => 'メールアドレスの形式が正しくありません',
    'validation.name.required' => '表示名を入力してください',
    'validation.name.too_long' => '表示名は50文字以内にしてください',
    'validation.name.invalid' => '表示名に使用できない文字が含まれています',
    'validation.password.invalid' => 'パスワードに使用できない文字が含まれています',
    'validation.password.too_short' => 'パスワードは8文字以上にしてください',
    'validation.password.too_long' => 'パスワードが長すぎます（72バイト以内）',
    'validation.title.required' => 'タイトルを入力してください',
    'validation.title.too_long' => 'タイトルは255文字以内にしてください',
    'validation.content.required' => '本文を入力してください',
    'validation.content.too_long' => '本文は2000文字以内にしてください',
    'validation.message.required' => 'お問い合わせ内容を入力してください',
    'validation.message.too_long' => 'お問い合わせ内容は2000文字以内にしてください',
    'validation.generic' => '入力内容が正しくありません',
    'err.thread_not_found' => 'スレッドが見つかりません',
    'err.thread_dead' => 'このスレッドは朽ちており書き込めません',
];
