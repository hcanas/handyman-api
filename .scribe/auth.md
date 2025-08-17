# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

If you logged in with the header **`X-Client-Platform: "web"`**, the token will be inside a cookie and will be sent automatically if you include credentials in your requests.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

The `token` is only provided if you log in successfully.
