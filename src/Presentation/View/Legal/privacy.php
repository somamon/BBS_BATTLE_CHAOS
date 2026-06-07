<?php
/**
 * プライバシーポリシー（確定版）。
 * @var string      $operator 運営者名（env LEGAL_OPERATOR）
 * @var string|null $contact  連絡先メール（env LEGAL_CONTACT / MAIL_FROM。未設定なら null）
 */
use App\Presentation\View\View;

$locale = current_locale();
$op = View::e($operator);
?>
<h2><?= t('legal.privacy.title') ?></h2>
<div class="card">
  <p class="muted"><?= t('legal.updated', ['date' => '2026-06-07']) ?></p>
</div>

<?php if ($locale === 'en'): ?>
<div class="card">
  <p>This Privacy Policy explains how <?= $op ?> (the "Operator") collects and handles personal data in BBS BATTLE CHAOS (the "Service").</p>

  <h3>1. Operator</h3>
  <p>The Operator of the Service and the entity responsible for handling personal data is <?= $op ?>.</p>

  <h3>2. Information we collect</h3>
  <ul>
    <li><strong>Email address</strong> — used as your login ID and for email verification and password reset.</li>
    <li><strong>Display name</strong> — shown publicly within the game.</li>
    <li><strong>Password</strong> — stored only as a one-way hash; we never store or can read the plaintext.</li>
    <li><strong>IP address and access information</strong> — IP address, request date/time, status, and error logs, stored to prevent unauthorized use and spam, to address violations of the Terms, and for operation.</li>
    <li><strong>Cookie</strong> — a strictly necessary session cookie to keep you logged in and to prevent CSRF, plus third-party cookies used by our advertising provider to deliver ads (see Section 4).</li>
  </ul>

  <h3>3. Purposes of use</h3>
  <ul>
    <li>To provide, operate, and maintain the Service;</li>
    <li>To authenticate users and send verification and password-reset emails;</li>
    <li>To prevent and respond to unauthorized or abusive use;</li>
    <li>To monitor reliability and improve quality (e.g., aggregate metrics such as registrations, activity, and error rates);</li>
    <li>To deliver, optimize, and measure advertising;</li>
    <li>To respond to inquiries.</li>
  </ul>

  <h3>4. Cookies and advertising</h3>
  <p>We use the session cookie necessary to operate the Service (set with HttpOnly and SameSite attributes, and Secure over HTTPS). Disabling it may prevent login and other functions.</p>
  <p>In addition, the Service displays advertisements provided by a third-party network, "Ninja AdMax" (Ninja Tools). The provider uses cookies and similar technologies to deliver, optimize, and measure ads, which may include personalized advertising based on your interests. Information collected via these advertising cookies (such as cookie identifiers, IP address, browsing history, and device information) is handled in accordance with the provider's own privacy policy.</p>
  <p>You can disable personalized advertising by changing your browser's cookie settings or by using the opt-out mechanisms offered by the provider or industry bodies.</p>

  <h3>5. Provision to third parties</h3>
  <p>We do not provide personal data to third parties except: (a) to an email delivery provider as a processor, solely to send verification and reset emails; (b) where required by law; or (c) where necessary to protect a person's life, body, or property. We do not sell personal data. Note that, in connection with the advertising described in Section 4, the advertising provider may collect information directly via cookies, for which the provider is responsible.</p>

  <h3>6. Retention period</h3>
  <p>We retain personal data while your account exists and only as long as necessary for the purposes above. When you delete your account, we delete your account information, holdings, and investment records. Anonymous posts are retained with identifying links removed. Operational logs are kept only for a reasonable period needed for security and operation.</p>

  <h3>7. Security measures</h3>
  <p>We take reasonable organizational and technical measures to protect personal data, including password hashing, HTTPS transport encryption, session protection, and access controls.</p>

  <h3>8. Your rights (disclosure, correction, deletion)</h3>
  <p>You may request disclosure, correction, suspension of use, or deletion of your personal data. You can delete your account yourself from My Page, or contact us using the details below.</p>

  <h3>9. Minors</h3>
  <p>The Service is open to all ages. Minors should use the Service with the consent of a parent or other legal guardian, and we collect and use minors' personal data on the basis of such consent.</p>

  <h3>10. Changes to this Policy</h3>
  <p>We may update this Policy as needed. Material changes will be announced within the Service. The updated Policy takes effect when posted.</p>

  <h3>11. Contact</h3>
  <p>For privacy inquiries, please contact the Operator:
    <?php if ($contact !== null): ?><a href="mailto:<?= View::e($contact) ?>"><?= View::e($contact) ?></a><?php else: ?>via the contact channel published within the Service<?php endif; ?>.</p>

  <p class="muted">Operator: <?= $op ?></p>
</div>
<?php else: ?>
<div class="card">
  <p>本プライバシーポリシーは、<?= $op ?>（以下「運営者」）が提供する BBS BATTLE CHAOS（以下「本サービス」）における個人データの取得・取扱いについて定めます。</p>

  <h3>1. 事業者</h3>
  <p>本サービスの運営者であり、個人データの取扱責任者は <?= $op ?> です。</p>

  <h3>2. 取得する情報</h3>
  <ul>
    <li><strong>メールアドレス</strong> — ログインIDとして、また本人確認メール・パスワード再設定に利用します。</li>
    <li><strong>表示名</strong> — ゲーム内で公開されます。</li>
    <li><strong>パスワード</strong> — 復元不可能なハッシュ値としてのみ保存します。平文を保存することはなく、運営者が知ることもできません。</li>
    <li><strong>IPアドレス・アクセス情報</strong> — IPアドレス、リクエスト日時・ステータス・エラーログ等を、不正利用防止・スパム対策・利用規約違反への対応および運用のために保存します。</li>
    <li><strong>Cookie</strong> — ログイン状態の維持とCSRF対策に必要な必須セッションCookieに加え、広告配信のために第三者配信事業者のCookie等を使用します（詳細は第4条）。</li>
  </ul>

  <h3>3. 利用目的</h3>
  <ul>
    <li>本サービスの提供・運営・維持のため</li>
    <li>ユーザー認証、確認メール・再設定メールの送信のため</li>
    <li>不正・濫用の防止および対応のため</li>
    <li>信頼性の監視と品質向上のため（登録数・活動・エラー率等の集計指標を含む）</li>
    <li>広告の配信・最適化・効果測定のため</li>
    <li>お問い合わせへの対応のため</li>
  </ul>

  <h3>4. Cookie・広告の配信</h3>
  <p>本サービスの運営に必要なセッションCookie（HttpOnly・SameSite 属性、HTTPS では Secure 属性）を使用します。無効化するとログイン等の機能が利用できない場合があります。</p>
  <p>あわせて、本サービスでは第三者配信事業者「忍者AdMax（忍者ツールズ）」による広告を掲載しており、同事業者が広告の配信・最適化・効果測定のために Cookie 等を使用します。これにより、ユーザーの興味・関心に応じた広告（パーソナライズ広告）が表示される場合があります。広告 Cookie によって取得される情報（Cookie 識別子・IPアドレス・閲覧履歴・デバイス情報等）の取扱いは、当該配信事業者の定めるプライバシーポリシーによります。</p>
  <p>パーソナライズ広告は、ブラウザの Cookie 設定の変更、または配信事業者・業界団体が提供するオプトアウトの仕組みにより無効化できます。</p>

  <h3>5. 第三者提供</h3>
  <p>運営者は、次の場合を除き、個人データを第三者に提供しません。(a) 確認メール・再設定メールの送信のため、委託先であるメール配信事業者に取扱いを委託する場合、(b) 法令に基づく場合、(c) 人の生命・身体・財産の保護のために必要な場合。個人データを販売することはありません。なお、第4条記載の広告配信に伴い、広告配信事業者が Cookie 等を通じてユーザーの情報を直接取得することがありますが、これは同事業者の責任で行われます。</p>

  <h3>6. 保存期間</h3>
  <p>個人データは、アカウントが存在する間、かつ上記目的に必要な期間に限り保存します。退会時には、アカウント情報・保有株・投資記録を削除します。匿名で投稿された内容は、本人と紐づく情報を除去したうえで残ります。運用ログは、セキュリティと運用に必要な合理的な期間に限り保存します。</p>

  <h3>7. 安全管理措置</h3>
  <p>運営者は、パスワードのハッシュ化、HTTPS による通信の暗号化、セッション保護、アクセス制御等、個人データを保護するための合理的な組織的・技術的措置を講じます。</p>

  <h3>8. 開示・訂正・削除等の請求</h3>
  <p>ユーザーは、自己の個人データの開示・訂正・利用停止・削除を請求できます。マイページの退会機能でご自身で削除できるほか、下記の連絡先までお問い合わせください。</p>

  <h3>9. 未成年者について</h3>
  <p>本サービスは全年齢を対象としています。未成年者が利用する場合は、親権者その他の法定代理人の同意を得たうえでご利用ください。未成年者の個人データは、当該同意のもとで取得・利用します。</p>

  <h3>10. ポリシーの変更</h3>
  <p>運営者は、必要に応じて本ポリシーを変更できます。重要な変更は本サービス内で告知します。変更後のポリシーは掲示した時点で効力を生じます。</p>

  <h3>11. お問い合わせ窓口</h3>
  <p>個人情報に関するお問い合わせは、運営者まで（
    <?php if ($contact !== null): ?><a href="mailto:<?= View::e($contact) ?>"><?= View::e($contact) ?></a><?php else: ?>本サービス内に掲示する窓口<?php endif; ?>）。</p>

  <p class="muted">運営者: <?= $op ?></p>
</div>
<?php endif; ?>

<p class="muted"><a href="/terms"><?= t('legal.terms.title') ?></a></p>
