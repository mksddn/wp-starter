# Установка Wordpress через Docker + WP CLI
Стартер для разработки на WordPress локально и с командой.

## Запускаешь сборку проекта впервые?
1. Создай и заполни **.env** файл на основе .env.example (*или запроси детали у старшего разработчика*)
> Убедись, что у тебя установлен Docker `docker -v`
2. Запусти приложение `./app up`
> Докеру понадобится несколько секунд, чтобы установить соединение с БД, поэтому сначала может быть показана ошибка
3. Выбери язык, создай пользователя и авторизуйся [http://localhost:8000](http://localhost:8000/wp-admin/) 
> **Не используй типичное имя пользователя (admin, user, root и тд) / создай сложный и уникальный пароль / укажи свой корпоративный имейл**
4. Запусти первоначальную настройку CMS `./app config`
> После команды `./app config` рекомендую перейти в **Настройки -> Постоянные ссылки** и просто нажать "Сохранить изменения"
5. [Импортируй дамп БД](#dbimport), если нужно `./app dbimport`

## Принимаешь изменения от другого разработчика?
1. Запусти приложение `./app up`
2. [Импортируй дамп БД](#dbimport), если нужно `./app dbimport`

### Что делает скрипт настройки CMS `./app config`
- Удаляет предустановленные темы
- Активирует нашу тему-стартер
- Удаляет предустановленные плагины
- Устанавливает рекомендуемые плагины и активирует их
- Меняет структуру пермалинков на '/%postname%/'
- Переименовывает "Пример страницы" в Главную и делает ее домашней

## Команды приложения
- Запустить приложение `./app up`
- Остановить приложение `./app stop`
- Создать нового админа `./app user-create`
- Очистить проект (удалит все, кроме файлов темы) `./app clean`
- Экспорт базы данных (в корне репозитория создастся dbdump.sql) `./app dbexport`
- <a id="dbimport"></a>Импорт базы данных `./app dbimport`
> Для импорта размести в корне репозитория дамп БД (не архив) с расширением .sql. **Созданные тобой пользователи будут сохранены после импорта**.

## Команды NPM
> Убедись, что у тебя установлен [Node.js](https://nodejs.org/en) `node --version` (рекомендую использовать [NVM](https://github.com/nvm-sh/nvm))
- Установить зависимости `./app npm-install` (используй `sudo` на маках с Apple Silicon)
- Отслеживать все файлы SASS/SCSS и компилировать их в css при их изменении `./app watch`
- Компилировать файлы SASS/SCSS в css `./app compile:css`

## Команды Composer
> Убедись, что у тебя установлен [Composer](https://getcomposer.org/) `composer --version`
- Установить зависимости `./app composer-install`
- Проверить все файлы PHP на наличие синтаксических ошибок `./app lint:php`
- Проверить все файлы PHP согласно [WP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) `./app lint:wpcs`

## Советы
- После команды `./app config` рекомендую перейти в **Настройки -> Постоянные ссылки** и просто нажать "Сохранить изменения"
- phpMyAdmin доступен по адресу [http://localhost:8080](http://localhost:8080/)
- Веди разработку под собственной учетной записью в админке
- Не размещай важный код в **wp-config.php**, так как этот файл в каждой среде свой. Динамической является только директория **/wp-content/** с темой, плагинами и загрузками.
- Не храни стили в штатном style.css, содержимое этого файла сбрасывается при каждой сборке.
- Не забывай передавать дамп БД другому разработчику или актуализировать ее при деплое в staging/production `./app dbexport`
- Тема поддерживает acf.json
![acf-sync](https://github.com/mksddn/wp-starter/assets/22976310/da78f925-ca72-4124-87a9-1e58dee0f398)


## TO DO
 - [x] Подключить и настроить линтер
 - [x] Реализовать возможность работать с SCSS/SASS
 - [x] Преднастроить API для headless CMS
 - [ ] Добавить шаблон формы для работы с mail.php
 - [ ] Добавить languages
 - [ ] Настроить CI/CD:
    - Автоматическое развертывание сайта на тестовом сервере при создании репозитория на основе данного шаблона
    - Перезапись директории /wp-content/ на сервере при PR в develop/master
