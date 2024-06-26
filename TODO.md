# TODO

## V2: Task pool:
- **General**:
    - [ ] Implement Frigate V2 Cli tool.
    - [ ] Add phpcs.
    - [ ] support returns negotiation of \*/\* and text/* etc....
    - [ ] Implement accept all and restriction in headers CORS policy 
    - [ ] Add parsedown for markdown parsing as part of the templating engine.

- **Routing Paths**:
    - [ ] Describe all routes command needs to be improved - I mean `dumpRoutes` command.
    - [ ] Test for max path length (i.e. 55 nested paths parts)

- **Endpoints**:
    - [ ] Improve endpoint for lazy loading of classes.

- **templating**:
    - [ ] Global templates - include basic twig templates as helpers.

- **Pages**:
    - [ ] Versioning with included files in page builder
    - [ ] Page builder should take additional default context in constructor
    - [ ] Page builder compile should take additional context -> const context -> defined context 


## In Progress
- **General**:
    - [ ] Authorization - Implement Basic, Bearer, JWT, OAuth, OAuth2, Session.
    - [ ] Implement static file loader - a folder is mapped to an endpoint optional php exec.

- **Middleware**:
    - [ ] Implement Middleware for routes.


## Completed
- **General**:
    - [x] File server for chunking
    - [x] Implement swagger
    - [x] Implement Patch handlers.
    - [x] Add unit tests.
    - [x] Implement mkdocs for documentation generating
    - [x] Make all request no matter what redirect to index.php this will reduce the .htaccess dependency.

- **Routing Paths**:
