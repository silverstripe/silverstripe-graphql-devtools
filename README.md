# Dev tools for silverstripe-graphql

[![CI](https://github.com/silverstripe/silverstripe-graphql-devtools/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-graphql-devtools/actions/workflows/ci.yml)

Tools to help developers building new applications on SilverStripe’s GraphQL API

## Installation

```sh
composer require --dev silverstripe/graphql-devtools
```

## What it does

This module adds an implementation of [graphiql](https://github.com/graphql/graphiql), an in-browser IDE for GraphQL servers. It provides browseable documentation of your schema, as well as autocomplete and syntax-checking of your queries.

### Accessing the IDE 

It can be accessed at `/dev/graphql/ide`.

This is because GraphQL 4+ has its own `DevelopmentAdmin` controller.

The GraphQL v4 version of the module allows you to clear your schema by running `sake graphql:clear`.

## Security

By default, the tool has the same restrictions as other development tools like `/dev/build`:

 * In "dev" mode, it's available without authentication
 * In "test" and "live" mode, it requires ADMIN permissions
 * It's installed with `composer require --dev` by default. In most deployment contexts that'll mean it's not available on environments in "test" or "live" modes

 
 ## Configuration
 
 In most installations of SilverStripe, there are at least two GraphQL servers running - one
 for the admin (`admin/graphql`) and one for the user space (`/graphql`). By default, only
 the `default` schema will show, but this is configurable.
 
 ### Showing multiple schemas
 
 You can configure the visible schemas in the controller.

Show a select list of schemas 
 ```yaml
SilverStripe\GraphQLDevTools\Controller:
  # show two schemas
  schemas:
    - default
    - admin
  # default schema that is selected
  default_schema: admin 
```

Show all schemas
 ```yaml
SilverStripe\GraphQLDevTools\Controller:
  # show two schemas
  schemas: '*'
  default_schema: default 
```

Further, you can override the config in the request, by using `?schema=<schemaName>`,
e.g. `http://example.com/dev/graphql/ide?schema=mySchema`.

## If you're using a custom controller for your GraphQL endpoint

The IDE finds schemas by checking `Director` for routes that map to a `SilverStripe\GraphQL\Controller` instance.
If for some reason you're using a custom controller, you might get an error: "Could not find your default schema 'default'. You will need to add one to the `SilverStripe\Control\Director.rules` config setting."

To avoid this, you can explicitly map your graphql route to a schema in your Director config:

```yaml
SilverStripe\Control\Director:
  rules:
    graphql:
      Controller: '%$MyCustomController'
      Schema: default
```

## GraphQL schema initialise task

This module provides a `GraphQLSchemaInitTask` task to initialise a basic GraphQL schema to get you started. It will create configuration files for your schema and a basic resolver. Specifically it will create:

- `app/_config/graphql.yml`
- `app/_graphql` containing several yml files
- `src/GraphQL/Resolvers.php`

You must be in CLI mode to use this task

To view help for the task to see what options are available:

```bash
vendor/bin/sake tasks:GraphQLSchemaInitTask --help
```

To run the task with with minimal options:

```bash
vendor/bin/sake tasks:GraphQLSchemaInitTask --namespace=App
```


## Upgrading and maintaining the IDE

The library running the IDE is [GraphiQL](https://github.com/graphql/graphiql).
It is served from your local environment as an exposed resource, with no CDN
dependency and no build chain required. The bundled files are:

* `client/react.production.min.js`
* `client/react-dom.production.min.js`
* `client/graphiql.min.js`
* `client/graphiql.min.css`

The benefits of this approach are:

* It allows offline use
* It does not require setting up a build chain or installing NPM dependencies

To upgrade GraphiQL, download the new versions from [unpkg.com](https://unpkg.com)
and overwrite the files in `client/`. The exact URLs and currently bundled versions
are noted in the comment above the `<script>` tags in `templates/DevTools.ss`.
