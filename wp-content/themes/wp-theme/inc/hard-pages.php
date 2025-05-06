<?php

// Подключаем файл с конфигурацией страниц
require_once __DIR__ . '/hard-pages-list.php';

// Программное создание страниц
function create_hard_pages()
{
  if (!isset($GLOBALS['hard_pages']) || !is_array($GLOBALS['hard_pages'])) {
    return;
  }

  foreach ($GLOBALS['hard_pages'] as $title => $data) {
    $slug = $data['slug'];

    // Проверяем, существует ли уже страница с таким слагом
    $existing_page = get_page_by_path($slug, OBJECT, 'page');

    if (!$existing_page) {
      // Создаем новую страницу
      $page_id = wp_insert_post([
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_author'  => 1,
      ]);

      // Добавляем мета-данные
      if ($page_id) {
        update_post_meta($page_id, '_hard_page', 'yes');
        update_post_meta($page_id, 'page_type', $data['page_type']);
      }
    } else {
      // Обновляем мета-данные для существующей страницы
      $existing_page_id = $existing_page->ID;
      update_post_meta($existing_page_id, '_hard_page', 'yes');
      update_post_meta($existing_page_id, 'page_type', $data['page_type']);
    }
  }
}
add_action('init', 'create_hard_pages');

// // Запрет удаления созданных страниц
// function prevent_hard_page_deletion($post_id)
// {
//   if (get_post_meta($post_id, '_hard_page', true) === 'yes') {
//     wp_die(__('This page is hard and cannot be deleted.'));
//   }
// }
// add_action('before_delete_post', 'prevent_hard_page_deletion');

// // Скрытие кнопки удаления в админке
// function hide_delete_for_hard_pages($actions, $post)
// {
//   if ($post->post_type === 'page' && get_post_meta($post->ID, '_hard_page', true) === 'yes') {
//     unset($actions['trash']);
//   }
//   return $actions;
// }
// add_filter('page_row_actions', 'hide_delete_for_hard_pages', 10, 2);

// // Блокировка удаления через REST API
// function prevent_rest_delete_hard_page($response, $post)
// {
//   if ($post->post_type === 'page' && get_post_meta($post->ID, '_hard_page', true) === 'yes') {
//     return new WP_Error('hard_page', __('This page is hard and cannot be deleted.'), ['status' => 403]);
//   }
//   return $response;
// }
// add_filter('rest_pre_dispatch', function ($result, $server, $request) {
//   if ($request->get_method() === 'DELETE' && isset($request['id'])) {
//     return prevent_rest_delete_hard_page(null, get_post($request['id']));
//   }
//   return $result;
// }, 10, 3);


// ==========================
// ACF: Расширение правил отображения по кастомному полю "page_type"
// ==========================

if (function_exists('acf_add_local_field_group')) {
  // 1. Добавляем новое условие
  add_filter('acf/location/rule_types', function ($choices) {
    $choices['Hard Pages']['hard_page'] = 'Hard Page Type';
    return $choices;
  });

  // 2. Автоматическое заполнение значений из $hard_pages
  add_filter('acf/location/rule_values/hard_page', function ($choices) {
    if (isset($GLOBALS['hard_pages']) && is_array($GLOBALS['hard_pages'])) {
      foreach ($GLOBALS['hard_pages'] as $page) {
        if (!empty($page['page_type'])) {
          $value = $page['page_type'];
          $choices[$value] = $value;
        }
      }
    }
    return $choices;
  });

  // 3. Логика сравнения
  add_filter('acf/location/rule_match/hard_page', function ($match, $rule, $options) {
    $post_id = $options['post_id'];

    if (!$post_id || get_post_type($post_id) !== 'page') {
      return false;
    }

    $field_value = get_post_meta($post_id, 'page_type', true);

    if ($rule['operator'] === '==') {
      $match = ($field_value === $rule['value']);
    } elseif ($rule['operator'] === '!=') {
      $match = ($field_value !== $rule['value']);
    }

    return $match;
  }, 10, 3);
}
