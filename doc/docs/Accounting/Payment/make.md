# Make
Make a payment.

REQUEST
---

[POST] **/payments/make**

The **make** endpoint initiates a payment to a given recipient address/invoice depending on payment method selected.

Param          | Type   | Constraints                                | Optional | Default
---------------|:------:|--------------------------------------------|:--------:|:------:
service        | string | Enabled payment service                    | false    |
amount         | string | Number > 0 suffixed with sat/msat/btc      | fasle    |
transaction    | object | Transaction payload for selected service   | false    |
description    | string | Between 1 & 80chars                        | true     | null
references     | object | Up to 5 key:value pairs between 1 & 300char| true     | {}

Transaction payloads vary from service to service. Satbased has built-in support for bitcoind and various lightning services but any payment service can be implemented and transaction payloads passed in as required, such as for **Liquid** network, or implementing *keysend* through Lightning etc.

Transaction payloads for built-in **bitcoin** based services are:

Param          | Type   | Constraints                                | Optional | Default
---------------|:------:|--------------------------------------------|:--------:|:------:
address        | string | Valid bitcoin address                      | false    |
feeRate        | float  | Maximum fee rate expressed in sats/byte    | true     | 0.00002

Transaction payloads for built-in **lightning** based services are:

Param          | Type   | Constraints                                | Optional | Default
---------------|:------:|--------------------------------------------|:--------:|:------:
invoice        | string | Valid lightning invoice                    | false    |
feeLimit       | float  | Maximum fee as percentage of invoice value | true     | 0.5

RESPONSE
---
HTTP 201 Created

```json
{
    "paymentId": "satbased.accounting.payment-b1835620-8798-4d03-a195-297eedcb1ccd",
    "revision": 0,
    "profileId": "satbased.security.profile-f1638d17-98b7-477c-ade8-b277b8799433",
    "accountId": "satbased.accounting.account-27e98b8f-08f6-47d5-9769-dc946b8d3df4",
    "references": {
        "my-app-key": "my-app-value"
    },
    "amount": "500000000MSAT",
    "description": "my payment description",
    "service": "bitcoin",
    "transaction": {
        "label": "satbased.accounting.payment-b1835620-8798-4d03-a195-297eedcb1ccd",
        "amount": "500000000MSAT",
        "outputs": [
            {
                "address": "bcrt1qyz292sne02t8c847klvarfcyk0usfcu26qamtq",
                "value": "500000000MSAT"
            }
        ],
        "feeRate": 0.00002,
        "feeEstimate": "416000MSAT",
        "confTarget": 3,
        "@type": "NGUtech\\Bitcoin\\Entity\\BitcoinTransaction"
    },
    "requestedAt": "2020-05-11T21:21:21.111222+00:00"
}
```