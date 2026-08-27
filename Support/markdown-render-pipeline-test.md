# Smartest Markdown Render Pipeline Test

This file exercises the Markdown version of the formatted text pipeline: Markdown formatting, Smartest attachments, Smartest square-bracket links, external links, Quince routes, and literal fenced-code examples.

## Basic Markdown

This paragraph includes **strong text**, _emphasised text_, `inline code`, and a normal Markdown link: [Example site](https://example.com).

- First bullet
- Second bullet with a Smartest page link: [[page:home|Home page]]
- Third bullet with a Smartest external link: [https://example.com Example site]

## Smartest Links

Internal page link: [[page:home|Home page]]

Model/item-style link: [[article:hello-world|Read the article]]

Mail link: [[mailto:test@example.com|Send email]]

External link in the same window: [https://example.com External example]

External link in a new window: [+https://example.com External example in new window]

Quince route link: [@desktop:home Desktop home]

## Attachments

The next attachment is on its own block.

{attach:hero_image}

Inline attachment test: before {attach:inline_image} after.

## Literal Examples

The following fenced code block should display Smartest syntax literally.

```
[[page:home|Do not parse this]]
[@desktop:home Do not parse this either]
[https://example.com Do not parse this external link]
{attach:do_not_render_this_attachment}
```

## Edge Cases

A malformed-looking bracket example should survive visibly: [[not quite finished

A repeated link appears twice and should render consistently both times:
[[page:home|Repeated Home]] and [[page:home|Repeated Home]]
