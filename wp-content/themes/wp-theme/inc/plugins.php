<?php

// Записываем текстовый домен плагина при его активации
function log_plugin_activation($plugin)
{
  // Получаем полные данные о плагине
  $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

  // Извлекаем текстовый домен плагина
  $plugin_text_domain = $plugin_data['TextDomain'];

  // Указываем путь к файлу plugins.txt в директории активной темы
  $log_file = get_stylesheet_directory() . '/plugins.txt';

  // Проверяем, существует ли файл и читаем его содержимое
  if (file_exists($log_file)) {
    $lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Если текстовый домен уже существует в файле, не добавляем его
    if (in_array($plugin_text_domain, $lines)) {
      return;
    }
  }

  // Записываем текстовый домен плагина в файл, если он еще не записан
  @file_put_contents($log_file, $plugin_text_domain . "\n", FILE_APPEND | LOCK_EX);
}
add_action('activated_plugin', 'log_plugin_activation', 10, 1);

// Удаляем текстовый домен плагина при его деактивации
function log_plugin_deactivation($plugin)
{
  // Получаем полные данные о плагине
  $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

  // Извлекаем текстовый домен плагина
  $plugin_text_domain = $plugin_data['TextDomain'];

  // Указываем путь к файлу plugins.txt в директории активной темы
  $log_file = get_stylesheet_directory() . '/plugins.txt';

  // Проверяем, существует ли файл
  if (file_exists($log_file)) {
    // Читаем содержимое файла в массив строк
    $lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Ищем и удаляем строку с текстовым доменом плагина
    if (($key = array_search($plugin_text_domain, $lines)) !== false) {
      unset($lines[$key]);

      // Перезаписываем файл без удаленной строки
      @file_put_contents($log_file, implode("\n", $lines) . "\n", LOCK_EX);
    }
  }
}
add_action('deactivated_plugin', 'log_plugin_deactivation', 10, 1);
