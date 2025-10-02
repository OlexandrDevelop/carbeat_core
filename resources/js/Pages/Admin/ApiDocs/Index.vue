<template>
    <Head title="API Документація" />

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="mb-6 text-2xl font-bold">
                        API Документація для мобільного додатку
                    </h1>

                    <nav class="mb-6 rounded border border-gray-200 bg-gray-50 p-4">
                        <h2 class="mb-2 text-sm font-semibold text-gray-600">Навігація по розділам</h2>
                        <ul class="flex flex-wrap gap-4 text-sm">
                            <li><a href="#auth" class="text-blue-600 hover:underline">Авторизація</a></li>
                            <li><a href="#subscriptions" class="text-blue-600 hover:underline">Підписки</a></li>
                            <li><a href="#availability" class="text-blue-600 hover:underline">Доступність майстра</a></li>
                            <li><a href="#schedule" class="text-blue-600 hover:underline">Графік/Слоти</a></li>
                            <li><a href="#booking" class="text-blue-600 hover:underline">Бронювання</a></li>
                            <li><a href="#tokens" class="text-blue-600 hover:underline">Токени</a></li>
                            <li><a href="#guide" class="text-blue-600 hover:underline">Інструкція</a></li>
                            <li><a href="#errors" class="text-blue-600 hover:underline">Помилки</a></li>
                            <li><a href="#base-url" class="text-blue-600 hover:underline">Базовий URL</a></li>
                        </ul>
                    </nav>

                    <div class="space-y-8">
                        <!-- Authentication Section -->
                        <section id="auth">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                🔐 Авторизація
                            </h2>

                            <div class="space-y-4 rounded-lg bg-gray-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        1. Запит OTP коду
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400"
                                    >
                                        <div>POST /api/auth/request-otp</div>
                                        <div class="text-gray-300">
                                            Content-Type: application/json
                                        </div>
                                        <div class="mt-2">
                                            {<br />
                                            &nbsp;&nbsp;"phone":
                                            "+380501234567"<br />
                                            }
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong> { "message":
                                        "OTP sent", "needs_registration": false
                                        }
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        2. Підтвердження OTP коду
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400"
                                    >
                                        <div>POST /api/auth/verify-otp</div>
                                        <div class="text-gray-300">
                                            Content-Type: application/json
                                        </div>
                                        <div class="mt-2">
                                            {<br />
                                            &nbsp;&nbsp;"phone":
                                            "+380501234567",<br />
                                            &nbsp;&nbsp;"sms_code": "1234"<br />
                                            }
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong><br />
                                        {<br />
                                        &nbsp;&nbsp;"user": { "id": 1, "name":
                                        "Master 4567", "phone": "+380501234567"
                                        },<br />
                                        &nbsp;&nbsp;"access_token":
                                        "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",<br />
                                        &nbsp;&nbsp;"refresh_token":
                                        "abc123def456...",<br />
                                        &nbsp;&nbsp;"expires_in": 3600<br />
                                        }
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        3. Оновлення токену
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400"
                                    >
                                        <div>POST /api/auth/refresh</div>
                                        <div class="text-gray-300">
                                            Content-Type: application/json
                                        </div>
                                        <div class="mt-2">
                                            {<br />
                                            &nbsp;&nbsp;"refresh_token":
                                            "abc123def456..."<br />
                                            }
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong><br />
                                        {<br />
                                        &nbsp;&nbsp;"access_token":
                                        "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",<br />
                                        &nbsp;&nbsp;"refresh_token":
                                        "xyz789uvw012...",<br />
                                        &nbsp;&nbsp;"expires_in": 3600<br />
                                        }
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        4. Отримання інформації про користувача
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400"
                                    >
                                        <div>GET /api/auth/me</div>
                                        <div class="text-gray-300">
                                            Authorization: Bearer {access_token}
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong><br />
                                        {<br />
                                        &nbsp;&nbsp;"user": { "id": 1, "name":
                                        "Master 4567", "phone": "+380501234567"
                                        }<br />
                                        }
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        5. Вихід з системи
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400"
                                    >
                                        <div>POST /api/auth/logout</div>
                                        <div class="text-gray-300">
                                            Authorization: Bearer {access_token}
                                        </div>
                                        <div class="text-gray-300">
                                            Content-Type: application/json
                                        </div>
                                        <div class="mt-2">
                                            {<br />
                                            &nbsp;&nbsp;"refresh_token":
                                            "abc123def456..."<br />
                                            }
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong> { "message":
                                        "Successfully logged out" }
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Token Management Section -->
                        <section id="tokens">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                ⏰ Управління токенами
                            </h2>

                            <div class="rounded-lg bg-yellow-50 p-4">
                                <h3 class="mb-2 font-semibold text-yellow-800">
                                    Важлива інформація:
                                </h3>
                                <ul class="space-y-1 text-sm text-yellow-700">
                                    <li>
                                        • <strong>Access Token</strong> дійсний
                                        60 хвилин (1 година)
                                    </li>
                                    <li>
                                        • <strong>Refresh Token</strong> дійсний
                                        14 днів
                                    </li>
                                    <li>
                                        • Зберігайте refresh_token локально в
                                        додатку
                                    </li>
                                    <li>
                                        • Автоматично оновлюйте access_token при
                                        закінченні
                                    </li>
                                    <li>
                                        • Перевіряйте авторизацію при запуску
                                        додатку
                                    </li>
                                </ul>
                            </div>
                        </section>

                        <!-- Implementation Guide Section -->
                        <section id="guide">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                📱 Інструкція для розробника
                            </h2>

                            <div class="space-y-4 rounded-lg bg-blue-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-blue-800">
                                        1. Збереження токенів
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-blue-400"
                                    >
                                        <div>// Flutter example</div>
                                        <div>class TokenStorage {</div>
                                        <div>
                                            &nbsp;&nbsp;static const String
                                            _accessTokenKey = 'access_token';
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;static const String
                                            _refreshTokenKey = 'refresh_token';
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;static const String
                                            _tokenExpiryKey = 'token_expiry';
                                        </div>
                                        <div>&nbsp;&nbsp;</div>
                                        <div>
                                            &nbsp;&nbsp;static
                                            Future&lt;void&gt; saveTokens(String
                                            accessToken, String refreshToken,
                                            DateTime expiry) async {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;final prefs
                                            = await
                                            SharedPreferences.getInstance();
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;await
                                            prefs.setString(_accessTokenKey,
                                            accessToken);
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;await
                                            prefs.setString(_refreshTokenKey,
                                            refreshToken);
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;await
                                            prefs.setString(_tokenExpiryKey,
                                            expiry.toIso8601String());
                                        </div>
                                        <div>&nbsp;&nbsp;}</div>
                                        <div>}</div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-blue-800">
                                        2. Перевірка авторизації при старті
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-blue-400"
                                    >
                                        <div>class AuthService {</div>
                                        <div>
                                            &nbsp;&nbsp;Future&lt;bool&gt;
                                            checkAuthStatus() async {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;final token
                                            = await
                                            TokenStorage.getAccessToken();
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;if (token ==
                                            null) return false;
                                        </div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;if (!await
                                            TokenStorage.isTokenValid()) {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return
                                            await refreshToken();
                                        </div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;}</div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;return true;
                                        </div>
                                        <div>&nbsp;&nbsp;}</div>
                                        <div>}</div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-blue-800">
                                        3. Автоматичне оновлення токену
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-blue-400"
                                    >
                                        <div>
                                            Future&lt;bool&gt; refreshToken()
                                            async {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;final refreshToken =
                                            await
                                            TokenStorage.getRefreshToken();
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;if (refreshToken ==
                                            null) return false;
                                        </div>
                                        <div>&nbsp;&nbsp;</div>
                                        <div>&nbsp;&nbsp;try {</div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;final
                                            response = await http.post(
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Uri.parse('$baseUrl/api/auth/refresh'),
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;headers:
                                            {'Content-Type':
                                            'application/json'},
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;body:
                                            json.encode({'refresh_token':
                                            refreshToken}),
                                        </div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;);</div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;if
                                            (response.statusCode == 200) {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;final
                                            data = json.decode(response.body);
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;await
                                            TokenStorage.saveTokens(
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;data['access_token'],
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;data['refresh_token'],
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DateTime.now().add(Duration(hours:
                                            1)),
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;);
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return
                                            true;
                                        </div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;}</div>
                                        <div>&nbsp;&nbsp;} catch (e) {</div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;print('Token
                                            refresh failed: $e');
                                        </div>
                                        <div>&nbsp;&nbsp;}</div>
                                        <div>&nbsp;&nbsp;return false;</div>
                                        <div>}</div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Error Handling Section -->
                        <section id="errors">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                ⚠️ Обробка помилок
                            </h2>

                            <div class="space-y-3 rounded-lg bg-red-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-red-800">
                                        Коди помилок:
                                    </h3>
                                    <ul class="space-y-1 text-sm text-red-700">
                                        <li>
                                            • <strong>400</strong> - Невірний
                                            OTP код
                                        </li>
                                        <li>
                                            • <strong>401</strong> - Невірний
                                            або прострочений токен
                                        </li>
                                        <li>
                                            • <strong>422</strong> - Відсутній
                                            refresh_token
                                        </li>
                                        <li>
                                            • <strong>500</strong> - Внутрішня
                                            помилка сервера
                                        </li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-red-800">
                                        Приклад обробки:
                                    </h3>
                                    <div
                                        class="mt-2 rounded bg-black p-3 font-mono text-sm text-red-400"
                                    >
                                        <div>try {</div>
                                        <div>
                                            &nbsp;&nbsp;final response = await
                                            http.post(...);
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;if (response.statusCode
                                            == 401) {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;// Токен
                                            прострочений, спробувати оновити
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;final
                                            refreshed = await refreshToken();
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;if
                                            (!refreshed) {
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;//
                                            Перенаправити на екран входу
                                        </div>
                                        <div>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;navigateToLogin();
                                        </div>
                                        <div>&nbsp;&nbsp;&nbsp;&nbsp;}</div>
                                        <div>&nbsp;&nbsp;}</div>
                                        <div>} catch (e) {</div>
                                        <div>
                                            &nbsp;&nbsp;print('Request failed:
                                            $e');
                                        </div>
                                        <div>}</div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Subscriptions Section -->
                        <section id="subscriptions">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                💳 Підписки (In-App Purchase)
                            </h2>

                            <div class="space-y-4 rounded-lg bg-gray-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        1. Перевірка/реєстрація підписки
                                    </h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>POST /api/v1/subscription/check</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                        <div class="text-gray-300">Content-Type: application/json</div>
                                        <div class="mt-2">{<br/>
                                            &nbsp;&nbsp;"platform": "apple" | "google",<br/>
                                            &nbsp;&nbsp;"receipt_token": "...",<br/>
                                            &nbsp;&nbsp;"product_id": "com.app.premium.monthly"<br/>
                                        }</div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong> { "active": true, "platform": "apple", "expires_at": "2025-11-02T12:00:00Z", "product_id": "..." }
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">
                                        2. Отримати статус підписки
                                    </h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>GET /api/v1/subscription/status</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong> { "active": true, "expires_at": "..." }
                                    </div>
                                </div>

                                <div class="rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800">
                                    <strong>Нотатка:</strong> на деві сервер приймає будь-який токен та ставить строк дії +1 місяць. У продакшні будуть використовуватись реальні дані Apple/Google.
                                </div>
                            </div>
                        </section>

                        <!-- Availability Flag Section -->
                        <section id="availability">
                            <h2 class="mb-4 text-xl font-semibold text-blue-600">🟢 Доступність майстра (прапорець)</h2>
                            <div class="space-y-4 rounded-lg bg-gray-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-green-600">Поставити статус "вільний"</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>POST /api/v1/masters/{id}/availability</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Зняти статус "вільний"</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>DELETE /api/v1/masters/{id}/availability</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Перевірити доступність</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>GET /api/v1/masters/{id}/availability</div>
                                    </div>
                                </div>
                                <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                                    Прапорець впливає лише на відображення майстра як доступного "зараз" у списках/мапі та не визначає, чи можна записатись на конкретний час.
                                </div>
                            </div>
                        </section>

                        <!-- Schedule/Slots Section -->
                        <section id="schedule">
                            <h2 class="mb-4 text-xl font-semibold text-blue-600">🗓️ Графік роботи / Слоти</h2>
                            <div class="space-y-4 rounded-lg bg-gray-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-green-600">Додати правило (тижневий графік)</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>POST /api/v1/masters/{id}/slots/rules</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                        <div class="text-gray-300">Content-Type: application/json</div>
                                        <div class="mt-2">{ "day_of_week": 1, "start_time": "09:00", "end_time": "18:00", "active": true }</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Видалити правило</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>DELETE /api/v1/masters/{id}/slots/rules/{ruleId}</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Додати вихідний/перерву (виняток)</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>POST /api/v1/masters/{id}/slots/time-off</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                        <div class="text-gray-300">Content-Type: application/json</div>
                                        <div class="mt-2">{ "start_time": "2025-10-03T09:00:00Z", "end_time": "2025-10-03T13:00:00Z", "reason": "..." }</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Видалити вихідний/перерву</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>DELETE /api/v1/masters/{id}/slots/time-off/{offId}</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Переглянути інтервали на день (обчислено з правил − винятки)</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>GET /api/v1/masters/{id}/slots/day?date=YYYY-MM-DD</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-green-600">Синхронізувати інтервали дня в Redis</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>POST /api/v1/masters/{id}/slots/sync-day?date=YYYY-MM-DD</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">Після зміни графіку викликайте sync-day для швидкого застосування при бронюванні.</div>
                                </div>
                            </div>
                        </section>

                        <!-- Booking Section -->
                        <section id="booking">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                📅 Бронювання у майстрів
                            </h2>

                            <div class="space-y-4 rounded-lg bg-gray-50 p-4">
                                <div>
                                    <h3 class="font-semibold text-green-600">1. Доступні слоти майстра</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>GET /api/v1/booking/masters/{masterId}/slots?date=YYYY-MM-DD&amp;duration_minutes=30</div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">2. Створити бронювання (клієнт)</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>POST /api/v1/booking/masters/{masterId}</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                        <div class="text-gray-300">Content-Type: application/json</div>
                                        <div class="mt-2">{<br/>
                                            &nbsp;&nbsp;"start_time": "2025-10-02T12:00:00Z",<br/>
                                            &nbsp;&nbsp;"end_time": "2025-10-02T12:30:00Z",<br/>
                                            &nbsp;&nbsp;"note": "Поміняти масло"<br/>
                                        }</div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <strong>Відповідь:</strong> { "id": 123, "status": "pending", ... }<br/>
                                        <strong>Помилки:</strong> 409 (Slot already booked), 422 (Not available)
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">3. Список бронювань майстра</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>GET /api/v1/booking/master?date=YYYY-MM-DD</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-600">
                                        Потребує активної підписки і фічі тарифу <code>"booking_management"</code>. Помилки: 402 (Subscription required), 403 (Feature not allowed)
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-green-600">4. Змінити статус бронювання</h3>
                                    <div class="mt-2 rounded bg-black p-3 font-mono text-sm text-green-400">
                                        <div>PUT /api/v1/booking/{bookingId}/status</div>
                                        <div class="text-gray-300">Authorization: Bearer {access_token}</div>
                                        <div class="text-gray-300">Content-Type: application/json</div>
                                        <div class="mt-2">{ "status": "confirmed" | "cancelled" }</div>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                                    <strong>Коментарі для Flutter:</strong> слоти будуються на Redis-інтервалах доступності майстра; дублювання бронювань блокується перевіркою перетинів у БД.
                                </div>
                            </div>
                        </section>

                        <!-- Base URL Section -->
                        <section id="base-url">
                            <h2
                                class="mb-4 text-xl font-semibold text-blue-600"
                            >
                                🌐 Базовий URL
                            </h2>

                            <div class="rounded-lg bg-green-50 p-4">
                                <div class="text-sm text-green-700">
                                    <strong>Розробка:</strong>
                                    http://localhost:100<br />
                                    <strong>Продакшн:</strong>
                                    https://carbeat.online
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
</script>
