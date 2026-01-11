# Установка Wordpress через Docker + WP CLI
Стартер для разработки на WordPress локально и с командой.

## Запуск приложения
1. Убедись, что у тебя установлен [Docker](https://www.docker.com) `docker -v`
2. Запусти приложение `./app up`
3. [Импортируй дамп БД](#dbimport), если нужно `./app db-import`

| username | password |
| -------- | -------- |
| dev      | root     |

### Команды приложения
- Запустить приложение `./app up`
- Остановить приложение `./app stop`
- Очистить проект (удалит все, кроме файлов темы) `./app clean`
- Создать нового админа `./app user-create`
- Экспорт базы данных (в корне репозитория создастся dbdump.sql) `./app db-export`
- <a id="dbimport"></a>Импорт базы данных `./app db-import`
> Для импорта размести в корне репозитория дамп БД (не архив) с расширением .sql

### Проверка кода (линтеры)
- Установить зависимости в корне проекта `composer install && npm install`
- Проверить PHP + WP `composer lint`
- Автофикс ошибок PHP `composer fix`
- Проверить HTML `npm run lint:htmlvalidate`

## Советы
- **Веди работу только в дочерней теме**, так как после удаленного обновления родительской темы все изменения в ней сбросятся.
- Не размещай важный код в **wp-config.php**, так как этот файл в каждой среде свой. Динамической является только директория **/wp-content/** с темой.
- Панель phpMyAdmin доступна по адресу [http://localhost:8080](http://localhost:8080/)
- Для Windows используй [WSL2](https://docs.microsoft.com/en-us/windows/wsl/install) или [Git Bash](https://git-scm.com/downloads)
- Перед запуском команд NPM, убедись, что у тебя установлен [Node.js](https://nodejs.org/en) `node --version` (рекомендую использовать [NVM](https://github.com/nvm-sh/nvm))
- Перед запуском линтинга, убедись, что у тебя установлен [Composer](https://getcomposer.org/) `composer --version` и [Node.js](https://nodejs.org/en) `node --version`