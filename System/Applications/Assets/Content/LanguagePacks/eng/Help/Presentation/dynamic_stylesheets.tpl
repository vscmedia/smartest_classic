<p>Dynamic stylesheets are ordinary stylesheet assets that are written with SCSS syntax and compiled by Smartest before they are linked from a page.</p>
<p>In addition to normal SCSS variables, mixins, nesting and imports, Smartest makes a small set of CMS-aware variables available before the file is compiled. These values are resolved on the server, so the browser receives plain CSS.</p>

<p><strong>Page fields</strong> use the prefix <code>$sm-page-field-</code>. For example, a page field called <code>background_colour</code> is available as <code>$sm-page-field-background_colour</code>.</p>

<pre><code>$page-background: $sm-page-field-background_colour;

body &#123;
  background-color: $page-background;
&#125;</code></pre>

<p><strong>Image placeholders</strong> can be used as URL values. A placeholder called <code>background_image</code> is available as <code>$sm-placeholder-background_image-url</code>.</p>

<pre><code>.hero &#123;
  background-image: url(#&#123;$sm-placeholder-background_image-url&#125;);
  background-size: cover;
  background-position: center;
&#125;</code></pre>

<p><strong>Site fields</strong> are available with the prefix <code>$sm-site-field-</code>. For compatibility, Smartest also exposes them as <code>$field_</code> variables.</p>

<pre><code>$brand-colour: $sm-site-field-brand_colour;

a &#123;
  color: $brand-colour;
&#125;</code></pre>

<p>You can also expose Smartest values as CSS custom properties, then use normal browser <code>var()</code> syntax elsewhere in the file. Smartest resolves the SCSS value first; CSS variables then behave exactly like static CSS.</p>

<pre><code>:root &#123;
  --site-brand-colour: #&#123;$sm-site-field-brand_colour&#125;;
  --page-background-colour: #&#123;$sm-page-field-background_colour&#125;;
  --hero-image: url(#&#123;$sm-placeholder-background_image-url&#125;);
&#125;

body &#123;
  background-color: var(--page-background-colour);
&#125;

.hero &#123;
  background-image: var(--hero-image);
&#125;</code></pre>

<p>Field and placeholder names are normalised the same way Smartest normalises template element names: lowercase letters, numbers and underscores. If a field or placeholder is not available in the current page context, Smartest uses a safe fallback value and records a compiler warning in the preview.</p>
<p>If a dynamic stylesheet uses only site-level values, it can be compiled and cached for the site. If it uses page fields or placeholders, Smartest compiles a page-specific cached CSS file so each page can receive its own values.</p>
