<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 法務ページ（利用規約・プライバシーポリシー）。M5。
 * 本文は確定版。運営者名・連絡先のみ環境変数（LEGAL_OPERATOR / LEGAL_CONTACT）で差し替え可能。
 */
final class LegalController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
    ) {}

    /** GET /terms 利用規約 */
    public function terms(Request $request): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, t('legal.terms.title'), 'Legal/terms', $this->legalParams());
        return Response::html($html);
    }

    /** GET /privacy プライバシーポリシー */
    public function privacy(Request $request): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, t('legal.privacy.title'), 'Legal/privacy', $this->legalParams());
        return Response::html($html);
    }

    /**
     * 運営者・連絡先は事実情報のため環境変数から渡す（未設定でも本文は完結する）。
     * @return array{operator:string,contact:?string}
     */
    private function legalParams(): array
    {
        // 連絡先は実在アドレスを既定にする（MAIL_FROM の no-reply 等にフォールバックしない）。
        $operator = (string) (getenv('LEGAL_OPERATOR') ?: 'BBS BATTLE CHAOS 運営チーム');
        $contact  = (string) (getenv('LEGAL_CONTACT') ?: '8556iamsmartphone0124@gmail.com');

        return [
            'operator' => $operator,
            'contact'  => $contact !== '' ? $contact : null,
        ];
    }
}
