# Apirus - Doc Generator

![Apirus](https://user-images.githubusercontent.com/146581/82272220-50305680-9950-11ea-92d5-38cbe914d98f.png)

PHP application to create beautiful rest API documentation using markdown. Inspired by [readme.com](https://readme.com/).

## Installation

To install, run the command:

```
composer create-project --prefer-dist vaneves/apirus
```

After downloading, go to the application directory and copy the `.env.example` file as `.env`.

```
cp .env.example .env
```

## Building

To compile the HTML output, just run the following command:

```
php apirus
```

### .env Configuration

You can configure settings to compile the API, to do so just edit the `.env` file and change the value of the variables.

```
API_URL=http://example.com/api

SOURCE=
DIST=
THEME=
HIGHTLIGHT=
```

| Variable   | Description             | Default          |
|------------|-------------------------|------------------|
| API_URL    | Base URL of requests    |                  |
| SOURCE     | Path the markdown files | ./docs           |
| DIST       | Destination folder      | ./public         |
| THEME      | Theme name              | ./themes/default |
| HIGHTLIGHT | Highlight style         | dark             |

The `API_URL` variable is used to not repeat the complete URL of the request in all markdown files. You can use it `{{API_URL}}`, for example:

````
---
url: "`{{API_URL}}/api/items"
---

```request:cURL
curl --location --request GET '`{{API_URL}}/api/items' \
--header 'Content-Type: application/json' 
```
````

### Optional Arguments

You can enter arguments for changing the build. The accepted arguments are:

| Argument  | Short | Description              | Default      |
|-----------|-------|--------------------------|--------------|
| help      |       | Prints a usage statement |              |
| watch     |       | Watching files changes   |              |
| src       | s     | Path the markdown files  | `SOURCE`     |
| dist      | d     | Destination folder       | `DIST`       |
| theme     | t     | Theme name               | `THEME`      |
| highlight | h     | Highlight style          | `HIGHTLIGHT` |

Example:

```
php apirus --src my-docs --theme mytheme -h monokai
```

**If an argument is defined when compiling, it will overwrite the values defined in `.env`.**

### Watching Files

In development environment you can use the `--watch` argument so that Apirus can see the directory where the markdown files are, as soon as there is a change (create, change or delete), it will automatically rebuild. For example:

```
php apirus --watch
```

You can pass other arguments as usual, for example:

```
php apirus --src ../my-docs --theme ../mytheme --watch
```

## Creating Documentation

By default, the directory where the markdown files are located is in `docs`. But you can change. Within that directory you must create other directories, where each one will correspond to an item in the menu, for example:

```
docs/
├── 00 - Getting started
|   ├── 00 - Description.md
|   └── 01 - Another section.md
└── 01 - Account
    ├── 00 - Auth.md
    ├── 01 - Register.md
    └── 02 - Recover password.md
```

The directory name will be used for the menu section title. But the numbering will be removed, as it is only used for ordering. For example `00 - Getting started` will generate the title` Getting started`.

In the directories you will create a file for each section. The file name is ignored if it has the `title` meta inside it. But if you don't have the `title` meta, the file name will be used in the menu. For example, `01 - Another section.md` will have the item in the` Another section` menu.

### Meta

"Meta" are used to build a section of your documentation. It is optional to inform the meta, but if defined, it must be of the following structure:

```yaml
---
title: Get example
method: GET
url: "http://example.com/api/items"
---
```

As I said, all are optional. If you leave the `title` blank, the file name without the format and initial numbering (used for sorting) will be used. If you leave the `method` or` url` blank, this information will not be rendered.

**The "meta" must be entered at the beginning of the file.**

### Requests

You can define several examples of requests, in several languages. It is somewhat similar to the code block, but we use the word `request` and in front of it the name of the language. For example:

````
```request:cURL
curl --location --request GET 'http://example.com/api/items' \
--header 'Content-Type: application/json' 
```
````

````
```request:Python
import requests
url = "http://example.com/api/items"
headers = {
  'Content-Type': 'application/json'
}
response = requests.request("GET", url, headers=headers)
print(response.text.encode('utf8'))
```
````

Each language block will be a tab with an example request on the interface. You can place a request block anywhere in the file.

### Reponses

You can define examples of request responses. It is similar to a block of code, but this time you enter the HTTP code of the response. For example:

````
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
````

````
```response:401
{
	"error": "Invalid token"
}
```
````

Each block will be a tab with a response example. You can place a response block anywhere in the file.

### Description

Any information other than the meta, requisition block or return block, will be part of the section description. You can use any markdown markup as per the [Parsedown](https://github.com/erusev/parsedown) library.