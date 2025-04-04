<?php

// Программное создание страниц
function create_protected_pages()
{
  // Глобально сохраняем массив, чтобы использовать в ACF-хуках
  $GLOBALS['protected_pages'] = [
    'Home Page' => ['slug' => 'home', 'page_type' => 'home_page'],
    // 'About Page' => ['slug' => 'about', 'page_type' => 'about_page'],
    // 'Contact Page' => ['slug' => 'contact', 'page_type' => 'contact_page'],
  ];

  foreach ($GLOBALS['protected_pages'] as $title => $data) {
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
        update_post_meta($page_id, '_protected_page', 'yes');
        update_post_meta($page_id, 'page_type', $data['page_type']);
      }
    }
  }
}
add_action('init', 'create_protected_pages');

// Запрет удаления защищённых страниц
function prevent_protected_page_deletion($post_id)
{
  if (get_post_meta($post_id, '_protected_page', true) === 'yes') {
    wp_die(__('This page is protected and cannot be deleted.'));
  }
}
add_action('before_delete_post', 'prevent_protected_page_deletion');

// Скрытие кнопки удаления в админке
function hide_delete_for_protected_pages($actions, $post)
{
  if ($post->post_type === 'page' && get_post_meta($post->ID, '_protected_page', true) === 'yes') {
    unset($actions['trash']);
  }
  return $actions;
}
add_filter('page_row_actions', 'hide_delete_for_protected_pages', 10, 2);

// Блокировка удаления через REST API
function prevent_rest_delete_protected_page($response, $post)
{
  if ($post->post_type === 'page' && get_post_meta($post->ID, '_protected_page', true) === 'yes') {
    return new WP_Error('protected_page', __('This page is protected and cannot be deleted.'), ['status' => 403]);
  }
  return $response;
}
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
  if ($request->get_method() === 'DELETE' && isset($request['id'])) {
    return prevent_rest_delete_protected_page(null, get_post($request['id']));
  }
  return $result;
}, 10, 3);


// ==========================
// ACF: Расширение правил отображения по кастомному полю "page_type"
// ==========================

if (function_exists('acf_add_local_field_group')) {
  // 1. Добавляем новое условие
  add_filter('acf/location/rule_types', function ($choices) {
    $choices['Custom fields']['custom_field_value'] = 'Custom Field Value';
    return $choices;
  });

  // 2. Автоматическое заполнение значений из $protected_pages
  add_filter('acf/location/rule_values/custom_field_value', function ($choices) {
    if (isset($GLOBALS['protected_pages']) && is_array($GLOBALS['protected_pages'])) {
      foreach ($GLOBALS['protected_pages'] as $page) {
        if (!empty($page['page_type'])) {
          $value = $page['page_type'];
          $choices[$value] = $value;
        }
      }
    }
    return $choices;
  });

  // 3. Логика сравнения
  add_filter('acf/location/rule_match/custom_field_value', function ($match, $rule, $options) {
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
