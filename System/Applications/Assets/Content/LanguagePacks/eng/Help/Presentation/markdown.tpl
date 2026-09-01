<p>Markdown files are a plain-text way to create formatted text. They are easy to read before publishing, and Smartest converts them to HTML when the file is rendered.</p>
<p>Use Markdown when you want headings, lists, quotes, links, images and code without writing full HTML. For a friendly syntax guide, see <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">Markdown Guide: Basic Syntax</a>. For a formal reference, see <a href="https://commonmark.org/help/" target="_blank">CommonMark's quick reference</a> and <a href="https://spec.commonmark.org/" target="_blank">the CommonMark specification</a>.</p>

<p><strong>Headings</strong> are made by starting a line with one or more hash signs:</p>
<pre><code># Page title
## Section title
### Smaller section title</code></pre>

<p><strong>Bold and italic text</strong> use asterisks or underscores:</p>
<pre><code>Use **bold** for emphasis.
Use *italic* for a lighter emphasis.</code></pre>

<p><strong>Bulleted lists</strong> are made by starting each line with a dash or asterisk:</p>
<pre><code>- One
- Two
- Three</code></pre>

<p><strong>Numbered lists</strong> are made with numbers followed by dots:</p>
<pre><code>1. First step
2. Second step
3. Third step</code></pre>

<p><strong>Links</strong> use the text in square brackets followed by the URL in parentheses:</p>
<pre><code>[Visit Smartest](https://www.smartestproject.org)</code></pre>

<p>Smartest's own square-bracket link format can also be used in Markdown text where it is supported. For example, <code>[[page:home|Home]]</code> can be used to link to a Smartest page.</p>

<p><strong>Block quotes</strong> start with a greater-than sign:</p>
<pre><code>&gt; This text will be shown as a quotation.</code></pre>

<p><strong>Code</strong> can be written inline with backticks, or as a fenced block:</p>
<pre><code>Use `inline code` inside a sentence.

```
body &#123;
  color: #333;
&#125;
```</code></pre>

<p><strong>Attachments</strong> use the same lightweight Smartest attachment shortcut as Textile files. Add <code>&#123;attach:hero_image&#125;</code>, save the file, then use the Attachments tab to choose the image or embed code that should appear there.</p>

<p>You can also render a short Markdown fragment directly inside a template with <code>&#123;markdown&#125;</code> and <code>&#123;/markdown&#125;</code>. In Smartest template syntax, use <code>&lt;?sm:markdown:?&gt;</code> and <code>&lt;?sm:/markdown:?&gt;</code>.</p>
