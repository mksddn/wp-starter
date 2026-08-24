# Стартер/шаблон для разработки на WordPress через Docker

## Содержание

- [Запуск приложения](#запуск-приложения)
- [Команды приложения](#команды-приложения)
- [Content Seeder](#content-seeder)
- [Единый адрес (десктоп + мобильные)](#единый-адрес-десктоп--мобильные)
- [Проверка кода (линтеры)](#проверка-кода-линтеры)
- [Советы](#советы)
- [AI-ассистенты и конфигурация Cursor](#ai-ассистенты-и-конфигурация-cursor)

---

## Запуск приложения

1. Убедись, что у тебя установлен [Docker](https://www.docker.com) `docker -v`
2. Запусти приложение `./app up`

| username | password |
| -------- | -------- |
| dev      | root     |

## Команды приложения

<a id="dbimport"></a>

```bash
./app up                              # запустить приложение
./app stop                            # остановить приложение
./app clean                           # очистить проект (удалит всё, кроме файлов темы)
./app hot-reload                      # browser-sync для дочерней темы (нужны debug и скрипт в wp_head)
./app debug-on                        # включить отладку
./app debug-off                       # отключить отладку
./app user-create                     # создать нового админа
./app db-export                       # экспорт БД в dbdump.sql в корне репозитория
./app db-import                       # импорт БД (.sql-дамп в корне репозитория)
./app seed-content                    # заполнить контент демо-данными (см. Content Seeder)
```

---

## Content Seeder

Инструмент для локальной разработки: создаёт недостающие сущности и заполняет поля placeholder-значениями.

### Что делает

- Создаёт sample-посты для **страниц, записей и CPT с ACF/SCF field groups** (по умолчанию до 3 шт. на тип) и sample-термины для `category`, `post_tag` и таксономий из ACF/SCF
- WordPress: заголовок, основной контент (h2/h3, списки, цитата, изображение, ссылка), excerpt, миниатюра, таксономии, описание термина
- ACF/SCF (если плагин активен): все поля на постах, options pages и терминах
- Для изображений используется единый файл темы: `wp-theme/assets/images/placeholder.svg` (импортируется в медиатеку один раз)

### CLI

```bash
./app seed-content --dry-run          # превью без записи в БД
./app seed-content                    # создать недостающие сущности и заполнить пустые поля
./app seed-content --overwrite        # перезаписать существующие значения
./app seed-content --no-create        # только заполнить, не создавать новые сущности
./app seed-content --post-type=page --limit=5
```

### Admin (скрытая страница, без пункта меню)

```
{site}/wp-admin/admin.php?page=wp-theme-content-seeder
```

Страница не отображается в меню, чтобы снизить риск затирания продовых данных.

---

## Единый адрес (десктоп + мобильные)

Сайт доступен по одному адресу — LAN IP (например `http://192.168.1.106:8000`). Открывай его и на компьютере, и на телефоне в той же сети WiFi. При `./app up` и `./app db-import` WordPress и контент в БД автоматически приводятся к этому адресу.

---

## Проверка кода (линтеры)

```bash
composer install && npm install       # установить зависимости в корне проекта
composer lint                         # проверить PHP (PHPCS, Psalm, Rector)
composer fix                          # автофикс PHP
npm run lint:htmlvalidate             # проверить HTML-фрагменты
```

---

## Советы

- **Веди работу только в дочерней теме**, так как после обновления родительской темы все изменения в ней сбросятся.
- Не размещай важный код в **wp-config.php**, так как этот файл в каждой среде свой. Рабочей является только директория **/wp-content/** с темой.
- Не храни дамп БД в репозитории - Git для кода, а не для контента.
- Для переноса контента или всего сайта используй плагин [MksDdn Migrate Content](https://wordpress.org/plugins/mksddn-migrate-content/)
- Панель phpMyAdmin доступна по [http://localhost:8080](http://localhost:8080) (или http://LAN_IP:8080 с мобильного в той же сети)
- Для Windows используй [WSL2](https://docs.microsoft.com/en-us/windows/wsl/install) или [Git Bash](https://git-scm.com/downloads)
- Перед запуском команд NPM, убедись, что у тебя установлен [Node.js](https://nodejs.org/en) `node --version` (рекомендую использовать [NVM](https://github.com/nvm-sh/nvm))
- Перед запуском линтеров, убедись, что у тебя установлен [Composer](https://getcomposer.org/) `composer --version` и [Node.js](https://nodejs.org/en) `node --version`

---

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
