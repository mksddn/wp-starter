# Установка Wordpress через Docker + WP CLI
Стартер для разработки на WordPress локально и с командой.

## Запуск приложения
1. Создай и заполни **.env** файл на основе .env.example (*или запроси детали у старшего разработчика*)
> Убедись, что у тебя установлен [Docker](https://www.docker.com) `docker -v`
2. Запусти приложение `./app up`
3. [Импортируй дамп БД](#dbimport), если нужно `./app dbimport`

| username | password |
| -------- | -------- |
| dev      | root     |

### Команды приложения
- Запустить приложение `./app up`
- Остановить приложение `./app stop`
- Очистить проект (удалит все, кроме файлов темы) `./app clean`
- Создать нового админа `./app user-create`
- Экспорт базы данных (в корне репозитория создастся dbdump.sql) `./app dbexport`
- <a id="dbimport"></a>Импорт базы данных `./app dbimport`
> Для импорта размести в корне репозитория дамп БД (не архив) с расширением .sql

### Команды NPM
> Убедись, что у тебя установлен [Node.js](https://nodejs.org/en) `node --version` (рекомендую использовать [NVM](https://github.com/nvm-sh/nvm))
- Установить зависимости `./app npm-install`
- Отслеживать все файлы SASS/SCSS и компилировать их в css при их изменении `./app watch`
- Компилировать файлы SASS/SCSS в css `./app compile:css`

### Команды Composer
> Убедись, что у тебя установлен [Composer](https://getcomposer.org/) `composer --version`
- Установить зависимости `./app composer-install`
- Проверить все файлы PHP на наличие синтаксических ошибок `./app lint:php`
- Проверить все файлы PHP согласно [WP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) `./app lint:wpcs`

## Советы
- Устанавливаемые плагины можно указать в файле wp-settings/plugins.txt
- Панель phpMyAdmin доступна по адресу [http://localhost:8080](http://localhost:8080/)
- Не размещай важный код в **wp-config.php**, так как этот файл в каждой среде свой. Динамической является только директория **/wp-content/** с темой, плагинами и загрузками.
- Не забывай передавать дамп БД другому разработчику или актуализировать ее при деплое в staging/production `./app dbexport`
- Тема поддерживает acf.json
![acf-sync](https://github.com/mksddn/wp-starter/assets/22976310/da78f925-ca72-4124-87a9-1e58dee0f398)