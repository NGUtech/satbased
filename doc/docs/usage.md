# Usage


## API

API interaction is via standard HTTP GET & POST requests. All payloads to be sent and received are as JSON documents.

## Authorization

Calling the security profile [login](Security/Profile/login.md) endpoint successfully will return a JWT and a XSRF token as response headers which can be interpreted as cookies.

=== "Shell"
    ```sh
    curl -v -XPOST \
    -H"Content-Type: application/json" \
    -H"Accept: application/json" \
    http://local.satbased.com/login \
    -d '{"email":"customer-verified@satbased.com", "password":"password"}'
    ```

=== "JS"
    ```js
    fetch("http://local.satbased.com/login", {
        method: "POST",
        mode: "cors",
        credentials: "include",
        headers: new Headers({
            Accept: "application/json",
            "Content-Type": "application/json"
        }),
        body: JSON.stringify({email:"customer-verified@satbased.com", password:"password"})
    })
    .then(response => response.json())
    .then(console.log)
    .catch(console.error);
    ```

=== "PHP"
    ```php
    $response = (new GuzzleHttp\Client)->request('POST', 'http://local.satbased.com/login', [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => GuzzleHttp\Psr7\Utils::streamFor('{"email":"customer-verified@satbased.com", "password":"password"}')
    ]);
    ```

Calling the [logout](Security/Profile/logout.md) endpoint will reset the previous JWT on the server.

## Authentication

If the client is capable of handling cookies, the JWT and XSRF should will be sent automatically on subsequent requests.

Otherwise, the JWT should be sent to any subsequent requests as an `Authorization` header with type `Bearer`. In addition the XSRF token should be sent as a custom `X-XSRF-TOKEN` header.

=== "Shell"
    ```sh
    curl -v -XGET \
    -H"Accept: application/json" \
    -H"Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJTYXRiYXNlZCIsImF1ZCI6IlNhdGJhc2VkIiwiZXhwIjoxNjA2MTQwNTAxLCJuYmYiOjE2MDM0NjIxMDEsImlhdCI6MTYwMzQ2MjEwMSwianRpIjoiOTUyODViOWUtODlmNS00ZTNjLWI5YzktODdkOGU1MTQ5NjNhIiwieHNyZiI6IjZiZTdiM2UxYzhiZjM1YmE2MGJhODVmNWMyNjEwOTk3MmFjYTgwNjZiYThmMzRhNzg2ZGY4MzM1M2Q2OTQ2OTgiLCJ1aWQiOiJzYXRiYXNlZC5zZWN1cml0eS5wcm9maWxlLWY4M2FjMjA4LTU5YTctNDRlMi1iYWU3LTg1NzlhMWYwMGIxZiJ9.VaQbXS5nwimf4GuLvcd1TgGpx16oNqBuVL94Of4PO9M" \
    -H"X-XSRF-TOKEN: 6be7b3e1c8bf35ba60ba85f5c26109972aca8066ba8f34a786df83353d694698" \
    http://local.satbased.com/profiles/me
    ```

=== "JS"
    ```js
    # after login React should store cookies so authorization is implicit
    fetch("http://local.satbased.com/profiles/me", {
        method: "GET",
        mode: "cors",
        credentials: "include",
        headers: new Headers({
            Accept: "application/json"
        })
    })
    .then(response => response.json())
    .then(console.log)
    .catch(console.error);
    ```

=== "PHP"
    ```php
    $response = (new GuzzleHttp\Client)->request('GET', 'http://local.satbased.com/profiles/me', [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJTYXRiYXNlZCIsImF1ZCI6IlNhdGJhc2VkIiwiZXhwIjoxNjA2MTQwNTAxLCJuYmYiOjE2MDM0NjIxMDEsImlhdCI6MTYwMzQ2MjEwMSwianRpIjoiOTUyODViOWUtODlmNS00ZTNjLWI5YzktODdkOGU1MTQ5NjNhIiwieHNyZiI6IjZiZTdiM2UxYzhiZjM1YmE2MGJhODVmNWMyNjEwOTk3MmFjYTgwNjZiYThmMzRhNzg2ZGY4MzM1M2Q2OTQ2OTgiLCJ1aWQiOiJzYXRiYXNlZC5zZWN1cml0eS5wcm9maWxlLWY4M2FjMjA4LTU5YTctNDRlMi1iYWU3LTg1NzlhMWYwMGIxZiJ9.VaQbXS5nwimf4GuLvcd1TgGpx16oNqBuVL94Of4PO9M',
            'X-XSRF-TOKEN' => '6be7b3e1c8bf35ba60ba85f5c26109972aca8066ba8f34a786df83353d694698'
        ]
    ]);
    ```