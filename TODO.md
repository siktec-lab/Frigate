# TODO

## V2: Task pool:
- [ ] Describe all routes command needs to be improved.
- [ ] Implement Frigate V2 Cli tool.
- [ ] Add php-cs and unit tests.
- [ ] support returns negotiation of */* and text/* etc....
- [ ] Implement accept all and restriction in headers CORS policy 
- [ ] Implement parsedown for documentation generating
- [ ] Global templates - include basic twig templates as helpers.
- [ ] Versioning with included files in page builder
- [ ] Add front-end global App builder.
- [ ] Page builder should take additional default context in constructor
- [ ] Page builder compile should take additional context -> const context -> defined context -> compile context.

## In Progress
- [ ] Make all request no matter what redirect to index.php this will reduce the .htaccess dependency.
- [ ] Authorization - Implement Basic, Bearer, JWT, OAuth, OAuth2, Session.
- [ ] Path args - Implement default value...
- [ ] Implement static file loader - a folder is mapped to an endpoint optional php exec.


## Completed
- [x] File server for chunking
- [x] Implement swagger
- [x] Implement Patch handlers.
- [x] ....