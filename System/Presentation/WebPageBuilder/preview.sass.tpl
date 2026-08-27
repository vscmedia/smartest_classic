<code>&lt;link rel="stylesheet" href="<?sm:$domain:?><?sm:$sass_live_web_path:?>"<?sm:if strlen($render_data.media):?> media="<?sm:$render_data.media:?>"<?sm:/if:?> /&gt;</code>

<div class="v-spacer"></div>
<?sm:if isset($sass_compile_result):?>
<p><strong>Compiled CSS cache path:</strong> <code><?sm:$sass_compile_result.cache_web_path:?></code></p>
<?sm:if isset($sass_compile_result.page) && $sass_compile_result.page:?>
<p><strong>Preview context page:</strong> <?sm:$sass_compile_result.page.title:?></p>
<?sm:/if:?>
<?sm:if count($sass_compile_result.warnings):?>
<p><strong>Compile warnings:</strong></p>
<ul>
<?sm:foreach from=$sass_compile_result.warnings item="warning":?>
  <li><?sm:$warning:?></li>
<?sm:/foreach:?>
</ul>
<?sm:/if:?>
<?sm:if !$sass_compile_result.ok:?>
<p><strong>Compile error:</strong> <?sm:$sass_compile_result.error:?></p>
<?sm:/if:?>
<?sm:/if:?>

<div class="v-spacer"></div>
<p><strong>File contents as rendered:</strong></p>

<pre><?sm:$compiled_css:?>
</pre>
