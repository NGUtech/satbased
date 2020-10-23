# Cancel
Cancel a held payment.

REQUEST
---

[POST] **/payments/*paymentId*/cancel**

The **cancel** endpoint provides a method of cancelling a payment which is in an intermediate held state, awaiting settlement or cancellation by the application.

This endpoint expects no request payload.

RESPONSE
---
HTTP 204 No Content