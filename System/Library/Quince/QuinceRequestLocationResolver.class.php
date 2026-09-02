<?php

/**
 * Resolves the public mount prefix and routed portion of a Quince request.
 *
 * URL-space evidence is deliberately preferred to filesystem inspection. A
 * route segment may share a name with a real directory, so walking directories
 * below DOCUMENT_ROOT cannot reliably identify an application's mount point.
 */
final class QuinceRequestLocationResolver
{
    public static function normalizeBasePath($path)
    {
        $path = parse_url((string) $path, PHP_URL_PATH);
        $path = is_string($path) ? str_replace('\\', '/', trim($path)) : '';
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : $path.'/';
    }

    /**
     * @param array $server A server-variable array; pass $_SERVER in production.
     * @param string|null $configuredBasePath An authoritative configured prefix.
     * @param string|null $requestUriOverride Used by Quince::prepare($url).
     * @return array{basePath:string,requestString:string,frontController:?string,usesPathInfo:bool}
     */
    public function resolve(array $server, $configuredBasePath = null, $requestUriOverride = null)
    {
        $requestUri = $requestUriOverride !== null
            ? (string) $requestUriOverride
            : (string) ($server['REQUEST_URI'] ?? '/');
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        $requestPath = is_string($requestPath) && $requestPath !== '' ? '/'.ltrim($requestPath, '/') : '/';

        // SCRIPT_NAME describes the front controller in URL space. Unlike
        // PHP_SELF it should not include the routed PATH_INFO suffix.
        $scriptName = isset($server['SCRIPT_NAME']) ? (string) $server['SCRIPT_NAME'] : '';
        $scriptPath = $scriptName !== '' ? '/'.ltrim($scriptName, '/') : '';
        $frontController = $scriptPath !== '' ? basename($scriptPath) : null;
        if ($frontController === null || $frontController === '' || strpos($frontController, '.') === false) {
            $frontController = isset($server['SCRIPT_FILENAME'])
                ? basename((string) $server['SCRIPT_FILENAME'])
                : null;
        }

        $pathInfo = isset($server['PATH_INFO']) ? '/'.ltrim((string) $server['PATH_INFO'], '/') : '';
        $scriptVisible = $scriptPath !== ''
            && ($requestPath === $scriptPath || strncmp($requestPath, $scriptPath.'/', strlen($scriptPath) + 1) === 0);
        $usesPathInfo = $pathInfo !== '' || $scriptVisible;

        if ($configuredBasePath !== null && trim((string) $configuredBasePath) !== '') {
            $basePath = self::normalizeBasePath($configuredBasePath);
            $prefix = rtrim($basePath, '/');
            if ($basePath !== '/' && $requestPath !== $prefix
                && strncmp($requestPath, $basePath, strlen($basePath)) !== 0) {
                throw new \InvalidArgumentException(
                    "Configured Quince Base Path '{$basePath}' is not a prefix of request path '{$requestPath}'."
                );
            }
        } elseif ($usesPathInfo && $scriptPath !== '') {
            // In visible-front-controller mode the Base Path is the complete
            // public prefix used for generated URLs, including index.php/.
            $basePath = self::normalizeBasePath($scriptPath);
        } elseif ($scriptPath !== '') {
            $basePath = self::normalizeBasePath(dirname($scriptPath));
        } else {
            // With no reliable URL-space evidence, root is the least speculative
            // fallback. We intentionally do not infer deeper paths from real dirs.
            $basePath = '/';
        }

        if ($pathInfo !== '') {
            $requestString = ltrim($pathInfo, '/');
        } else {
            $prefix = rtrim($basePath, '/');
            if ($basePath === '/') {
                $requestString = ltrim($requestPath, '/');
            } elseif ($requestPath === $prefix) {
                $requestString = '';
            } else {
                $requestString = substr($requestPath, strlen($basePath));
            }
        }

        return array(
            'basePath' => $basePath,
            'requestString' => trim((string) $requestString, '/'),
            'frontController' => $usesPathInfo ? $frontController : null,
            'usesPathInfo' => $usesPathInfo,
        );
    }
}

// Smartest's historical include-based API remains available during migration.
if (!class_exists('QuinceController\\QuinceRequestLocationResolver', false)) {
    class_alias('QuinceRequestLocationResolver', 'QuinceController\\QuinceRequestLocationResolver');
}
