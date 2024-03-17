<?php
add_filter('wpcf7_autop_or_not', '__return_false');
add_filter('wpcf7_form_elements', function ($content) {
  $content = preg_replace('/<(span).*?class="\s*(?:.*\s)?wpcf7-form-control-wrap(?:\s[^"]+)?\s*"[^\>]*>(.*)<\/\1>/i', '\2', $content);

  return $content;
});
add_filter('wpcf7_form_elements', function ($content) {
  $content = str_replace('<span', '<div', $content);
  $content = str_replace('</span', '</div', $content);
  return $content;
});
add_filter('wpcf7_form_elements', function ($content) {
  $dom = new DOMDocument();
  $dom->preserveWhiteSpace = false;
  $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

  $xpath = new DomXPath($dom);
  $spans = $xpath->query("//span[contains(@class, 'wpcf7-form-control-wrap')]");

  foreach ($spans as $span) :
    $children = $span->firstChild;
    $span->parentNode->replaceChild($children, $span);
  endforeach;

  return $dom->saveHTML();
});
