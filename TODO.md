# TODO

## V2: Task pool:
- **General**:
    - [ ] Implement Frigate V2 Cli tool.
    - [ ] Add phpcs.
    - [ ] support returns negotiation of \*/\* and text/* etc....
    - [ ] Implement accept all and restriction in headers CORS policy 
    - [ ] Implement parsedown for documentation generating

- **Routing Paths**:
    - [ ] Describe all routes command needs to be improved.
    - [ ] Test for max path length (i.e. 55 nested paths parts)

- **templating**:
    - [ ] Global templates - include basic twig templates as helpers.

- **Pages**:
    - [ ] Versioning with included files in page builder
    - [ ] Page builder should take additional default context in constructor
    - [ ] Page builder compile should take additional context -> const context -> defined context 


## In Progress
- **General**:
    - [ ] Make all request no matter what redirect to index.php this will reduce the .htaccess dependency.
    - [ ] Authorization - Implement Basic, Bearer, JWT, OAuth, OAuth2, Session.
    - [ ] Path args - Implement default value...
    - [ ] Implement static file loader - a folder is mapped to an endpoint optional php exec.

- **Routing Paths**:


## Completed
- **General**:
    - [x] File server for chunking
    - [x] Implement swagger
    - [x] Implement Patch handlers.
    - [x] Add unit tests.

- **Routing Paths**:
