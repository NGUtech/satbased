# Approve
Approve a payment.

REQUEST
---

[GET] **/payments/*paymentId*/approve**

The **approve** endpoint provides a method of approving a payment with a second factor authorization. A payment can only be approved when it is in the `made` outgoing state.

Param          | Type   | Constraints                                | Optional | Default
---------------|:------:|--------------------------------------------|:--------:|:------:
t              | string | Valid approval token                       | false    |

RESPONSE
---
HTTP 204 OK

```json
{
    "paymentId": "satbased.accounting.payment-a8739b9c-ba2c-4521-86db-3fe93619393f",
    "revision": 1,
    "approvedAt": "2020-05-11T21:22:21.111222+00:00"
}
```