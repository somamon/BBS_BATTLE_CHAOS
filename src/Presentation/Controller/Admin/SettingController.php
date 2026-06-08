<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\SettingRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 設定（ゲームバランスのDB上書き・メンテモード・アナウンス）。
 * バランス値は空欄で保存すると上書きを解除し、env/既定値に戻る（解決順: DB → env → const）。
 */
final class SettingController
{
    use RendersAdmin;

    /** 管理画面から調整できるゲームバランスのキー（GAME_*）。 */
    private const BALANCE_KEYS = [
        'GAME_INITIAL_MONEY', 'GAME_MIN_INVEST', 'GAME_SPLIT_SHARES',
        'GAME_SHARE_PRICE_BASE', 'GAME_SHARE_PRICE_SLOPE',
        'GAME_POST_DECAY_PER_MIN', 'GAME_THREAD_DECAY_PER_MIN',
        'GAME_DECAY_MIN_FACTOR', 'GAME_DECAY_FULL_AT_HUMANS',
        'GAME_BOT_MAX_HUMANS', 'GAME_BOT_REFILL_TO',
        'GAME_BOT_MIN_INVEST', 'GAME_BOT_MAX_INVEST',
        'GAME_MONEY_CEILING',
    ];

    /**
     * キー別の許容範囲 [最小, 最大]（境界含む）。汎用の「0以上」では防げない経済破壊を弾く:
     *  - GAME_SHARE_PRICE_BASE が 0 だと株価が0になり、投資時に floor(shares / 0) でゼロ除算（全投資が500）。
     *  - GAME_SPLIT_SHARES が 1 超だと延命HP配分が負になり、投資のたびにレスHPが減る（経済破壊）。
     */
    private const BALANCE_BOUNDS = [
        'GAME_INITIAL_MONEY'        => [0, 1000000000],
        'GAME_MIN_INVEST'           => [1, 1000000000],
        'GAME_SPLIT_SHARES'         => [0.0001, 1],            // 0<v<=1
        'GAME_SHARE_PRICE_BASE'     => [0.01, 1000000000],     // >0（ゼロ除算防止）
        'GAME_SHARE_PRICE_SLOPE'    => [0, 1000000],
        'GAME_POST_DECAY_PER_MIN'   => [0, 1000000],
        'GAME_THREAD_DECAY_PER_MIN' => [0, 1000000],
        'GAME_DECAY_MIN_FACTOR'     => [0, 1],
        'GAME_DECAY_FULL_AT_HUMANS' => [0, 1000000],
        'GAME_BOT_MAX_HUMANS'       => [0, 1000000],
        'GAME_BOT_REFILL_TO'        => [0, 1000000000],
        'GAME_BOT_MIN_INVEST'       => [0, 1000000000],
        'GAME_BOT_MAX_INVEST'       => [0, 1000000000],
        'GAME_MONEY_CEILING'        => [0, 1000000000000],
    ];

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly AuditLogger $audit,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/settings */
    public function index(Request $request): Response
    {
        $current = $this->settings->all();
        $balance = [];
        foreach (self::BALANCE_KEYS as $k) {
            $balance[$k] = $current[$k] ?? '';
        }
        return $this->adminPage('settings', '設定', 'Admin/settings', [
            'balance'      => $balance,
            'maintenance'  => ($current['maintenance'] ?? '0') === '1',
            'announcement' => $current['announcement'] ?? '',
            'flash'        => Flash::pull(),
        ]);
    }

    /** POST /admin/settings */
    public function update(Request $request): Response
    {
        // まずバランス値を検証（不正があれば一切保存せず差し戻す）。
        $toSet = [];
        $toDelete = [];
        foreach (self::BALANCE_KEYS as $k) {
            $v = trim((string) $request->input($k, ''));
            if ($v === '') {
                $toDelete[] = $k;
                continue;
            }
            if (!is_numeric($v) || strlen($v) > 32) {
                Flash::set("「{$k}」は数値で入力してください。保存していません。");
                return Response::redirect('/admin/settings');
            }
            [$min, $max] = self::BALANCE_BOUNDS[$k];
            $f = (float) $v;
            if ($f < $min || $f > $max) {
                Flash::set("「{$k}」は {$min}〜{$max} の範囲で入力してください（経済破壊の防止）。保存していません。");
                return Response::redirect('/admin/settings');
            }
            $toSet[$k] = $v;
        }

        foreach ($toDelete as $k) {
            $this->settings->delete($k); // 上書き解除（env/既定へ）
        }
        foreach ($toSet as $k => $v) {
            $this->settings->set($k, $v);
        }

        $this->settings->set('maintenance', (string) $request->input('maintenance', '') === '1' ? '1' : '0');
        // 列長(255)に収まるよう切り詰め（桁あふれ500の防止）。
        $announcement = mb_substr(trim((string) $request->input('announcement', '')), 0, 255);
        $this->settings->set('announcement', $announcement);

        $this->audit->record((string) $this->auth->userId(), 'settings.update', null, null, null, $request->ip());
        Flash::set('設定を保存しました（次のリクエストから反映）。');
        return Response::redirect('/admin/settings');
    }
}
