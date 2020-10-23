# Select
Select a payment method for a requested payment.

REQUEST
---

[POST] **/payments/*paymentId*/select**

The **select** endpoint specifies the payment method a payment request payer wishes to use.

Param          | Type   | Constraints                                | Optional | Default
---------------|:------:|--------------------------------------------|:--------:|:------:
service        | string | Enabled payment service                    | false    |

Enabled services for the payment and current security profile are listed at the [services](services.md) endpoint.

RESPONSE
---
HTTP 200 Ok

The transaction payload is generated for the selected payment service. This can include a bitcoin address, a lightning invoice, or other payment metadata.

```json
{
    "paymentId": "satbased.accounting.payment-a8739b9c-ba2c-4521-86db-3fe93619393f",
    "revision": 1,
    "service": "bitcoin",
    "transaction": {
        "label": "satbased.accounting.payment-a8739b9c-ba2c-4521-86db-3fe93619393f",
        "amount": "2000000000MSAT",
        "outputs": [
            {
                "address": "bcrt1qa7pf02lxh95asyl0qp0pd9sdhunntu6xvxmpr2",
                "value": "2000000000MSAT"
            }
        ],
        "comment": "my payment",
        "confTarget": 3,
        "@type": "NGUtech\\Bitcoin\\Entity\\BitcoinTransaction"
    },
    "selectedAt": "2020-09-14T13:33:03.285514+00:00"
}
```