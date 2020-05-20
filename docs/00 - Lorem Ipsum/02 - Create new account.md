---
title: Create New Account
method: POST
url: "{{API_URL}}/register"
---

It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).


```request:cURL
curl --location --request POST '{{API_URL}}/register' \
--header 'Content-Type: application/json' \
--data-raw '{
	"username": "vaneves",
	"email": "vaneves@vaneves.com",
	"password": "123456"
}'
```

```request:Python
import requests

url = "{{API_URL}}/register"

payload = "{\"username\": \"vaneves\", \"email\": \"vaneves@vaneves.com\", \"password\": \"123456\"\n}"
headers = {
  'Content-Type': 'application/json'
}

response = requests.request("POST", url, headers=headers, data = payload)

print(response.text.encode('utf8'))
```

```response:200
{
	"success": true
}
```

```response:400
{
	"error": "Username already exists"
}
```