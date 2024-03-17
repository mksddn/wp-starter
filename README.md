# Установка Wordpress через Docker + WP CLI

Стартер для разработки на WordPress локально и с командой.

## Порядок работы
1. Получи site.env файл у создателя репозитория или старшего разработчика. Укажи в нем данные для твоего сайта и оставь в корне проекта.
2. Скопируй .env файл для подключения к БД и измени его, если нужно `sh bin.sh env`
3. Запусти установку `sh bin.sh install`
4. Сайт доступен по адресу [http://localhost:8080/](http://localhost:8080/)

### Дополнительные команды:
1. Создать нового админа `docker-compose exec -T wp-cli wp user create --allow-root <YOUR_LOGIN> <YOUR_EMAIL> --role=administrator --user_pass=<YOUR_PASSWORD>` (укажи логин, имейл и пароль)
2. Экспорт БД `sh bin.sh dbexport`
3. Очистка проекта `sh bin.sh clean` (удалит все, кроме темы; остановит и удалит контейнеры)