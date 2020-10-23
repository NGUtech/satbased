# My
Retrieve the account for the authenticated user.

REQUEST
---

[GET] **/accounts/my**

The **my** endpoint returns the current account data and any wallet balances for the authenticated user.

This endpoint expects no request payload.


RESPONSE
---
HTTP 200 Ok

```json
{
    "@type": "Satbased\\Accounting\\ReadModel\\Standard\\Account",
    "accountId": "satbased.accounting.account-27e98b8f-08f6-47d5-9769-dc946b8d3df4",
    "revision": 1,
    "profileId": "satbased.security.profile-b01e2943-4fe0-40b0-ba3e-67cf1081476e",
    "wallet": [
        "MSAT": "0MSAT"
    ],
    "state": "opened",
    "openedAt": "2020-09-03T20:10:28.693630+00:00",
    "frozenAt": null
}
```