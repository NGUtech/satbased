# Resource
Retrieve a security user profile resource.

REQUEST
---

[GET] **/profiles/*profileId***

The **resource** endpoint returns the specified security profile resource data if permission is granted for the authenticated user.

This endpoint expects no request payload.


RESPONSE
---
HTTP 200 Ok

```json
{
    "@type": "Satbased\\Security\\ReadModel\\Standard\\Profile",
    "profileId": "satbased.security.profile-b01e2943-4fe0-40b0-ba3e-67cf1081476e",
    "revision": 11,
    "name": "my username",
    "email": "myemail@mydomain.dev",
    "language": "en",
    "role": "customer",
    "state": "verified",
    "registeredAt": "2020-09-03T20:10:28.693630+00:00",
    "verifiedAt": null,
    "closedAt": null,
    "tokens": [
        {
            "@type": "Satbased\\Security\\Entity\\AuthenticationToken",
            "id": "9bc67c37-14b2-4510-9ed9-d6e7fd9c376e",
            "token": "48b057d61157edb31535d8fc0f3d3200aa27cc45f6b90ab42f401e6c5fa3da3c",
            "expiresAt": "2020-10-03T20:38:06.362550+00:00"
        },
        {
            "@type": "Satbased\\Security\\Entity\\VerificationToken",
            "id": "d41d6e1d-6d6a-4f42-88f7-be52074249d9",
            "token": "1c0f9fd357835a28703b134a13e887ff86e46eb08f0048f3aaaeb87f4c62625c",
            "expiresAt": "2020-10-03T20:38:06.362550+00:00"
        }
    ]
}
```