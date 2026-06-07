<?php
/**
 * 利用規約（確定版）。
 * @var string      $operator 運営者名（env LEGAL_OPERATOR）
 * @var string|null $contact  連絡先メール（env LEGAL_CONTACT / MAIL_FROM。未設定なら null）
 */
use App\Presentation\View\View;

$locale = current_locale();
$op = View::e($operator);
?>
<h2><?= t('legal.terms.title') ?></h2>
<div class="card">
  <p class="muted"><?= t('legal.updated', ['date' => '2026-06-07']) ?></p>
</div>

<?php if ($locale === 'en'): ?>
<div class="card">
  <p>These Terms of Use ("Terms") set out the conditions for using BBS BATTLE CHAOS (the "Service"), operated by <?= $op ?> (the "Operator"). By registering for or using the Service, you agree to these Terms.</p>

  <h3>Article 1 (Application)</h3>
  <p>These Terms apply to all use of the Service. If the Operator publishes separate guidelines, they form part of these Terms. Where they conflict, these Terms prevail unless stated otherwise.</p>

  <h3>Article 2 (Definitions)</h3>
  <ul>
    <li>"User" means anyone who uses the Service, whether registered or anonymous.</li>
    <li>"Registered User" means a User who has created an account.</li>
    <li>"In-game assets" means in-game currency, shares, holdings, scores, and similar items within the Service.</li>
    <li>"Content" means text and other information a User posts or transmits through the Service.</li>
  </ul>

  <h3>Article 3 (Registration and eligibility)</h3>
  <ul>
    <li>The Service is open to all ages. Minors must use the Service with the consent of a parent or other legal guardian.</li>
    <li>Each person may hold only one account. Creating multiple accounts to manipulate rankings or the market is prohibited.</li>
    <li>You are responsible for managing your credentials and for all activity under your account. The Operator is not liable for loss arising from inadequate management, misuse, or third-party use of your credentials.</li>
  </ul>

  <h3>Article 4 (Nature of the Service)</h3>
  <ul>
    <li>The Service is an entertainment game built around an anonymous message board. Registered Users can invest in-game currency in posts to acquire in-game shares.</li>
    <li>In-game currency and shares <strong>have no monetary value</strong>, are not legal tender or electronic money, and <strong>cannot be exchanged, cashed out, refunded, or transferred</strong> for real money or assets.</li>
    <li>Terms such as "invest", "shares", and "price" are game mechanics only and do not constitute financial instruments, securities, deposits, or gambling.</li>
    <li>The Service does not offer prizes, payouts, or any economic benefit in exchange for play.</li>
  </ul>

  <h3>Article 5 (In-game assets)</h3>
  <p>In-game assets are a usage right within the Service and are not your property. They may be adjusted, reduced, or reset (for example, at the end of a round or during maintenance). The Operator is not liable for any loss or change of in-game assets.</p>

  <h3>Article 6 (Prohibited conduct)</h3>
  <p>You must not:</p>
  <ul>
    <li>violate laws or these Terms, or infringe the rights of others (copyright, privacy, portrait, honor, etc.);</li>
    <li>post defamatory, discriminatory, obscene, violent, or otherwise harmful content;</li>
    <li>post content that promotes crime or harms public order and morals;</li>
    <li>gain unauthorized access, or abuse the Service using bots, scraping, AI, or other automated means (including bulk or mechanical posting or operations);</li>
    <li>impose excessive load on, or interfere with, the Service or its servers/networks;</li>
    <li>create multiple or fraudulent accounts, or manipulate rankings or the market;</li>
    <li>impersonate others or use the Service for commercial solicitation without permission;</li>
    <li>attempt to convert in-game assets into cash or trade them outside the Service;</li>
    <li>any other conduct the Operator reasonably deems inappropriate.</li>
  </ul>

  <h3>Article 7 (Content)</h3>
  <ul>
    <li>You are solely responsible for the Content you post.</li>
    <li>You grant the Operator a non-exclusive, royalty-free license to use (store, display, reproduce, and distribute) your Content to the extent necessary to operate and improve the Service.</li>
    <li>The Operator does not, in principle, monitor Content in advance, but may review it where a violation is suspected through a report or otherwise.</li>
    <li>The Operator may remove or hide Content, or restrict its handling, without prior notice if it reasonably determines the Content violates these Terms or operational needs require it. The Service is also designed so that threads and posts decay and disappear over time.</li>
  </ul>

  <h3>Article 8 (Refusal of registration; suspension and deletion of accounts)</h3>
  <p>The Operator may, without prior notice, suspend or delete an account or restrict use if a User violates these Terms, if registration information is found to be false, or if the Operator otherwise reasonably deems it necessary. The Operator is not liable for resulting disadvantage to the User.</p>
  <p>The Operator may also refuse a registration, or suspend or restrict use of the Service, without any obligation to disclose its reasons.</p>

  <h3>Article 9 (Withdrawal)</h3>
  <p>You may delete your account at any time from My Page. On deletion, your personal data is handled as described in the Privacy Policy.</p>

  <h3>Article 10 (Changes, suspension, and termination of the Service)</h3>
  <p>The Operator may change, add to, suspend, or terminate all or part of the Service, and may reset data, at any time without prior notice. The Operator is not liable for resulting damage to Users.</p>
  <p>For the purpose of maintaining and adjusting game balance, the Operator may change, without prior notice, the share-price formula, rewards, the ranking calculation method, the specifications of in-game assets, and other aspects of the game.</p>

  <h3>Article 10-2 (Advertising)</h3>
  <ul>
    <li>The Operator may display advertisements provided by a third-party network ("Ninja AdMax" / Ninja Tools) within the Service.</li>
    <li>The Operator does not warrant the content of advertisements, advertisers, or linked websites, and is not liable for any damage arising from them. Any transaction with an advertiser is at your own risk and responsibility.</li>
    <li>You must not fraudulently click advertisements, inflate impressions, or interfere with ad delivery.</li>
  </ul>

  <h3>Article 11 (Disclaimer)</h3>
  <ul>
    <li>The Service is provided "as is" without any warranty as to fitness for a particular purpose, accuracy, completeness, or continued availability.</li>
    <li>To the maximum extent permitted by law, the Operator is not liable for damages arising from use of the Service. Should the Operator bear liability, it is limited to direct and ordinary damages, and the Operator is not liable for special, indirect, incidental, or consequential damages.</li>
    <li>The Operator is not responsible for transactions, communications, or disputes between Users or between a User and a third party.</li>
  </ul>

  <h3>Article 12 (Intellectual property)</h3>
  <p>All intellectual property rights in the Service (excluding User Content) belong to the Operator or its licensors. Use of the Service does not transfer those rights to you.</p>

  <h3>Article 13 (Changes to these Terms)</h3>
  <p>The Operator may amend these Terms when necessary. Material changes will be announced within the Service in advance. Continued use after the effective date constitutes acceptance of the amended Terms.</p>

  <h3>Article 14 (Governing law and jurisdiction)</h3>
  <p>These Terms are governed by the laws of Japan. Any dispute relating to the Service shall be subject to the exclusive jurisdiction of the Tokyo District Court as the court of first instance.</p>

  <h3>Article 15 (Contact)</h3>
  <p>For inquiries regarding the Service, please contact the Operator:
    <?php if ($contact !== null): ?><a href="mailto:<?= View::e($contact) ?>"><?= View::e($contact) ?></a><?php else: ?>via the contact channel published within the Service<?php endif; ?>.</p>

  <p class="muted">Operator: <?= $op ?></p>
</div>
<?php else: ?>
<div class="card">
  <p>本利用規約（以下「本規約」）は、<?= $op ?>（以下「運営者」）が提供する BBS BATTLE CHAOS（以下「本サービス」）の利用条件を定めるものです。本サービスへの登録または利用をもって、本規約に同意したものとみなします。</p>

  <h3>第1条（適用）</h3>
  <p>本規約は、本サービスの利用に関わる一切に適用されます。運営者が別途定めるガイドライン等は本規約の一部を構成し、本規約と矛盾する場合は、特段の定めがない限り本規約が優先します。</p>

  <h3>第2条（定義）</h3>
  <ul>
    <li>「ユーザー」とは、登録の有無を問わず本サービスを利用するすべての者をいいます。</li>
    <li>「登録ユーザー」とは、アカウントを作成したユーザーをいいます。</li>
    <li>「ゲーム内資産」とは、本サービス内の仮想通貨・株・保有・スコア等をいいます。</li>
    <li>「投稿コンテンツ」とは、ユーザーが本サービスを通じて投稿・送信する文章その他の情報をいいます。</li>
  </ul>

  <h3>第3条（登録・利用資格）</h3>
  <ul>
    <li>本サービスは全年齢を対象とします。未成年者が利用する場合は、親権者その他の法定代理人の同意を得たうえで利用するものとします。</li>
    <li>アカウントは1人につき1つのみ保有できます。ランキングや相場を操作する目的での複数アカウントの作成を禁止します。</li>
    <li>ユーザーは自己の認証情報を適切に管理する責任を負い、アカウントでの一切の行為について責任を負います。管理不十分・第三者による使用等によって生じた損害について、運営者は責任を負いません。</li>
  </ul>

  <h3>第4条（本サービスの性質）</h3>
  <ul>
    <li>本サービスは、匿名掲示板を題材としたエンターテインメント・ゲームです。登録ユーザーは、ゲーム内の仮想通貨を投稿に投資してゲーム内の株を取得できます。</li>
    <li>ゲーム内通貨および株には<strong>財産的価値はなく</strong>、法定通貨・電子マネーのいずれでもありません。現実の金銭・資産との<strong>交換・換金・払戻し・譲渡はできません</strong>。</li>
    <li>「投資」「株」「株価」等の表現はゲーム上の仕組みであり、金融商品・有価証券・預け金・賭博のいずれにも該当しません。</li>
    <li>本サービスは、プレイの対価としての賞品・配当その他の経済的利益を一切提供しません。</li>
  </ul>

  <h3>第5条（ゲーム内資産の取扱い）</h3>
  <p>ゲーム内資産は本サービス内の利用上の地位にすぎず、ユーザーの所有物ではありません。ラウンドの終了時やメンテナンス等に際して、調整・減少・初期化されることがあります。ゲーム内資産の消失・変動について、運営者は責任を負いません。</p>

  <h3>第6条（禁止事項）</h3>
  <p>ユーザーは、次の行為をしてはなりません。</p>
  <ul>
    <li>法令もしくは本規約に違反する行為、または第三者の権利（著作権・プライバシー・肖像・名誉等）を侵害する行為</li>
    <li>誹謗中傷・差別・わいせつ・暴力的その他有害なコンテンツの投稿</li>
    <li>犯罪を助長し、または公序良俗に反するコンテンツの投稿</li>
    <li>不正アクセス、または、ボット・スクレイピング・AIその他の自動的手段を用いた濫用（大量・機械的な投稿や操作の繰り返しを含む）</li>
    <li>本サービスやサーバー・ネットワークに過度の負荷をかけ、または運営を妨害する行為</li>
    <li>複数アカウントや不正なアカウントの作成、ランキング・相場の操作</li>
    <li>他者へのなりすまし、許可のない営業・勧誘行為</li>
    <li>ゲーム内資産を換金し、または本サービス外で取引しようとする行為</li>
    <li>その他、運営者が不適切と合理的に判断する行為</li>
  </ul>

  <h3>第7条（投稿コンテンツ）</h3>
  <ul>
    <li>投稿コンテンツについては、投稿したユーザーが一切の責任を負います。</li>
    <li>ユーザーは運営者に対し、本サービスの提供・改善に必要な範囲で投稿コンテンツを利用（保存・表示・複製・配信）する、無償・非独占的な権利を許諾するものとします。</li>
    <li>運営者は原則として投稿内容を事前に監視しませんが、通報その他の方法により本規約への違反が疑われる場合は、内容を確認することがあります。</li>
    <li>運営者は、投稿コンテンツが本規約に違反すると合理的に判断した場合、または運営上必要な場合、事前の通知なく削除・非表示・取扱いの制限を行うことができます。なお本サービスは、スレッド・投稿が時間経過で朽ちて消滅する設計です。</li>
  </ul>

  <h3>第8条（登録拒否・アカウントの停止・削除）</h3>
  <p>運営者は、ユーザーが本規約に違反した場合、登録情報に虚偽が判明した場合、その他必要と合理的に判断した場合、事前の通知なくアカウントの利用停止・削除または利用制限を行うことができます。これによりユーザーに生じた不利益について、運営者は責任を負いません。</p>
  <p>また運営者は、理由を開示する義務を負うことなく、ユーザーの登録を拒否し、または本サービスの利用を停止・制限することができます。</p>

  <h3>第9条（退会）</h3>
  <p>ユーザーはマイページからいつでも退会できます。退会時の個人データの取扱いは、プライバシーポリシーの定めによります。</p>

  <h3>第10条（本サービスの変更・中断・終了）</h3>
  <p>運営者は、本サービスの全部または一部を、事前の通知なくいつでも変更・追加・中断・終了し、またデータを初期化することができます。これによりユーザーに生じた損害について、運営者は責任を負いません。</p>
  <p>運営者は、ゲームバランスの維持・調整のため、株価の計算式、報酬、ランキングの算定方法、ゲーム内資産の仕様その他のゲーム内容を、事前の通知なく変更することができます。</p>

  <h3>第10条の2（広告の掲載）</h3>
  <ul>
    <li>運営者は、本サービスに第三者配信事業者（忍者AdMax（忍者ツールズ））による広告を掲載することがあります。</li>
    <li>運営者は、広告の内容・広告主・リンク先ウェブサイトについて保証せず、これらに起因する損害について責任を負いません。広告に関する取引は、ユーザーと広告主の責任において行ってください。</li>
    <li>広告の不正なクリック、インプレッションの水増し、その他広告配信を妨害する行為を禁止します。</li>
  </ul>

  <h3>第11条（免責）</h3>
  <ul>
    <li>本サービスは現状有姿で提供され、特定目的への適合性・正確性・完全性・継続的な提供について、いかなる保証も行いません。</li>
    <li>運営者は、法令で許される最大限の範囲において、本サービスの利用に起因する損害について責任を負いません。運営者が責任を負う場合でも、その範囲は直接かつ通常の損害に限られ、特別損害・間接損害・派生的損害については責任を負いません。</li>
    <li>ユーザー間またはユーザーと第三者との間の取引・連絡・紛争について、運営者は責任を負いません。</li>
  </ul>

  <h3>第12条（知的財産権）</h3>
  <p>本サービスに関する知的財産権（投稿コンテンツを除く）は、運営者またはその許諾者に帰属します。本サービスの利用は、これらの権利の移転を意味するものではありません。</p>

  <h3>第13条（規約の変更）</h3>
  <p>運営者は、必要に応じて本規約を変更できます。重要な変更は、あらかじめ本サービス内で告知します。効力発生日以降に本サービスを利用した場合、変更後の規約に同意したものとみなします。</p>

  <h3>第14条（準拠法・管轄）</h3>
  <p>本規約は日本法に準拠します。本サービスに関して紛争が生じた場合は、東京地方裁判所を第一審の専属的合意管轄裁判所とします。</p>

  <h3>第15条（お問い合わせ）</h3>
  <p>本サービスに関するお問い合わせは、運営者まで（
    <?php if ($contact !== null): ?><a href="mailto:<?= View::e($contact) ?>"><?= View::e($contact) ?></a><?php else: ?>本サービス内に掲示する窓口<?php endif; ?>）。</p>

  <p class="muted">運営者: <?= $op ?></p>
</div>
<?php endif; ?>

<p class="muted"><a href="/privacy"><?= t('legal.privacy.title') ?></a></p>
