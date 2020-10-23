# Logout
Logout a registered security user profile and reset authentication token.

REQUEST
---

[POST] **/logout**

The **logout** endpoint resets the authentication token for the user. This invalidates all logins using the same authentication token.

This endpoint expects no request payload.


RESPONSE
---
HTTP 204 No Content