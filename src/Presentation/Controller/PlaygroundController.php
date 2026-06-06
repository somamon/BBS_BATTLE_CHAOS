<?php

namespace App\Presentation\Controller;

class PlaygroundController
{
    public function index(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo <<<'HTML'
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JS Playground</title>
<style>
  body { font-family: -apple-system, sans-serif; margin: 0; background: #1e1e2e; color: #e4e4f0; }
  h1 { font-size: 16px; padding: 12px 16px; margin: 0; border-bottom: 1px solid #3b3d57; }
  .wrap { display: flex; flex-direction: column; gap: 10px; padding: 16px; }
  textarea {
    width: 100%; height: 200px; box-sizing: border-box; resize: vertical;
    background: #15151f; color: #e4e4f0; border: 1px solid #3b3d57; border-radius: 6px;
    padding: 12px; font-family: Menlo, Consolas, monospace; font-size: 14px; line-height: 1.5;
  }
  button {
    align-self: flex-start; background: #7c6cff; color: #fff; border: 0; border-radius: 6px;
    padding: 8px 18px; font-size: 14px; font-weight: 600; cursor: pointer;
  }
  button:hover { background: #9385ff; }
  pre {
    margin: 0; min-height: 80px; background: #15151f; border: 1px solid #3b3d57; border-radius: 6px;
    padding: 12px; font-family: Menlo, Consolas, monospace; font-size: 13px; white-space: pre-wrap;
  }
  .err { color: #f87171; }
</style>
</head>
<body>
  <h1>⚡ JS Playground <span style="color:#9a9ab5;font-weight:400;font-size:12px">— Ctrl/⌘ + Enter で実行</span></h1>
  <div class="wrap">
    <textarea id="code">console.log("Hello, World!");
1 + 2;</textarea>
    <button id="run">▶ 実行</button>
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
    }
}
