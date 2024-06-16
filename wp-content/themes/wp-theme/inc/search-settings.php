<?php

/**
 * Показываем только определенные типы записей в результатах поиска
 */
// function searchfilter($query)
// {
//   if ($query->is_search && !is_admin()) {
//     $query->set('post_type', array('post', 'page', 'product'));
//   }
//   return $query;
// }
// add_filter('pre_get_posts', 'searchfilter');


/**
 * Остановить запрос в случае пустого поиска 
 */
add_filter('posts_search', function ($search, \WP_Query $q) {
  if (!is_admin() && empty($search) && $q->is_search() && $q->is_main_query())
    $search .= " AND 0=1 ";
  return $search;
}, 10, 2);
