<?php

$locale = str_replace('-', '_', DatawrapperSession::getLanguage());
$domain = 'messages';
putenv('LANGUAGE=' . $locale);
setlocale(LC_ALL, $locale);

$__messages = array();

/*
 * load messages
 */
function load_messages($locale) {
    global $memcache;
    $locale = str_replace('-', '_', $locale);
    $mkey = 'l10n-messages-' . $locale;
    if (isset($_GLOBALS['dw-config']['memcache'])) {
        // pull translation from memcache
        $msg = $memcache->get($mkey);
        if (!empty($msg)) return $msg;
    }
    // core
    $messages = array();
    function parse($fn) {
        if (file_exists($fn)) {
            $msg = json_decode(file_get_contents($fn), true);
            $msgids = array_keys($msg);
            foreach ($msgids as $msgid) {
                $cleaned = _l10n_clean_msgid($msgid);
                if ($cleaned != $msgid) {
                    $msg[$cleaned] = $msg[$msgid];
                }
            }
            return $msg;
        }
        return array();
    }
    $messages['core'] = parse(ROOT_PATH . 'locale/' . $locale . '.json');
    $plugins = PluginQuery::create()->filterByEnabled(true)->find();
    foreach ($plugins as $plugin) {
        $messages[$plugin->getName()] = parse($plugin->getPath() . 'locale/' . $locale . '.json');
    }
    if (isset($_GLOBALS['dw-config']['memcache'])) {
        // store translation in memcache for one minute to prevent
        // us from loading the JSON for every request
        $memcache->set($mkey, $messages, 60);
    }
    return $messages;
}

/*
 * translate function
 */
function __($text, $domain = 'core', $fallback = '') {
    global $__messages;
    $text_cleaned = _l10n_clean_msgid($text);
    if (!isset($__messages[$domain]) || !isset($__messages[$domain][$text_cleaned])) {
        // no translation found
        return !empty($fallback) ? $fallback : $text;
    }
    return $__messages[$domain][$text_cleaned];
}

function _l10n_clean_msgid($msgid) {
    return trim(str_replace("\n", "", $msgid));
}

$__messages = load_messages($locale);
