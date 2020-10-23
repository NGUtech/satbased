# Register
Register a security user profile.

REQUEST
---

[POST] **/profiles**

The **register** endpoint registers a security profile with a *pending* state.

Param    | Type   | Constraints          | Optional | Default
---------|:------:|----------------------|:--------:|:------:
name     | string | Between 4 & 64 chars | false    |
email    | string | Valid email address  | false    |
password | string | Between 8 & 64 chars | false    |
language | string | Available locales    | true     | en

```json
{
    "name": "my username",
    "email": "myemail@mydomain.dev",
    "password": "mypassword"
}
```

RESPONSE
---
HTTP 201 Created

```json
{
    "profileId": "satbased.security.profile-b01e2943-4fe0-40b0-ba3e-67cf1081476e",
    "revision": 0,
    "name": "my username",
    "email": "myemail@mydomain.dev",
    "passwordHash": "$2y$10$eWo4Xy8c8lahhDZM3EwPg.YTw5BvbWFG0XAUtj6kxVv0/i5YPyUJe",
    "language": "en",
    "role": "customer",
    "state": "pending",
    "registeredAt": "2020-09-03T20:10:28.693630+00:00",
    "verificationTokenExpiresAt": "2020-10-03T20:10:28.693630+00:00"
}
```