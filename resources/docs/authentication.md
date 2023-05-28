# Authenticating requests

This API is authenticated by sending an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can obtain a token via the auth/token API endpoint.
