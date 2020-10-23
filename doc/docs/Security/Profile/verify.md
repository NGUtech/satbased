# Verify
Verify a registered security user profile.

REQUEST
---

[GET] **/profiles/*profileId*/verify**

The **verify** endpoint verifies the email address of a profile and proceeds it to a *verified* state on success.

Param    | Type     | Constraints          | Optional | Default
---------|:--------:|----------------------|:--------:|:------:
profileId| string   | Valid profile id     | false    |
t        | string   | 64 chars hash        | false    |


RESPONSE
---
HTTP 200 Ok

```json
{
    "profileId": "satbased.security.profile-b01e2943-4fe0-40b0-ba3e-67cf1081476e",
    "revision": 3,
    "token": "e719b4fdd7b3a90dd00fed5683a08024ded1b7da75bf0f24f9f9700a5a1ea4ed",
    "verifiedAt": "2020-09-03T20:20:28.693630+00:00"
}
```
