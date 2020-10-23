# Resource
Retrieve a payment resource.

REQUEST
---

[GET] **/payments/*paymentId***

The **resource** endpoint returns the specified payment data if permission is granted for the authenticated user.

This endpoint expects no request payload.


RESPONSE
---
HTTP 200 Ok

```json
{
    "paymentId": "satbased.accounting.payment-d4a00738-cc52-4be2-9f7a-640232477772",
    "revision": 3,
    "profileId": "satbased.security.profile-f1638d17-98b7-477c-ade8-b277b8799433",
    "accountId": "satbased.accounting.account-4ee2ce4d-0f62-4c52-877b-1d619de82be1",
    "references": {
        "mykey": "myvalue"
    },
    "accepts": ["bitcoin", "lightning", "transfer"],
    "amount": "1000000MSAT",
    "description": "staff fixture",
    "service": "testbitcoind",
    "transaction": {
        "label": "satbased.accounting.payment-b94fc7ee-e3f0-4414-81cf-e44da134b191",
        "amount": "1000000MSAT",
        "outputs": [
            {
                "address": "bcrt1qu50fe2kz9m382mk5wum6y4y8njf4jm7q2y3mtc",
                "value": "1000000MSAT"
            }
        ],
        "comment": "payment description",
        "confTarget": 3,
        "@type": "NGUtech\\Bitcoin\\Entity\\BitcoinTransaction"
    },
    "state": "settled",
    "direction": "incoming",
    "requestedAt": "2020-05-11T21:21:21.111222+00:00",
    "expiresAt": "2020-05-12T21:21:21.111222+00:00",
    "selectedAt": "2020-05-11T21:26:21.111222+00:00",
    "settledAt": "2020-05-12T21:31:21.111222+00:00",
    "@type": "Satbased\\Accounting\\ReadModel\\Standard\\Payment"
}
```