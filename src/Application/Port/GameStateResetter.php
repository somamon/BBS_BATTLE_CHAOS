<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * ラウンドの遊技データ初期化ポート（M2）。Application 層が依存する抽象。
 *
 * 削除対象: 投資ログ・保有株・投稿・スレッド、相場フェーズ/ボットtickの状態。
 * 残すもの: ユーザーアカウント（所持金は初期値へ戻す）。
 * 破壊的操作のため、呼び出し側のトランザクション内で実行されることを前提とする。
 */
interface GameStateResetter
{
    /** 遊技データを初期化する。$humanMoney は人間ユーザーの所持金を戻す初期値。 */
    public function reset(int $humanMoney): void;
}
