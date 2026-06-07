<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

class PlaygroundController
{
    public function index(Request $request): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JS Playground</title>
<style>
  body { font-family: "MS PGothic",Meiryo,sans-serif; margin: 0; background: #efefef; color: #000; }
  h1 { font-size: 15px; padding: 5px 10px; margin: 0; background: #e0e0e0; border-bottom: 2px solid #889; color: #cc0000; }
  .wrap { display: flex; flex-direction: column; gap: 8px; padding: 10px; max-width: 900px; margin: 0 auto; }
  textarea {
    width: 100%; height: 200px; box-sizing: border-box; resize: vertical;
    background: #fff; color: #000; border: 1px solid #999; border-radius: 0;
    padding: 8px; font-family: Menlo, Consolas, monospace; font-size: 13px; line-height: 1.5;
  }
  button {
    align-self: flex-start; background: #f0f0f0; color: #000; border: 2px outset #f5f5f5; border-radius: 0;
    padding: 2px 14px; font-size: 13px; cursor: pointer; font-family: inherit;
  }
  button:active { border-style: inset; }
  pre {
    margin: 0; min-height: 80px; background: #fff; border: 1px solid #999; border-radius: 0;
    padding: 8px; font-family: Menlo, Consolas, monospace; font-size: 13px; white-space: pre-wrap;
  }
  .err { color: #cc0000; }
</style>
</head>
<body>
  <h1>JS Playground <span style="color:#555;font-weight:400;font-size:12px">— Ctrl/⌘ + Enter で実行</span></h1>
  <div class="wrap">
    <textarea id="code">console.log("Hello, World!");
1 + 2;</textarea>
    <button id="run">実行</button>
    <pre id="out"></pre>
  </div>
<script>
  const code = document.getElementById("code");
  const out = document.getElementById("out");
  function run() {
    out.textContent = "";
    out.className = "";
    const orig = console.log;
    console.log = (...a) => {
      out.textContent += a.map(v => typeof v === "object" ? JSON.stringify(v, null, 2) : String(v)).join(" ") + "\n";
      orig.apply(console, a);
    };
    try {
      const r = (0, eval)(code.value);
      if (typeof r !== "undefined") out.textContent += "⇐ " + (typeof r === "object" ? JSON.stringify(r, null, 2) : String(r)) + "\n";
      if (out.textContent === "") out.textContent = "(出力なし)";
    } catch (e) {
      out.className = "err";
      out.textContent += "✕ " + (e && e.stack ? e.stack : e);
    } finally {
      console.log = orig;
    }
  }
  document.getElementById("run").addEventListener("click", run);
  code.addEventListener("keydown", e => {
    if ((e.metaKey || e.ctrlKey) && e.key === "Enter") { e.preventDefault(); run(); }
  });
</script>
</body>
</html>
HTML;

        return Response::html($html);
    }
}
