# Rescan
Rescan the state of a payment.

REQUEST
---

[GET] **/payments/*paymentId*/rescan**

The **rescan** endpoint provides a method of checking and updating a payment state if for some reason it appears to have missed an automatic state change notification.

This endpoint expects no request payload.

RESPONSE
---
HTTP 204 No Content