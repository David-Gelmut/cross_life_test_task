
### Задача: средствами laravel (php 7+, mysql) необходимо разработать часть магазина

Инициализация проекта:
1. Устанавливаем Composer:

    ```
    composer install
    ```
2. Запускаем миграции и сидеры:
   ```
   php artisan migrate
   php artisan db:seed
   ```
   При запуске сидеров создаётся тестовый пользователь User с email "user@user.com" и паролем "password"

3. Запускаем сервер :

    ```
    php artisan serve
    ```

### Пример запроса в Postman:
Пример:

Тело запроса заполняется в raw тип json.

Запрос:

/create-order

    {
        "products": [
            {
                "product_id": 1,
                "quantity": 1
            },
            {
                "product_id": 2,
                "quantity": 4
            }
        ],
        "user_id":1
    }
Ответ:

    {
        "message": "Заказ №895281e3-b34a-4072-9485-fe5d3ed208d4 успешно создан"
    }



Запрос:

/approve-order

    {
        "order_number":"895281e3-b34a-4072-9485-fe5d3ed208d4"
    }
Ответ:

    {
        "message": "Заказ подтвержден",
        "total_amount": 1170
    }
