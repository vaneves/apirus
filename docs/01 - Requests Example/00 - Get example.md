---
title: Get example
method: GET
url: "{{API_URL}}/items"
---

Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.

```request:cURL
curl --location --request GET '{{API_URL}}/items' \
--header 'Content-Type: application/json' 
```

```request:Python
import requests

url = "{{API_URL}}/items"

headers = {
  'Content-Type': 'application/json'
}

response = requests.request("GET", url, headers=headers)

print(response.text.encode('utf8'))
```

```response:200
[{
    "id": 1,
    "name": "example 1"
}, {
    "id": 2,
    "name": "example 2"
}, {
    "id": 3,
    "name": "example 3"
}]
```