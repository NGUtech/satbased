# Request
Request a payment.

REQUEST
---

[POST] **/payments/request**

The **request** endpoint creates a payment resource which can be presented to a payer.

Param          | Type   | Constraints                                | Optional | Default
---------------|:------:|--------------------------------------------|:--------:|:------:
amount         | string | Number > 0 suffixed with sat/msat/btc      | false    |
description    | string | Between 1 & 80chars                        | true     | null
accepts        | array  | Names of configured payment services       | true     | []
references     | object | Up to 5 key:value pairs between 1 & 300char| true     | {}
expires        | string | Date/timestamp > now & < 6 months          | true     | null

Once a request has been made, the payment method must be selected via the [select](select.md) endpoint.

RESPONSE
---
HTTP 201 Created

```json
{
    "paymentId": "satbased.accounting.payment-a8739b9c-ba2c-4521-86db-3fe93619393f",
    "revision": 0,
    "profileId": "satbased.security.profile-f1638d17-98b7-477c-ade8-b277b8799433",
    "accountId": "satbased.accounting.account-27e98b8f-08f6-47d5-9769-dc946b8d3df4",
    "references": {
        "my-app-key": "my-app-value"
    },
    "accepts": ["bitcoin", "lightning", "transfer"],
    "amount": "2000000000MSAT",
    "description": "my payment",
    "requestedAt": "2020-05-11T21:21:21.111222+00:00",
    "expiresAt": null
}
```