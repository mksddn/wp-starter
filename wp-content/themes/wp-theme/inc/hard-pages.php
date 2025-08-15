<?php
/**
 * Управление жёстко заданными страницами (hard pages) для темы.
 *
 * @package wp-theme
 */

// Подключаем файл с конфигурацией страниц.
require_once __DIR__ . '/hard-pages-list.php';

// Глобальная переменная для отслеживания недавно удаленных страниц.
$GLOBALS['recently_deleted_hard_pages'] = [];

// Назначаем page_type для всех страниц.
foreach ($GLOBALS['hard_pages'] as $key => $hard_page) {
    $GLOBALS['hard_pages'][ $key ]['page_type'] = $hard_page['slug'];
}


/**
 * Добавляет страницу в hard-pages-list.php.
 */
function add_page_to_hard_pages( $page_id, string $page_title, string $page_slug ): void {
    static $added_pages = [];

    // Проверяем, не была ли эта страница уже добавлена в текущем запросе.
    $page_key = $page_title . '|' . $page_slug;
    if (in_array( $page_key, $added_pages )) {
        return;
    }

    // Проверяем, что это новая страница (не hard-page).
    if ('yes' === get_post_meta( $page_id, '_hard_page', true )) {
        return;
    }

    // Пропускаем страницы с суффиксами __trashed.
    if (str_contains( $page_slug, '__trashed' )) {
        return;
    }

    // Проверяем, есть ли уже страница с таким названием в hard-pages-list.php.
    $file_path = __DIR__ . '/hard-pages-list.php';

    // Проверяем, что файл существует.
    if (! file_exists( $file_path )) {
        return;
    }

    // Проверяем права на запись.
    if (! is_writable( $file_path )) {
        return;
    }

    $content = file_get_contents( $file_path );

    if (false === $content) {
        return;
    }

    // Проверяем, есть ли уже такая страница в списке.
    if (str_contains( $content, sprintf("'slug' => '%s'", $page_slug) )) {
        return;
    }

    // Экранируем специальные символы в названии страницы для поиска.
    $escaped_title = str_replace( "'", "\\'", $page_title );
    if (str_contains( $content, sprintf("'%s'", $escaped_title) )) {
        return;
    }

    // Находим позицию для вставки в массив $GLOBALS['hard_pages'].
    // Ищем строку с закрывающей скобкой массива.
    $pattern = '/\$GLOBALS\[\'hard_pages\'\]\s*=\s*\[(.*?)\];/s';
    if (preg_match( $pattern, $content, $matches )) {
        $array_content = $matches[1];

        // Формируем новую запись.
        $new_entry = "    '{$escaped_title}' => ['slug' => '{$page_slug}'],\n";

        // Добавляем новую запись в конец массива.
        $new_array_content = rtrim( $array_content ) . "\n" . $new_entry;

        // Заменяем содержимое массива.
        $new_content = preg_replace( $pattern, "\$GLOBALS['hard_pages'] = [\n{$new_array_content}];", $content );

        // Записываем обновленный файл.
        if (file_put_contents( $file_path, $new_content )) {
            // Добавляем страницу в список уже добавленных.
            $added_pages[] = $page_key;

            // Обновляем глобальную переменную.
            $GLOBALS['hard_pages'][ $page_title ] = [
                'slug'      => $page_slug,
                'page_type' => $page_slug,
            ];

            // Принудительно обновляем глобальную переменную из файла.
            $updated_content = file_get_contents( $file_path );
            if (false !== $updated_content) {
                // Находим массив в обновленном файле.
                $pattern = '/\$GLOBALS\[\'hard_pages\'\]\s*=\s*\[(.*?)\];/s';
                if (preg_match( $pattern, $updated_content, $matches )) {
                    // Безопасно парсим массив.
                    $hard_pages = [];
                    $lines      = explode( "\n", $matches[1] );
                    foreach ($lines as $line) {
                        $line = trim( $line );
                        if (preg_match( "/'([^']+)'\\s*=>\\s*\\['slug'\\s*=>\\s*'([^']+)'\\]/", $line, $matches )) {
                            $title                = $matches[1];
                            $slug                 = $matches[2];
                            $hard_pages[ $title ] = [
                                'slug'      => $slug,
                                'page_type' => $slug,
                            ];
                        }
                    }

                    // Обновляем глобальную переменную.
                    $GLOBALS['hard_pages'] = $hard_pages;
                }
            }

            // Добавляем мета-данные к странице.
            update_post_meta( $page_id, '_hard_page', 'yes' );
            update_post_meta( $page_id, 'page_type', $page_slug );

            normalize_hard_pages_file();
        }
    }
}


/**
 * Удаляет страницу из hard-pages-list.php.
 *
 * @param string $page_title Название страницы.
 * @param string $page_slug Слаг страницы.
 */
function remove_page_from_hard_pages( string $page_title, $page_slug ): void {
    static $removed_pages = [];

    // Проверяем, не была ли эта страница уже удалена в текущем запросе.
    $page_key = $page_title . '|' . $page_slug;
    if (in_array( $page_key, $removed_pages )) {
        return;
    }

    // Очищаем slug от суффиксов __trashed.
    $original_slug = preg_replace( '/__trashed-\d+$/', '', $page_slug );
    if ($original_slug !== $page_slug) {
        $page_slug = $original_slug;
    }

    // Читаем текущий файл hard-pages-list.php.
    $file_path = __DIR__ . '/hard-pages-list.php';
    $content   = file_get_contents( $file_path );

    // Экранируем специальные символы в названии страницы для поиска.
    $escaped_title = str_replace( "'", "\\'", $page_title );

    // Проверяем, есть ли страница в файле перед попыткой удаления.
    if (!str_contains( $content, sprintf("'%s'", $escaped_title) )) {
        return;
    }

    // Ищем строку с этой страницей (универсальный поиск, без \n).
    $search_pattern = "/\s*'" . preg_quote( $escaped_title, '/' ) . "'\s*=>\s*\['slug'\s*=>\s*'[^']*'\],?/u";

    if (preg_match( $search_pattern, $content, $matches )) {
        // Удаляем строку с этой страницей.
        $new_content = preg_replace( $search_pattern, '', $content );

        // Чистим двойные запятые и лишние пробелы.
        $new_content = preg_replace( '/,\s*,/', ',', (string) $new_content );
        $new_content = preg_replace( '/\[\s*,/', '[', (string) $new_content );
        $new_content = preg_replace( '/,\s*\]/', ']', (string) $new_content );

        // Записываем обновленный файл.
        if (file_put_contents( $file_path, $new_content )) {
            // Добавляем страницу в список уже удаленных.
            $removed_pages[] = $page_key;

            // Добавляем страницу в глобальный список недавно удаленных для предотвращения воссоздания.
            $GLOBALS['recently_deleted_hard_pages'][] = $page_key;

            // Удаляем из глобальной переменной.
            if (isset( $GLOBALS['hard_pages'][ $page_title ] )) {
                unset( $GLOBALS['hard_pages'][ $page_title ] );
            }

            // Принудительно обновляем глобальную переменную из файла.
            $updated_content = file_get_contents( $file_path );
            if (false !== $updated_content) {
                // Находим массив в обновленном файле.
                $pattern = '/\$GLOBALS\[\'hard_pages\'\]\s*=\s*\[(.*?)\];/s';
                if (preg_match( $pattern, $updated_content, $matches )) {
                    // Безопасно парсим массив.
                    $hard_pages = [];
                    $lines      = explode( "\n", $matches[1] );
                    foreach ($lines as $line) {
                        $line = trim( $line );
                        if (preg_match( "/'([^']+)'\\s*=>\\s*\\['slug'\\s*=>\\s*'([^']+)'\\]/", $line, $matches )) {
                            $title                = $matches[1];
                            $slug                 = $matches[2];
                            $hard_pages[ $title ] = [
                                'slug'      => $slug,
                                'page_type' => $slug,
                            ];
                        }
                    }

                    // Обновляем глобальную переменную.
                    $GLOBALS['hard_pages'] = $hard_pages;
                }
            }

            normalize_hard_pages_file();
        }
    }
}


/**
 * Обновляет информацию о странице в hard-pages-list.php
 *
 * @param int    $post_id ID страницы
 * @param string $old_title Старый заголовок
 * @param string $old_slug Старый слаг
 * @param string $new_title Новый заголовок
 * @param string $new_slug Новый слаг
 */
function update_page_in_hard_pages( $post_id, $old_title, $old_slug, $new_title, $new_slug ): void {
    // Проверяем, что это hard-page
    if (get_post_meta( $post_id, '_hard_page', true ) !== 'yes') {
        return;
    }

    // Читаем текущий файл hard-pages-list.php
    $file_path = __DIR__ . '/hard-pages-list.php';
    $content   = file_get_contents( $file_path );

    // Экранируем специальные символы в старом названии для поиска
    $escaped_old_title = str_replace( "'", "\\'", $old_title );

    // Ищем старую запись
    $old_pattern = "/\\s*'" . preg_quote( $escaped_old_title, '/' ) . "'\\s*=>\\s*\\['slug'\\s*=>\\s*'" . preg_quote( $old_slug, '/' ) . "'\\],?\\s*\\n?/";

    if (preg_match( $old_pattern, $content )) {
        // Экранируем специальные символы в новом названии
        $escaped_new_title = str_replace( "'", "\\'", $new_title );

        // Формируем новую запись
        $new_entry = "    '{$escaped_new_title}' => ['slug' => '{$new_slug}'],\n";

        // Заменяем старую запись на новую
        $new_content = preg_replace( $old_pattern, $new_entry, $content );

        // Записываем обновленный файл
        if (file_put_contents( $file_path, $new_content )) {
            // Обновляем глобальную переменную
            if (isset( $GLOBALS['hard_pages'][ $old_title ] )) {
                $page_data = $GLOBALS['hard_pages'][ $old_title ];
                unset( $GLOBALS['hard_pages'][ $old_title ] );
                $GLOBALS['hard_pages'][ $new_title ] = [
                    'slug'      => $new_slug,
                    'page_type' => $new_slug,
                ];
            }

            // Обновляем мета-данные
            update_post_meta( $post_id, 'page_type', $new_slug );
        }
    }
}


/**
 * Хук: создание новой страницы
 *
 * @param int     $post_id ID страницы
 * @param WP_Post $post Объект страницы
 * @param bool    $update true, если обновление
 */
function on_page_created( $post_id, $post, $update ): void {
    if ($update) {
        return;
    }

    // Проверяем, что это страница
    if ('page' !== $post->post_type) {
        return;
    }

    // Проверяем, что страница опубликована
    if ('publish' !== $post->post_status) {
        return;
    }

    // Добавляем страницу в hard-pages
    add_page_to_hard_pages( $post_id, $post->post_title, $post->post_name );
}


add_action( 'wp_insert_post', 'on_page_created', 5, 3 );


/**
 * Хук: смена статуса страницы
 *
 * @param string  $new_status Новый статус
 * @param string  $old_status Старый статус
 * @param WP_Post $post Объект страницы
 */
function on_page_status_changed( $new_status, $old_status, $post ): void {
    if ('publish' === $new_status && 'publish' !== $old_status && 'page' === $post->post_type) {
        add_page_to_hard_pages( $post->ID, $post->post_title, $post->post_name );
    }

    // Добавляем отслеживание перемещения в корзину
    if ('trash' === $new_status && 'trash' !== $old_status && 'page' === $post->post_type && 'yes' === get_post_meta( $post->ID, '_hard_page', true )) {
        remove_page_from_hard_pages( $post->post_title, $post->post_name );
        // Автоматически окончательно удаляем страницу из корзины через 5 секунд
        wp_schedule_single_event( time() + 5, 'force_delete_hard_page', [ $post->ID ] );
    }
}


add_action( 'transition_post_status', 'on_page_status_changed', 5, 3 );


/**
 * Хук: удаление страницы
 *
 * @param int $post_id ID страницы
 */
function on_page_deleted( $post_id ): void {
    $post = get_post( $post_id );

    if (! $post) {
        return;
    }

    if ('page' !== $post->post_type) {
        return;
    }

    // Проверяем, была ли это hard-page
    if ('yes' === get_post_meta( $post_id, '_hard_page', true )) {
        remove_page_from_hard_pages( $post->post_title, $post->post_name );
    }
}


// Используем несколько хуков для надежности
add_action( 'before_delete_post', 'on_page_deleted' );
add_action( 'wp_trash_post', 'on_page_deleted' );
add_action( 'delete_post', 'on_page_deleted' );

// Дополнительный хук для отслеживания через REST API
add_action( 'rest_delete_post', 'on_page_deleted' );


/**
 * Хук: перемещение страницы в корзину
 *
 * @param int $post_id ID страницы
 */
function on_page_trashed( $post_id ): void {
    $post = get_post( $post_id );

    if (! $post) {
        return;
    }

    if ('page' !== $post->post_type) {
        return;
    }

    // Проверяем, была ли это hard-page
    if ('yes' === get_post_meta( $post_id, '_hard_page', true )) {
        // Очищаем slug от суффиксов __trashed для поиска в файле
        $original_slug = preg_replace( '/__trashed-\d+$/', '', (string) $post->post_name );

        remove_page_from_hard_pages( $post->post_title, $original_slug );

        // Автоматически окончательно удаляем страницу из корзины через 5 секунд
        wp_schedule_single_event( time() + 5, 'force_delete_hard_page', [ $post_id ] );
    }
}


add_action( 'wp_trash_post', 'on_page_trashed' );


/**
 * Принудительное удаление hard-page из корзины
 *
 * @param int $post_id ID страницы
 */
function force_delete_hard_page( $post_id ): void {
    $post = get_post( $post_id );

    if (! $post) {
        return;
    }

    if ('page' !== $post->post_type) {
        return;
    }

    if ('trash' === $post->post_status) {
        // Окончательно удаляем страницу
        wp_delete_post( $post_id, true ); // true = удалить окончательно
    }
}


add_action( 'force_delete_hard_page', 'force_delete_hard_page' );


/**
 * Хук: обновление страницы
 *
 * @param int     $post_id ID страницы
 * @param WP_Post $post_after После изменений
 * @param WP_Post $post_before До изменений
 */
function on_page_updated( $post_id, $post_after, $post_before ): void {
    if ('page' !== $post_after->post_type) {
        return;
    }

    // Проверяем, изменились ли заголовок или слаг
    if ($post_after->post_title !== $post_before->post_title ||
        $post_after->post_name !== $post_before->post_name ) {
        update_page_in_hard_pages(
            $post_id,
            $post_before->post_title,
            $post_before->post_name,
            $post_after->post_title,
            $post_after->post_name
        );
    }
}


add_action( 'post_updated', 'on_page_updated', 10, 3 );


/**
 * Программное создание жёстко заданных страниц
 */
function create_hard_pages(): void {
    // Принудительно обновляем глобальную переменную из файла перед созданием страниц
    $file_path = __DIR__ . '/hard-pages-list.php';
    if (file_exists( $file_path )) {
        $content = file_get_contents( $file_path );
        if (false !== $content) {
            // Находим массив в файле
            $pattern = '/\$GLOBALS\[\'hard_pages\'\]\s*=\s*\[(.*?)\];/s';
            if (preg_match( $pattern, $content, $matches )) {
                // Безопасно парсим массив
                $hard_pages = [];
                $lines      = explode( "\n", $matches[1] );
                foreach ($lines as $line) {
                    $line = trim( $line );
                    if (preg_match( "/'([^']+)'\\s*=>\\s*\\['slug'\\s*=>\\s*'([^']+)'\\]/", $line, $matches )) {
                        $title                = $matches[1];
                        $slug                 = $matches[2];
                        $hard_pages[ $title ] = [
                            'slug'      => $slug,
                            'page_type' => $slug,
                        ];
                    }
                }

                // Обновляем глобальную переменную
                $GLOBALS['hard_pages'] = $hard_pages;
            }
        }
    }

    if (! isset( $GLOBALS['hard_pages'] ) || ! is_array( $GLOBALS['hard_pages'] )) {
        return;
    }

    foreach ($GLOBALS['hard_pages'] as $title => $data) {
        $slug = $data['slug'];

        // Проверяем, не была ли эта страница недавно удалена
        $page_key = $title . '|' . $slug;
        if (isset( $GLOBALS['recently_deleted_hard_pages'] ) && in_array( $page_key, $GLOBALS['recently_deleted_hard_pages'] )) {
            continue;
        }

        // Проверяем, существует ли уже страница с таким слагом
        $existing_page = get_page_by_path( $slug, OBJECT, 'page' );

        if (! $existing_page) {
            // Создаем новую страницу
            $page_id = wp_insert_post(
                [
                    'post_title'  => $title,
                    'post_name'   => $slug,
                    'post_status' => 'publish',
                    'post_type'   => 'page',
                    'post_author' => 1,
                ]
            );

            // Добавляем мета-данные
            if ($page_id) {
                update_post_meta( $page_id, '_hard_page', 'yes' );
                update_post_meta( $page_id, 'page_type', $data['page_type'] );
            }
        } else {
            // Обновляем мета-данные для существующей страницы
            $existing_page_id = $existing_page->ID;
            update_post_meta( $existing_page_id, '_hard_page', 'yes' );
            update_post_meta( $existing_page_id, 'page_type', $data['page_type'] );
        }
    }
}


add_action( 'wp_loaded', 'create_hard_pages', 999 );

// ==========================
// ACF: Расширение правил отображения по кастомному полю "page_type"
// ==========================

if (function_exists( 'acf_add_local_field_group' )) {
    // 1. Добавляем новое условие
    add_filter(
        'acf/location/rule_types',
        function ( array $choices ) {
            $choices['Hard Pages']['hard_page'] = 'Hard Page Type';
            return $choices;
        }
    );

    // 2. Автоматическое заполнение значений из $hard_pages
    add_filter(
        'acf/location/rule_values/hard_page',
        function ( array $choices ): array {
            if (isset( $GLOBALS['hard_pages'] ) && is_array( $GLOBALS['hard_pages'] )) {
                foreach ($GLOBALS['hard_pages'] as $page) {
                    if (! empty( $page['page_type'] )) {
                        $value             = $page['page_type'];
                        $choices[ $value ] = $value;
                    }
                }
            }

            return $choices;
        }
    );

    // 3. Логика сравнения
    add_filter(
        'acf/location/rule_match/hard_page',
        function ( $match, array $rule, array $options ) {
            $post_id = $options['post_id'];

            if (! $post_id || 'page' !== get_post_type( $post_id )) {
                return false;
            }

            $field_value = get_post_meta( $post_id, 'page_type', true );

            if ('==' === $rule['operator']) {
                $match = ( $field_value === $rule['value'] );
            } elseif ('!=' === $rule['operator']) {
                $match = ( $field_value !== $rule['value'] );
            }

            return $match;
        },
        10,
        3
    );
}


/**
 * Нормализует массив hard_pages в файле
 */
function normalize_hard_pages_file(): void {
    $file_path = __DIR__ . '/hard-pages-list.php';
    $content   = file_get_contents( $file_path );
    if (false === $content) {
        return;
    }

    // Находим массив $GLOBALS['hard_pages']
    $pattern = '/\$GLOBALS\[\'hard_pages\'\]\s*=\s*\[(.*?)\];/s';
    if (preg_match( $pattern, $content, $matches )) {
        $array_content = $matches[1];
        // Удаляем пустые строки и пробелы
        $lines   = array_filter( array_map( 'trim', explode( "\n", $array_content ) ) );
        $entries = [];
        foreach ($lines as $line) {
            if (preg_match( "/'[^']+'\s*=>\s*\['slug'\s*=>\s*'[^']+'\],?\s*$/", $line )) {
                $entries[] = rtrim( $line, ',' );
            }
        }

        // Формируем новый массив с запятыми
        $new_array_content = '';
        $count             = count( $entries );
        foreach ($entries as $i => $entry) {
            $new_array_content .= '    ' . $entry;
            if ($i < $count - 1) {
                $new_array_content .= ",\n";
            } else {
                $new_array_content .= "\n";
            }
        }

        $new_content = preg_replace( $pattern, "\$GLOBALS['hard_pages'] = [\n{$new_array_content}];", $content );
        file_put_contents( $file_path, $new_content );
    }
}


/**
 * Получает и очищает список post_id из $_GET по ключу (по умолчанию 'ids').
 *
 * @param string $key Ключ в массиве $_GET
 * @return int[] Массив целых post_id
 */
function get_sanitized_post_ids_from_get( $key = 'ids' ): array {
    if (!isset($_GET[$key])) {
        return [];
    }

    $raw_ids = wp_unslash($_GET[$key]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $ids = array_map('sanitize_text_field', explode(',', $raw_ids));
    $ids = array_map('intval', $ids);
    return array_filter($ids, fn($id): bool => $id > 0);
}


// phpcs:ignore WordPress.Security.NonceVerification.Missing
function on_admin_all_actions_early(): void {
    // Минимальная проверка nonce, если он есть в запросе
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if (isset($_GET['_wpnonce']) && !wp_verify_nonce(wp_unslash($_GET['_wpnonce']))) {
        return;
    }

    // Проверяем GET параметры для действий с постами
    if (isset( $_GET['trashed'] ) && isset( $_GET['ids'] )) {
        $post_ids = get_sanitized_post_ids_from_get( 'ids' );
        foreach ($post_ids as $post_id) {
            // Получаем информацию о посте
            $post = get_post( $post_id );
            // Проверяем, была ли это hard-page
            if ($post && 'page' === $post->post_type && 'yes' === get_post_meta( $post_id, '_hard_page', true )) {
                // Очищаем slug от суффиксов __trashed для поиска в файле
                $original_slug = preg_replace( '/__trashed-\d+$/', '', (string) $post->post_name );
                remove_page_from_hard_pages( $post->post_title, $original_slug );
                // Автоматически окончательно удаляем страницу из корзины через 5 секунд
                wp_schedule_single_event( time() + 5, 'force_delete_hard_page', [ $post_id ] );
            }
        }
    }

    // Проверяем удаление окончательно
    if (isset( $_GET['deleted'] ) && isset( $_GET['ids'] )) {
        $post_ids = get_sanitized_post_ids_from_get( 'ids' );
        foreach ($post_ids as $post_id) {
            // Получаем информацию о посте из базы данных (если еще доступна)
            global $wpdb;
            $post = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %s WHERE ID = ' . $wpdb->posts, $post_id ) );

            if ($post && 'page' === $post->post_type) {
                // Проверяем, была ли это hard-page
                $is_hard_page = $wpdb->get_var( $wpdb->prepare( sprintf("SELECT meta_value FROM %s WHERE post_id = %%d AND meta_key = '_hard_page'", $wpdb->postmeta), $post_id ) );

                if ('yes' === $is_hard_page) {
                    remove_page_from_hard_pages( $post->post_title, $post->post_name );
                }
            }
        }
    }
}


add_action( 'admin_init', 'on_admin_all_actions_early', 1 );
