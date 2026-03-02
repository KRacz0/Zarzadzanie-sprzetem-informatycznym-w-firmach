<?php

use voku\helper\AntiXSS;

function security_get_anti_xss(): AntiXSS {
    static $instance = null;
    if ($instance === null) {
        $instance = new AntiXSS();
    }

    return $instance;
}

function security_get_html_purifier(): HTMLPurifier {
    static $instance = null;
    if ($instance === null) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'b,strong,i,em,u,br,p,ul,ol,li');
        $instance = new HTMLPurifier($config);
    }

    return $instance;
}

function sanitize_input($value) {
    if (is_array($value)) {
        $cleaned = [];
        foreach ($value as $key => $item) {
            $cleaned[$key] = sanitize_input($item);
        }
        return $cleaned;
    }

    if ($value === null) {
        return '';
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $antiXss = security_get_anti_xss();
    $purifier = security_get_html_purifier();

    $cleaned = $antiXss->xss_clean($value);
    return $purifier->purify($cleaned);
}

function e($value): string {
    if ($value === null) {
        return '';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
