# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Токен выдаётся при входе: <code>POST /api/v1/auth/login</code>. Передавайте его в заголовке <code>Authorization: Bearer {токен}</code>.
