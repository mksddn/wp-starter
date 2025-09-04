<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    // Проверяем mu-plugins и wp-theme
    $rectorConfig->paths([
        __DIR__ . '/../../wp-content/mu-plugins/',
        __DIR__ . '/../../wp-content/themes/',
    ]);

    // Исключаем vendor и node_modules
    $rectorConfig->skip([
        __DIR__ . '/../../vendor/',
        __DIR__ . '/../../node_modules/',
    ]);

    // Включаем базовые наборы правил
    $rectorConfig->phpVersion(80200); // PHP 8.2
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        // Исключаем CODING_STYLE, так как он конфликтует с WordPress Coding Standards
        // SetList::CODING_STYLE,
    ]);

    // Настройки для WordPress
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    
    // Исключаем WordPress-специфичные функции от рефакторинга
    $rectorConfig->skip([
        'wp_*',
        'add_action',
        'add_filter',
        'register_post_type',
        'register_taxonomy',
        'wp_enqueue_script',
        'wp_enqueue_style',
        'wp_localize_script',
        'wp_register_script',
        'wp_register_style',
        // Исключаем функции экранирования WordPress
        'esc_html',
        'esc_attr',
        'esc_url',
        'esc_textarea',
        'esc_js',
        'wp_kses',
        'wp_kses_post',
        'wp_kses_data',
        'sanitize_text_field',
        'sanitize_email',
        'sanitize_url',
        'sanitize_textarea_field',
        'wp_unslash',
        'wp_verify_nonce',
        'wp_create_nonce',
        'admin_url',
        'get_admin_url',
        'home_url',
        'site_url',
        'get_home_url',
        'get_site_url',
    ]);
}; 