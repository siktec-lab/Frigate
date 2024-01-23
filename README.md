# Frigate V2

Frigate is a lightweight web application framework. It is designed to be easy, and extremely fast with the ability to scale up to complex applications.

## Quick Links:
- [DOCUMENTATION](https://siktec-lab.github.io/frigate/)
- [Installation](#installation)
- [Features](#features)
- [Milestones](#future-milestones)


## Features:
- Easy and Powerfull routing system.
- Builtin Database class for MySQL.
- Build an API
- Serve Files, Pages, JSON.
- Build Hybrid application (e.g an API with a administration panel).
- PHP - 8.
- Easy framework syntax.
- Very Flexible design.


## Installation

Install Frigate with composer:

```bash
composer require siktec/qdm
```

Use a bootstrap project to get started:

```bash
composer create-project siktec/frigate-bootstrap
```

## Milestones:
- [v] support returns negotiation of */* and text/* etc....

- [v] File server for chunking

- [-] Implement accept all and restriction in headers CORS policy 

- [-] Implement parsedown for documentation generating

- [v] Implement swagger

- [-] Global templates - include basic twig templates as helpers.

- [-] Versioning with included files in page builder

- [v] Implement Patch handlers.

- [-] Add front-end global App builder.

- [-] Implement static file loader - a folder is mapped to an endpoint optional php exec.

- [-] Page builder should take additional default context in constructor

- [-] Page builder compile should take additional context -> const context -> defined context -> compile context.
