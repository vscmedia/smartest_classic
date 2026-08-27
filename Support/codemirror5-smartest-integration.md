# CodeMirror 5 Smartest Integration

Smartest now uses CodeMirror 5.65.21 for source-oriented editors. The previous `CodeMirror-5.2` folder is still present for reference, but the interface header loads the new versioned folder.

## Asset Type Mapping

| Smartest context | Asset type / screen | CodeMirror mode |
| --- | --- | --- |
| Markdown text asset | `SM_ASSETTYPE_MARKDOWN_TEXT` | `smartest-markdown` |
| Textile text asset | `SM_ASSETTYPE_TEXTILE_TEXT` | `smartest-textile` |
| JavaScript asset | `SM_ASSETTYPE_JAVASCRIPT` | `smartest-javascript` |
| CSS stylesheet asset | `SM_ASSETTYPE_STYLESHEET` | `smartest-css` |
| SCSS dynamic stylesheet asset | `SM_ASSETTYPE_SCSS_DYNAMIC_STYLESHEET` | `smartest-scss` |
| HTML fragment asset | `SM_ASSETTYPE_HTML_FRAGMENT` | `smartest-htmlmixed` |
| Template source | master, container, itemspace, compound list, block list, single item templates | `smartest-htmlmixed` |
| SVG asset | `SM_ASSETTYPE_SVG_IMAGE` | `xml` |
| Unknown editable source | fallback | `smartest-htmlmixed` |

The `smartest-*` modes keep the relevant CodeMirror base mode, then highlight Smartest tags of the form:

```smartest
<?sm:tag attribute="value":?>
```

## Tag Builder Feasibility

A richer CodeMirror 5 addon for Smartest tags is feasible and should be kept separate from the mode:

1. The mode should only tokenize/highlight `<?sm:...:?>`.
2. An addon can provide completion/snippets for tag names, known attributes, and common values.
3. The addon can read a registry supplied by Smartest as JSON, so modules can contribute their own tags without hard-coding everything in JavaScript.
4. The first useful version could expose `Ctrl-Space` completion and a small toolbar command to insert a tag skeleton.

Suggested registry shape:

```json
{
  "attachment": {
    "attributes": {
      "name": {"required": true, "type": "varname"},
      "system": {"type": "boolean"},
      "format": {"type": "enum", "values": ["html", "textile", "markdown"]}
    }
  },
  "container": {
    "attributes": {
      "name": {"required": true, "type": "varname"},
      "instance": {"type": "varname"}
    }
  }
}
```

This keeps the syntax layer, editor assistance layer, and server-side rendering rules distinct.
