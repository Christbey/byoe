# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your authentication token by logging in via the <code>POST /api/v1/auth/login</code> endpoint. The token must be prefixed with "Bearer " in the Authorization header.
