# PHP 8 PEAR Usage Audit

This is a targeted audit of `Library/Pear` usage from outside the PEAR bundle.
The goal is to avoid spending upgrade time on unused PHP 4-era library code and
instead remove or replace the small surface area that Smartest still touches.

## Always-On Load Points

`System/init.php` adds `Library/Pear/` to the global PHP include path:

- `System/init.php:40`

Before this branch's YAML migration, `SmartestResponse` eagerly included PEAR
XML classes during every request:

- `System/Response/SmartestResponse.class.php:15` requires `PEAR.php`
- `System/Response/SmartestResponse.class.php:16` requires `XML/Unserializer.php`
- `System/Response/SmartestResponse.class.php:17` requires `XML/Serializer.php`

Those eager includes have now been removed. PEAR is no longer loaded just to
start a request.

## Active First-Party Consumers

`SmartestXmlHelper` used to be the active wrapper around PEAR XML:

- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:20`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:24`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:25`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:31`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:34`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:61`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:62`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:63`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:69`
- `System/Helpers/SmartestXml.helper/SmartestXmlHelper.class.php:72`

It has now been replaced with a native `SimpleXML` fallback parser. Its active
callers are mostly cached system type registries, and those callers now prefer
YAML where a YAML file exists:

- `System/Response/SmartestLog.class.php:94`
- `System/Response/SmartestLog.class.php:106`
- `System/Data/SmartestDataUtility.class.php:558`
- `System/Data/SmartestDataUtility.class.php:570`
- `System/Data/SmartestDataUtility.class.php:728`
- `System/Data/SmartestDataUtility.class.php:738`
- `System/Data/SmartestDataUtility.class.php:869`
- `System/Data/SmartestDataUtility.class.php:879`
- `System/Helpers/SmartestTodoList.helper/SmartestTodoListHelper.class.php:36`
- `System/Helpers/SmartestTodoList.helper/SmartestTodoListHelper.class.php:46`
- `System/Helpers/SmartestTodoList.helper/SmartestTodoListHelper.class.php:86`
- `System/Helpers/SmartestTodoList.helper/SmartestTodoListHelper.class.php:97`
- `System/Helpers/SmartestManyToMany.helper/SmartestManyToManyHelper.class.php:22`
- `System/Helpers/SmartestManyToMany.helper/SmartestManyToManyHelper.class.php:32`

The migrated YAML files are under `System/Core/Types/`:

- `caches.yml`
- `commenttypes.yml`
- `datatypes.yml`
- `errortypes.yml`
- `itemcolorschemes.yml`
- `logs.yml`
- `mtmrelationshiptypes.yml`
- `placeholdertypes.yml`
- `todoitemtypes.yml`
- `usertokens.yml`

The old XML files remain in place for reference during migration.

There is also an unused-looking compatibility method:

- `System/Helpers/SmartestDataObject.helper/SmartestDataObjectHelper.class.php:53`

`getBasicObjectSchemaXmlData()` points to `basicobjecttypes.xml`, but only
`basicobjecttypes.yml` exists and no first-party caller was found.

## Probably Inactive First-Party Mentions

These PEAR XML serializer references are inside commented-out code blocks:

- `System/Applications/Items/Items.class.php:508`
- `System/Applications/Items/Items.class.php:511`
- `System/Applications/Items/Items.class.php:3478`
- `System/Applications/Items/Items.class.php:3481`
- `System/Applications/Items/Items.class.php:3729`
- `System/Applications/Items/Items.class.php:3732`

These are comments only:

- `System/Applications/Assets/Assets.class.php:12`
- `System/Helpers/SmartestRssOutput.helper/SmartestRssOutputHelper.class.php:3`

## Bundled PEAR Packages With No First-Party References Found

No first-party include or class usage was found for these PEAR packages:

- `Cache/Lite`
- `Config`
- `DB.php`
- `MDB2.php`
- `HTTP/OAuth`
- `Net/DIME`
- `Net/Geo`
- `Net/GeoIP`
- `Net/LDAP`
- `Net/SMTP`
- `Net/Server`
- `Net/Socket`
- `Net/URL`
- `Numbers/Words`
- `PEAR/*` package manager classes
- `XML/RPC`
- `XML/RSS`
- `XML/SVG`
- `XML/Tree`

Some may be referenced internally by PEAR itself, tests, examples, or generated
documentation, but not by Smartest code outside `Library/Pear`.

## Recommended PHP 8 Path

1. Keep validating the generated YAML against real UI paths, especially the
   complex datatype filters and many-to-many relationship definitions.
2. Remove or rewrite the commented-out XML export code in `Items.class.php`.
3. Remove `Library/Pear/` from the global include path once no lazy include path
   consumers remain.
4. Delete unused PEAR packages, keeping only any package proven by runtime
   coverage or feature smoke tests to still be needed.

The highest-value first step is complete: the active PEAR XML dependency has
been collapsed from "loaded on every request" to zero known first-party runtime
callers.
