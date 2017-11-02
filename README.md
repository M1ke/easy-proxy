## Installation instructions

Create a file `_config.php`:

```
<?php

define('BASE', 'https://domain-to-proxy.com/path/');
define('KEY', 'some-random-key');
```

Create a file `_deploy`:

```
user@someserver.com:/var/www/path/
```

Run `./deploy` to upload the files. Now make a request to a URL that hits the server you just uploaded this to.

The script will receive the request, rebuild it using Guzzle and send it on to your target using the same method, body, form data, path & headers.

The response will be returned to you.

### Authentication

The request you send to the proxy will require authentication which is provided in the form of an `x-proxy-auth` header which sends a HMAC calculated using a pre-shared key.

For requests without a body you can calculate the HMAC as follows. Let's assume we're making a request to an endpoint `/get/products` on the eventual target of the proxy.

```
// Note the lack of leading slash in the uri
$uri = "get/products";
$x_proxy_auth = hash_hmac('sha256', $uri, KEY);
```

Assuming we are sending a more complex request, e.g. a POST request with JSON body:

```
// Note the lack of leading slash in the uri
$uri = "get/products";
$data = ['key' => 'value'];
$request_body = json_encode($data);
$auth_string = $uri.$request_body;
$x_proxy_auth = hash_hmac('sha256', $auth_string, KEY);
```

Obviously any body content should work as long as it is concatenated with the URI as shown. Try and avoid leading or trailing whitespace around the body as it is less certain how this could be interpreted by the proxy when it receives the request.

### Skip forwarding headers

If you wish to not forward on certain headers try adding the following definition to the `_config.php` file. The header names should be lower case and comma separated without spaces.

```
define('HEADER_EXCLUDE', 'some-header,another-header');
```
