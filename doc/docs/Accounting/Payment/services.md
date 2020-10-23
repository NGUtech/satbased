# Services
List enabled payment services for a specific payment.

REQUEST
---

[GET] **/payments/*paymentId*/services**

This endpoint expects no request payload.

RESPONSE
---
HTTP 200 Ok

```json
[
    "bitcoin",
    "lightning",
    "transfer"
]
```