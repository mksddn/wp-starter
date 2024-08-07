<?
/*
 * "Хлебные крошки" для WordPress
 * версия: 2019.03.03
 * лицензия: MIT
*/
function breadcrumbs()
{
	echo '<div class="page-content__breadcrumb breadcrumb">';
	/* === ОПЦИИ === */
	$text['home']     = 'Home'; // текст ссылки "Главная"
	$text['category'] = '%s'; // текст для страницы рубрики
	$text['search']   = 'Search'; // текст для страницы с результатами поиска
	$text['tag']      = 'Tag'; // текст для страницы тега
	$text['author']   = 'Author articles %s'; // текст для страницы автора
	$text['404']      = 'Error 404'; // текст для страницы 404
	$text['page']     = 'Page %s'; // текст 'Страница N'
	$text['cpage']    = 'Comments page %s'; // текст 'Страница комментариев N'

	$wrap_before    = ''; // открывающий тег обертки
	$wrap_after     = ''; // закрывающий тег обертки
	$sep            = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M4.45508 9.95998L7.71508 6.69998C8.10008 6.31498 8.10008 5.68498 7.71508 5.29998L4.45508 2.03998" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
      </svg>'; // разделитель между "крошками"
	$before         = '<span class="breadcrumbs__item breadcrumbs__item--active">'; // тег перед текущей "крошкой"
	$after          = '</span>'; // тег после текущей "крошки"

	$show_on_home   = 0; // 1 - показывать "хлебные крошки" на главной странице, 0 - не показывать
	$show_home_link = 1; // 1 - показывать ссылку "Главная", 0 - не показывать
	$show_current   = 1; // 1 - показывать название текущей страницы, 0 - не показывать
	$show_last_sep  = 1; // 1 - показывать последний разделитель, когда название текущей страницы не отображается, 0 - не показывать
	/* === КОНЕЦ ОПЦИЙ === */

	global $post;
	$home_url       = home_url('/');
	$link          = '<a class="breadcrumbs__item link" itemprop="item" href="%1$s"><span itemprop="name">%2$s</span>';
	$link          .= '</a>';
	$parent_id      = ($post) ? $post->post_parent : '';
	$home_link      = sprintf($link, $home_url, $text['home'], 1);

	// Вырежем лишние теги от тайтла
	$title = get_the_title();
	if (is_home() || is_front_page()) {

		if ($show_on_home) echo $wrap_before . $home_link . $wrap_after;
	} else {

		$position = 0;

		echo $wrap_before;

		if ($show_home_link) {
			$position += 1;
			echo $home_link;
		}

		if (is_category()) {
			$parents = get_ancestors(get_query_var('cat'), 'category');
			foreach (array_reverse($parents) as $cat) {
				$position += 1;
				if ($position > 1) echo $sep;
				echo sprintf($link, get_category_link($cat), get_cat_name($cat), $position);
			}
			if (get_query_var('paged')) {
				$position += 1;
				$cat = get_query_var('cat');
				echo $sep . sprintf($link, get_category_link($cat), get_cat_name($cat), $position);
				echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
			} else {
				if ($show_current) {
					if ($position >= 1) echo $sep;
					echo $before . sprintf($text['category'], single_cat_title('', false)) . $after;
				} elseif ($show_last_sep) echo $sep;
			}
		} elseif (is_search()) {
			if (get_query_var('paged')) {
				$position += 1;
				if ($show_home_link) echo $sep;
				echo sprintf($link, $home_url . '?s=' . get_search_query(), sprintf($text['search'], get_search_query()), $position);
				echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
			} else {
				if ($show_current) {
					if ($position >= 1) echo $sep;
					echo $before . sprintf($text['search'], get_search_query()) . $after;
				} elseif ($show_last_sep) echo $sep;
			}
		} elseif (is_year()) {
			if ($show_home_link && $show_current) echo $sep;
			if ($show_current) echo $before . get_the_time('Y') . $after;
			elseif ($show_home_link && $show_last_sep) echo $sep;
		} elseif (is_month()) {
			if ($show_home_link) echo $sep;
			$position += 1;
			echo sprintf($link, get_year_link(get_the_time('Y')), get_the_time('Y'), $position);
			if ($show_current) echo $sep . $before . get_the_time('F') . $after;
			elseif ($show_last_sep) echo $sep;
		} elseif (is_day()) {
			if ($show_home_link) echo $sep;
			$position += 1;
			echo sprintf($link, get_year_link(get_the_time('Y')), get_the_time('Y'), $position) . $sep;
			$position += 1;
			echo sprintf($link, get_month_link(get_the_time('Y'), get_the_time('m')), get_the_time('F'), $position);
			if ($show_current) echo $sep . $before . get_the_time('d') . $after;
			elseif ($show_last_sep) echo $sep;
		} elseif (is_single() && !is_attachment()) {
			if (get_post_type() != 'post') {
				$position += 1;
				$post_type = get_post_type_object(get_post_type());
				if ($position > 1) echo $sep;
				echo sprintf($link, get_post_type_archive_link($post_type->name), $post_type->labels->name, $position);
				if ($show_current) echo $sep . $before . $title . $after;
				elseif ($show_last_sep) echo $sep;
			} else {
				$cat = get_the_category();
				$catID = $cat[0]->cat_ID;
				$parents = get_ancestors($catID, 'category');
				$parents = array_reverse($parents);
				$parents[] = $catID;
				foreach ($parents as $cat) {
					$position += 1;
					if ($position > 1) echo $sep;
					echo sprintf($link, get_category_link($cat), get_cat_name($cat), $position);
				}
				if (get_query_var('cpage')) {
					$position += 1;
					echo $sep . sprintf($link, get_permalink(), $title, $position);
					echo $sep . $before . sprintf($text['cpage'], get_query_var('cpage')) . $after;
				} else {
					if ($show_current) echo $sep . $before . $title . $after;
					elseif ($show_last_sep) echo $sep;
				}
			}
		} elseif (is_post_type_archive()) {
			$post_type = get_post_type_object(get_post_type());
			if (get_query_var('paged')) {
				$position += 1;
				if ($position > 1) echo $sep;
				echo sprintf($link, get_post_type_archive_link($post_type->name), $post_type->label, $position);
				echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
			} else {
				if ($show_home_link && $show_current) echo $sep;
				if ($show_current) echo $before . $post_type->label . $after;
				elseif ($show_home_link && $show_last_sep) echo $sep;
			}
		} elseif (is_attachment()) {
			$parent = get_post($parent_id);
			$cat = get_the_category($parent->ID);
			$catID = $cat[0]->cat_ID;
			$parents = get_ancestors($catID, 'category');
			$parents = array_reverse($parents);
			$parents[] = $catID;
			foreach ($parents as $cat) {
				$position += 1;
				if ($position > 1) echo $sep;
				echo sprintf($link, get_category_link($cat), get_cat_name($cat), $position);
			}
			$position += 1;
			echo $sep . sprintf($link, get_permalink($parent), $parent->post_title, $position);
			if ($show_current) echo $sep . $before . $title . $after;
			elseif ($show_last_sep) echo $sep;
		} elseif (is_page() && !$parent_id) {
			if ($show_home_link && $show_current) echo $sep;
			if ($show_current) echo $before . $title . $after;
			elseif ($show_home_link && $show_last_sep) echo $sep;
		} elseif (is_page() && $parent_id) {
			$parents = get_post_ancestors(get_the_ID());
			foreach (array_reverse($parents) as $pageID) {
				$position += 1;
				if ($position > 1) echo $sep;
				echo sprintf($link, get_page_link($pageID), get_the_title($pageID), $position);
			}
			if ($show_current) echo $sep . $before . $title . $after;
			elseif ($show_last_sep) echo $sep;
		} elseif (is_tag()) {
			if (get_query_var('paged')) {
				$position += 1;
				$tagID = get_query_var('tag_id');
				echo $sep . sprintf($link, get_tag_link($tagID), single_tag_title('', false), $position);
				echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
			} else {
				if ($show_home_link && $show_current) echo $sep;
				if ($show_current) echo $before . sprintf($text['tag'], single_tag_title('', false)) . $after;
				elseif ($show_home_link && $show_last_sep) echo $sep;
			}
		} elseif (is_author()) {
			$author = get_userdata(get_query_var('author'));
			if (get_query_var('paged')) {
				$position += 1;
				if ($show_home_link && $show_current) echo $sep;
				echo '<a class="breadcrumbs__item link" href="/our-team/" itemprop="item">Our team</a>' . $sep;
				echo sprintf($link, get_author_posts_url($author->ID), sprintf($author->display_name), $position) . $sep;
				echo $before . sprintf($text['page'], get_query_var('paged')) . $after;
			} else {
				if ($show_home_link && $show_current) echo $sep;
				echo '<a class="breadcrumbs__item link" href="/our-team/" itemprop="item">Our team</a>' . $sep;
				if ($show_current) echo $before . $author->display_name . $after;
				elseif ($show_home_link && $show_last_sep) echo $sep;
			}
		} elseif (is_404()) {
			if ($show_home_link && $show_current) echo $sep;
			if ($show_current) echo $before . $text['404'] . $after;
			elseif ($show_last_sep) echo $sep;
		} elseif (has_post_format() && !is_singular()) {
			if ($show_home_link && $show_current) echo $sep;
			echo get_post_format_string(get_post_format());
		}

		echo $wrap_after;
	}
	echo '</div>';
} // end of breadcrumbs()
