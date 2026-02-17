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
- Включить browser-sync (hot-reload) для дочерней темы `./app hot-reload`
  > Слежение/обновление работает только когда включена отладка и подключен скрипт (который вызывается в `wp_head()`)
- Включить отладку `./app debug-on`
- Отключить отладку `./app debug-off`
- Создать нового админа `./app user-create`
- Экспорт базы данных (в корне репозитория создастся dbdump.sql) `./app db-export`
- <a id="dbimport"></a>Импорт базы данных `./app db-import`
  > Для импорта размести в корне репозитория дамп БД (не архив) с расширением .sql

### Единый адрес (десктоп + мобильные)
Сайт доступен по одному адресу — LAN IP (например `http://192.168.1.106:8000`). Открывай его и на компьютере, и на телефоне в той же WiFi. При `./app up` и `./app db-import` WordPress и контент в БД автоматически приводятся к этому адресу.

### Проверка кода (линтеры)
- Установить зависимости в корне проекта: `composer install && npm install`
- Проверить PHP (PHPCS, Psalm, Rector): `composer lint`
- Автофикс PHP: `composer fix`
- Проверить HTML-фрагменты: `npm run lint:htmlvalidate`

## Советы
- **Веди работу только в дочерней теме**, так как после удаленного обновления родительской темы все изменения в ней сбросятся.
- Не размещай важный код в **wp-config.php**, так как этот файл в каждой среде свой. Динамической является только директория **/wp-content/** с темой.
- Панель phpMyAdmin доступна по [http://localhost:8080](http://localhost:8080) (или http://LAN_IP:8080 с мобильного в той же сети)
- Для Windows используй [WSL2](https://docs.microsoft.com/en-us/windows/wsl/install) или [Git Bash](https://git-scm.com/downloads)
- Перед запуском команд NPM, убедись, что у тебя установлен [Node.js](https://nodejs.org/en) `node --version` (рекомендую использовать [NVM](https://github.com/nvm-sh/nvm))
- Перед запуском линтинга, убедись, что у тебя установлен [Composer](https://getcomposer.org/) `composer --version` и [Node.js](https://nodejs.org/en) `node --version`

## AI-ассистенты и конфигурация Cursor

В проекте настроены инструкции для AI-ассистентов в директории `.cursor/`:

### Совместимость с другими редакторами

**Skills (Agent Skills)** — открытый стандарт, работает в разных редакторах:
- **Cursor**: `.cursor/skills/` (автоматически обнаруживается)
- **Claude Desktop**: `.claude/skills/` (создай симлинк или скопируй папку)
- **Codex**: `.codex/skills/` (создай симлинк или скопируй папку)

**Agents/Subagents** — поддерживается в:
- **Cursor**: `.cursor/agents/` (автоматически обнаруживается)
- **Claude Desktop**: `.claude/agents/` (создай симлинк или скопируй папку)

**Commands** — специфично для Cursor (`.cursor/commands/`)

### Как использовать в других редакторах

1. **Claude Desktop**: Создай симлинки или скопируй папки:
   ```bash
   ln -s .cursor/skills .claude/skills
   ln -s .cursor/agents .claude/agents
   ```

2. **Codex**: Аналогично для Codex:
   ```bash
   ln -s .cursor/skills .codex/skills
   ln -s .cursor/agents .codex/agents
   ```

3. **Другие редакторы с поддержкой Agent Skills**: Следуй стандарту [agentskills.io](https://agentskills.io) — структура папок и формат `SKILL.md` совместимы.

### Что включено

- **Rules**: Общие правила проекта в `.cursor/rules/`
- **Skills**: Специализированные знания по WordPress (безопасность, хуки, REST API, кодстайл, ACF)
- **Agents**: Специализированные агенты для ревью кода (безопасность, кодстайл, ACF)
- **Commands**: Быстрые команды для создания функций, хуков, REST endpoints и т.д.