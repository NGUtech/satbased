# Login
Login a registered security user profile.

REQUEST
---

[POST] **/login**

The **login** endpoint provides an authentication and xsrf token for subsequent requests to access controlled endpoints.

Param    | Type     | Constraints          | Optional | Default
---------|:--------:|----------------------|:--------:|:------:
email    | string   | Valid email address  | false    |
password | string   | Between 8 & 64 chars | false    |

```json
{
    "email": "myemail@mydomain.dev",
    "password": "mypassword"
}
```

RESPONSE
---
HTTP 200 Ok

```json
{
    "profileId": "satbased.security.profile-b01e2943-4fe0-40b0-ba3e-67cf1081476e",
    "revision": 5,
    "authenticationTokenExpiresAt": "2020-10-03T20:38:06.362550+00:00"
}
```
A secure JWT cookie is returned with the response the content of which must be sent with subsequent requests as a bearer token authorization header. An insecure XSRF token is also returned matching the JWT and must also be returned on subsequent requests as a cookie or `X-XSRF-TOKEN` custom http header.