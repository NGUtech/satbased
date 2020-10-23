# Settle
Settle a held payment.

REQUEST
---

[POST] **/payments/*paymentId*/settle**

The **settle** endpoint provides a method of settling a payment which is in an intermediate held state, awaiting settlement or cancellation by the application.

This endpoint expects no request payload.

RESPONSE
---
HTTP 204 No Content