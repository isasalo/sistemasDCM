<?php
// router.php: Router for PHP built-in server to map '/proyecto hospital/' paths
$uri = urldecode($_SERVER['REQUEST_URI']);

// Remove query string if present
$pos = strpos($uri, '?');
$cleanUri = ($pos !== false) ? substr($uri, 0, $pos) : $uri;

if (preg_match('#^/proyecto hospital/(.*)$#', $cleanUri, $matches)) {
    $requestedPath = __DIR__ . '/' . $matches[1];
    if (is_file($requestedPath)) {
        // Set proper mime types for assets
        $ext = pathinfo($requestedPath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'svg'  => 'image/svg+xml'
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($requestedPath);
        exit;
    }
}

// Default fallback
return false;
