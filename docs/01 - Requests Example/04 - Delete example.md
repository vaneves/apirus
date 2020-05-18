---
title: Delete example
method: DELETE
url: "http://example.com/api/items/:id"
---

It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).


```request:cURL
curl --location --request DELETE 'http://example.com/api/items/:id' \
--header 'Content-Type: application/json' \
--data-raw '{
	"name": "other example",
}'
```

```request:Python
import requests

url = "http://example.com/api/items/:id"

headers = {
  'Content-Type': 'application/json'
}

response = requests.request("DELETE", url, headers=headers)

print(response.text.encode('utf8'))
```

```response:200
{
	"success": true
}
```

```response:404
{
	"error": "item not found"
}
```