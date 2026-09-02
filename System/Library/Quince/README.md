# Quince

Quince is Smartest's lightweight modular controller, router, and dispatcher. It
discovers application modules from configured directories; it does not replace
that module system with Composer autoloading.

## Base Path

`base_path` is the public prefix at which Quince is mounted. It is normalised to
either `/` or a leading-and-trailing-slash form such as `/tools/app/`.

```yaml
quince:
  base_path: /tools/app/
```

For PATH_INFO routing, the Base Path is the complete prefix used for links and
redirects, including the visible controller: `/tools/app/index.php/`. Quince
also records `index.php` separately as the front controller. Rewritten installs
retain `/` or `/tools/app/` and therefore keep their existing URL semantics.

The old `domain` configuration key and `QuinceRequest::getDomain()` /
`setDomain()` methods remain compatible aliases, but are deprecated because the
value is a path, not a hostname. New code should use `base_path`, `getBasePath()`
and `setBasePath()`.

Request location is resolved from `REQUEST_URI`, `SCRIPT_NAME`, and `PATH_INFO`
where available. Filesystem directories are not used to extend the Base Path,
because a routed segment can legitimately have the same name as a real folder.
An explicit Base Path is authoritative and a request outside it is rejected.

Run the focused regression suite with:

```sh
php System/Library/Quince/tests/RequestLocationResolverTest.php
```

## Standalone packaging direction

The standalone package should expose engine classes under `QuinceController\\`
with PSR-4 source files and retain temporary global aliases for Smartest. Module
classes remain dynamically discovered files and may use
`use QuinceController\\QuinceBase`; they do not need to live in the package's
namespace. YAML access should be behind the existing configuration helper so a
standalone Composer dependency on Spyc can be isolated cleanly.

`examples/Modules/Articles` is a complete discovered module showing aliases,
named routes, URL variables, fixed parameters, metadata, and an alternate Ajax
routing namespace/class.
