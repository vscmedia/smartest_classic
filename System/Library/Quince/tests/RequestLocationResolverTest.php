<?php

require_once dirname(__DIR__).'/QuinceRequestLocationResolver.class.php';
require_once dirname(__DIR__).'/Quince.class.php';

use QuinceController\QuinceRequestLocationResolver;

if (!class_exists('QuinceController\\QuinceBase') || !class_exists('QuinceController\\Quince')) {
    throw new RuntimeException('QuinceController namespace compatibility aliases are unavailable.');
}

$requestApi = new QuinceController\QuinceRequest;
$requestApi->setBasePath('tools/app');
if ($requestApi->getBasePath() !== '/tools/app/' || $requestApi->getDomain() !== '/tools/app/') {
    throw new RuntimeException('Base Path and legacy domain APIs do not share one value.');
}

class QuinceTestModule extends QuinceController\QuinceBase
{
    public function destination($to)
    {
        return $this->_handleDestination($to);
    }
}

$pathInfoRequest = new QuinceController\QuinceRequest;
$pathInfoRequest->setBasePath('/tools/app/index.php/');
$pathInfoRequest->setNamespace('default');
$pathInfoRequest->setModule('articles');
$module = new QuinceTestModule($pathInfoRequest);
if ($module->destination('/articles/view') !== '/tools/app/index.php/articles/view') {
    throw new RuntimeException('Redirect generation did not preserve the visible front controller.');
}

QuinceController\Quince::$routes = array(
    'articles:view' => array(
        'module' => 'articles', 'name' => 'view', 'action' => 'view', 'url' => '/articles/:id'
    ),
);
$router = new QuinceController\QuinceRouter;
$router->setRequest($pathInfoRequest);
if ($router->fetchRouteUrl('@articles:view?id=42') !== '/tools/app/index.php/articles/42') {
    throw new RuntimeException('Named-route generation did not preserve the visible front controller.');
}

function resolveLocation(array $server, $basePath = null)
{
    return (new QuinceRequestLocationResolver)->resolve($server, $basePath);
}

function assertLocation($name, array $server, array $expected, $basePath = null)
{
    $actual = resolveLocation($server, $basePath);
    foreach ($expected as $key => $value) {
        if ($actual[$key] !== $value) {
            throw new RuntimeException(
                $name.": {$key} expected ".var_export($value, true).', got '.var_export($actual[$key], true)
            );
        }
    }
}

$cases = array(
    'root rewrite' => array(
        array('REQUEST_URI' => '/articles/view', 'SCRIPT_NAME' => '/index.php'),
        array('basePath' => '/', 'requestString' => 'articles/view', 'frontController' => null, 'usesPathInfo' => false),
    ),
    'subdirectory rewrite' => array(
        array('REQUEST_URI' => '/tools/app/articles/view', 'SCRIPT_NAME' => '/tools/app/index.php'),
        array('basePath' => '/tools/app/', 'requestString' => 'articles/view', 'usesPathInfo' => false),
    ),
    'root PATH_INFO' => array(
        array('REQUEST_URI' => '/index.php/articles/view', 'SCRIPT_NAME' => '/index.php', 'PATH_INFO' => '/articles/view'),
        array('basePath' => '/index.php/', 'requestString' => 'articles/view', 'frontController' => 'index.php', 'usesPathInfo' => true),
    ),
    'subdirectory PATH_INFO' => array(
        array('REQUEST_URI' => '/tools/app/index.php/articles/view', 'SCRIPT_NAME' => '/tools/app/index.php', 'PATH_INFO' => '/articles/view'),
        array('basePath' => '/tools/app/index.php/', 'requestString' => 'articles/view', 'frontController' => 'index.php', 'usesPathInfo' => true),
    ),
    'visible controller without PATH_INFO' => array(
        array('REQUEST_URI' => '/tools/app/index.php/articles/view', 'SCRIPT_NAME' => '/tools/app/index.php'),
        array('basePath' => '/tools/app/index.php/', 'requestString' => 'articles/view', 'frontController' => 'index.php', 'usesPathInfo' => true),
    ),
    'root request' => array(
        array('REQUEST_URI' => '/', 'SCRIPT_NAME' => '/index.php'),
        array('basePath' => '/', 'requestString' => '', 'usesPathInfo' => false),
    ),
    'query string' => array(
        array('REQUEST_URI' => '/articles/view?id=42', 'SCRIPT_NAME' => '/index.php'),
        array('basePath' => '/', 'requestString' => 'articles/view'),
    ),
    'directory collision ignored' => array(
        array(
            'REQUEST_URI' => '/quince-demo/images/view',
            'SCRIPT_NAME' => '/quince-demo/index.php',
            'DOCUMENT_ROOT' => sys_get_temp_dir(),
        ),
        array('basePath' => '/quince-demo/', 'requestString' => 'images/view'),
    ),
    'missing optional variables' => array(
        array('REQUEST_URI' => '/articles/view'),
        array('basePath' => '/', 'requestString' => 'articles/view', 'frontController' => null),
    ),
    'PATH_INFO without REQUEST_URI' => array(
        array('SCRIPT_NAME' => '/index.php', 'PATH_INFO' => '/articles/view'),
        array('basePath' => '/index.php/', 'requestString' => 'articles/view', 'usesPathInfo' => true),
    ),
);

foreach ($cases as $name => $case) {
    assertLocation($name, $case[0], $case[1]);
}

assertLocation(
    'explicit Base Path',
    array('REQUEST_URI' => '/tools/app/articles/view', 'SCRIPT_NAME' => '/wrong/index.php'),
    array('basePath' => '/tools/app/', 'requestString' => 'articles/view'),
    '/tools/app/'
);
assertLocation(
    'legacy domain equivalent',
    array('REQUEST_URI' => '/tools/app/articles/view'),
    array('basePath' => '/tools/app/', 'requestString' => 'articles/view'),
    '/tools/app/'
);

foreach (array('/', 'foo/bar', '/foo/bar', 'foo/bar/') as $input) {
    $expected = $input === '/' ? '/' : '/foo/bar/';
    if (QuinceRequestLocationResolver::normalizeBasePath($input) !== $expected) {
        throw new RuntimeException("Base Path normalisation failed for {$input}");
    }
}

$mismatchRejected = false;
try {
    resolveLocation(array('REQUEST_URI' => '/other/articles'), '/tools/app/');
} catch (InvalidArgumentException $e) {
    $mismatchRejected = true;
}
if (!$mismatchRejected) {
    throw new RuntimeException('A configured Base Path missing from the request was not rejected.');
}

echo "Request-location tests passed (".(count($cases) + 4)." assertions groups).\n";
