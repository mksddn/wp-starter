# Документация по плагину Forms Handler

Универсальная система обработки форм для WordPress с поддержкой REST API, шорткодов, валидации, отправки email, уведомлений в Telegram, интеграции с Google Sheets и сохранения заявок в админ-панели.

## Основные возможности

-   **REST API и шорткоды**: Интеграция форм как через API, так и с помощью шорткодов на страницах.
-   **Гибкая настройка**: Конфигурация полей, получателей email, темы письма и BCC через админ-панель.
-   **Многоканальные уведомления**:
    -   Email
    -   Telegram
    -   Google Sheets
-   **Хранение и экспорт**: Сохранение всех заявок в админ-панели с возможностью экспорта в CSV.
-   **Безопасность**: Встроенные механизмы защиты от распространенных уязвимостей.

## Создание и настройка формы

1.  Перейдите в раздел **Forms** в админ-панели WordPress и нажмите **Add New**.
2.  Заполните заголовок формы (например, "Contact Form"). Слаг (slug) будет сгенерирован автоматически и будет использоваться в REST API и шорткоде.
3.  Настройте параметры формы в секции **Form Settings**.

### Конфигурация полей (Fields Configuration)

Это JSON-массив, описывающий поля формы. Каждое поле — это объект со следующими ключами:
-   `name` (string, required): Уникальное имя поля (используется в HTML и API).
-   `label` (string, required): Отображаемое название поля.
-   `type` (string, required): Тип поля (`text`, `email`, `tel`, `textarea`, и т.д.).
-   `required` (boolean, optional): `true`, если поле обязательно для заполнения.

**Пример конфигурации:**
```json
[
    {"name": "name", "label": "Name", "type": "text", "required": true},
    {"name": "email", "label": "Email", "type": "email", "required": true},
    {"name": "message", "label": "Message", "type": "textarea", "required": true}
]
```

### Настройка Email

-   **Recipients**: Email-адреса получателей через запятую.
-   **BCC Recipient**: Email для скрытой копии.
-   **Email Subject**: Тема письма.

### Настройка Telegram

Для интеграции с Telegram необходимо получить **Bot Token** и **Chat ID**.

1.  **Bot Token**:
    -   Найдите в Telegram бота `@BotFather`.
    -   Отправьте ему команду `/newbot` и следуйте инструкциям.
    -   Скопируйте полученный токен.
2.  **Chat ID**:
    -   Добавьте вашего нового бота в нужный чат или канал.
    -   Отправьте любое сообщение в этот чат.
    -   Перейдите по URL: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates` (замените `<YOUR_BOT_TOKEN>` на ваш токен).
    -   Найдите в JSON-ответе объект `chat` и скопируйте значение поля `id`.

Активируйте опцию **Send to Telegram** и вставьте полученные токен и ID в соответствующие поля.

### Интеграция с Google Sheets

1.  Перейдите в **Settings** → **Google Sheets Settings** в админ-панели WordPress.
2.  Следуйте инструкциям на странице для настройки OAuth 2.0 клиента в Google Cloud Console. Вам потребуется создать учетные данные и получить **Client ID** и **Client Secret**.
3.  Авторизуйтесь в вашем Google-аккаунте.
4.  В настройках формы активируйте опцию **Send to Google Sheets**, укажите **Spreadsheet ID** и, при необходимости, **Sheet Name**.

### Хранение в админ-панели

Активируйте опцию **Save submissions to admin panel**, чтобы все заявки сохранялись в разделе **Submissions**.

## Использование формы

### Через шорткод

Для вставки формы на страницу используйте шорткод, указав слаг формы:
`[form id="contact-form"]`

### Через REST API

Отправьте `POST` запрос на эндпоинт:
`/wp-json/wp/v2/forms/{form_slug}/submit`

-   Замените `{form_slug}` на слаг вашей формы.
-   Тело запроса должно быть в формате `application/json`.
-   Ключи в JSON-объекте должны соответствовать именам полей (`name`) из конфигурации.

**Пример запроса (curl):**
```bash
curl -X POST http://your-site.com/wp-json/wp/v2/forms/contact-form/submit \
-H "Content-Type: application/json" \
-d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "message": "Hello, this is a test."
}'
```

**Пример успешного ответа:**
```json
{
    "success": true,
    "message": "Form submitted successfully!",
    "form_id": 123,
    "form_title": "Contact Form",
    "delivery_results": {
        "email": { "success": true, "error": null },
        "telegram": { "success": true, "error": null, "enabled": true },
        "google_sheets": { "success": true, "error": null, "enabled": true },
        "admin_storage": { "success": true, "error": null, "enabled": true }
    },
    "submitted_fields": ["name", "email", "message"],
    "timestamp": "YYYY-MM-DD HH:MM:SS"
}
```

## Безопасность

Плагин включает следующие меры защиты:

-   **Фильтрация полей**: Принимаются только поля, определенные в конфигурации формы.
-   **Валидация данных**: Проверка обязательных полей, формата email и длины данных.
-   **CSRF-защита**: Использование WordPress nonces для форм, отправляемых через шорткод.
-   **Ограничения**: Лимиты на количество полей и общий размер запроса для защиты от DoS-атак.
-   **Санитизация данных**: Очистка всех входящих данных для предотвращения XSS и других атак. 