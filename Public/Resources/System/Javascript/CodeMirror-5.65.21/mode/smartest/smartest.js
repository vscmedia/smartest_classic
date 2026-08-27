// Smartest template tags for CodeMirror 5.
// Highlights <?sm:tag attribute="value":?> inside another base mode.
(function(mod) {
  if (typeof exports == "object" && typeof module == "object")
    mod(require("../../lib/codemirror"));
  else if (typeof define == "function" && define.amd)
    define(["../../lib/codemirror"], mod);
  else
    mod(CodeMirror);
})(function(CodeMirror) {
  "use strict";

  function defineSmartestMode(name, baseModeSpec) {
    CodeMirror.defineMode(name, function(config, parserConf) {
      var baseMode = CodeMirror.getMode(config, parserConf.baseMode || baseModeSpec || "null");
      var leftDelimiter = "<?sm:";
      var rightDelimiter = ":?>";
      var identifier = /[A-Za-z0-9_-]/;
      var operator = /[=\/]/;
      var last = null;

      function tokenBase(stream, state) {
        var string = stream.string;
        var tagStart = string.indexOf(leftDelimiter, stream.pos);

        if (tagStart == stream.pos) {
          stream.match(leftDelimiter);
          state.tokenize = tokenSmartest;
          last = "startTag";
          return "tag";
        }

        if (tagStart > -1) stream.string = string.slice(0, tagStart);
        var style = baseMode.token(stream, state.base);
        if (tagStart > -1) stream.string = string;
        return style;
      }

      function tokenSmartest(stream, state) {
        if (stream.match(rightDelimiter)) {
          state.tokenize = tokenBase;
          last = null;
          return "tag";
        }

        var ch = stream.next();

        if (/\s/.test(ch)) {
          last = "space";
          return null;
        }

        if (ch == "$") {
          stream.eatWhile(identifier);
          last = "variable";
          return "variable-2";
        }

        if (ch == "\"" || ch == "'") {
          state.tokenize = tokenString(ch);
          last = "string";
          return "string";
        }

        if (operator.test(ch)) {
          last = "operator";
          return "operator";
        }

        if (ch == "." || ch == ":" || ch == "|") {
          last = "operator";
          return "operator";
        }

        if (ch == "(" || ch == ")" || ch == "[" || ch == "]") {
          last = "bracket";
          return "bracket";
        }

        if (/\d/.test(ch)) {
          stream.eatWhile(/\d/);
          last = "number";
          return "number";
        }

        if (identifier.test(ch)) {
          stream.eatWhile(identifier);
          if (last == "startTag") {
            last = "tagName";
            return "tag";
          }
          last = "attribute";
          return "attribute";
        }

        last = null;
        return null;
      }

      function tokenString(quote) {
        return function(stream, state) {
          var escaped = false, ch;

          while ((ch = stream.next()) != null) {
            if (ch == quote && !escaped) {
              state.tokenize = tokenSmartest;
              break;
            }
            escaped = !escaped && ch == "\\";
          }

          return "string";
        };
      }

      return {
        startState: function() {
          return {
            base: CodeMirror.startState(baseMode),
            tokenize: tokenBase
          };
        },
        copyState: function(state) {
          return {
            base: CodeMirror.copyState(baseMode, state.base),
            tokenize: state.tokenize
          };
        },
        token: function(stream, state) {
          return state.tokenize(stream, state);
        },
        indent: function(state, textAfter) {
          if (state.tokenize == tokenBase && baseMode.indent) {
            return baseMode.indent(state.base, textAfter);
          }
          return CodeMirror.Pass;
        },
        innerMode: function(state) {
          if (state.tokenize == tokenBase) {
            return {mode: baseMode, state: state.base};
          }
          return {mode: this, state: state};
        },
        blockCommentStart: "<?sm:*",
        blockCommentEnd: "*:?>"
      };
    });
  }

  defineSmartestMode("smartest", "htmlmixed");
  defineSmartestMode("smartest-htmlmixed", "htmlmixed");
  defineSmartestMode("smartest-markdown", "markdown");
  defineSmartestMode("smartest-textile", "textile");
  defineSmartestMode("smartest-css", "css");
  defineSmartestMode("smartest-scss", "text/x-scss");
  defineSmartestMode("smartest-javascript", "javascript");

  CodeMirror.defineMIME("text/x-smartest", "smartest");
  CodeMirror.defineMIME("text/x-smartest-template", "smartest-htmlmixed");
  CodeMirror.defineMIME("text/x-smartest-markdown", "smartest-markdown");
  CodeMirror.defineMIME("text/x-smartest-textile", "smartest-textile");
  CodeMirror.defineMIME("text/x-smartest-css", "smartest-css");
  CodeMirror.defineMIME("text/x-smartest-scss", "smartest-scss");
  CodeMirror.defineMIME("text/x-smartest-javascript", "smartest-javascript");
});
