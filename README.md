# Task Followup

Task followup web application is a RESTful APIs for managing tasks.

## Features

- Register, login to the system and reset your password.
- Get your profile basic info.
- Create task for a specific date.
- List your upcoming tasks.
- List today's tasks.
- Get a specific task detail.
- Filter all the list collections with every fields.
- Update every fields of a specific task, complete a task.
- Delete a specific task.

## Tech

- [PHP v7.4.19]
- [Symfony v5.2.8]
- [Elasticsearch v6.8.15]
- [PHPUnit v9.5.4]
- [Json Web Token]

## Installation

```sh
git clone git@github.com:tkorkmazer/task-followup.git
cd task-followup
docker-compose up --build -d
docker exec -it php /bin/bash
composer install
```

## Swagger

https://app.swaggerhub.com/apis/tkorkmazer/task-followup/1.0#
