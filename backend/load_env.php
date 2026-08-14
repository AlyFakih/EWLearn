<?php
/**
 * Minimal .env loader - no composer dependency needed for a couple of
 * key=value secrets. Reads backend/.env (gitignored, uploaded directly to
 * the server, never committed) and returns the values as an array.
 *
 * Returns an array instead of using putenv()/getenv(): InfinityFree disables
 * putenv() (function_exists() still returns true and the call itself doesn't
 * error, it just silently fails to persist anything - confirmed live via a
 * throwaway diagnostic script), so anything relying on it to actually set
 * process environment variables would silently never work on this host.
 */
function load_env($path)
{
    $vars = [];
    if (!file_exists($path)) {
        return $vars;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key !== '') {
            $vars[$key] = $value;
        }
    }

    return $vars;
}
