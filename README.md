## Installation instructions

Create a file `_config.php`:

```
<?php

define('BASE', 'https://domain-to-proxy.com/path/');
```

Create a file `_deploy`:

```
user@someserver.com:/var/www/path/
```

Run `./deploy` to upload the files. Now make a request to a URL that hits the server you just uploaded this to.

The script will receive the request, rebuild it using Guzzle and send it on to your target using the same method, body, form data and path.

The response will be returned to you.

### To do

* Proxy headers
* Authentication on proxy (so the proxy can authenticate the original client)
