---
title: Patch example
method: PATCH
url: "{{API_URL}}/items/:id"
---

It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).

```param:path
- name: "id"
  type: "int"
  required: true,
  description: "ID"
```

```param:body
- name: "name"
  type: "string"
  required: true,
  description: "First and last name"
```

```request:cURL
curl --location --request PATCH '{{API_URL}}/items/:id' \
--header 'Content-Type: application/json' \
--data-raw '{
	"name": "other example",
}'
```

```request:Python
import requests

url = "{{API_URL}}/items/:id"

payload = "{\"name\": \"other example\"}"
headers = {
  'Content-Type': 'application/json'
}

response = requests.request("PATCH", url, headers=headers, data = payload)

print(response.text.encode('utf8'))
```

```response:200
{
	"success": true
}
```

```response:402
{
	"error": "name is required"
}
```

```response:404
{
	"error": "item not found"
}
```