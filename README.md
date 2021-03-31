# Dev tools for silverstripe-graphql
Tools to help developers building new applications on SilverStripe’s GraphQL API

## Installation
```
$ composer require --dev silverstripe/graphql-devtools
```

## Requirements

* [silverstripe/graphql](https://github.com/silverstripe/silverstripe-graphql)

## What it does

This module adds an implementation of [graphiql](https://github.com/graphql/graphiql), an in-browser IDE for GraphQL servers. It provides browseable documentation of your schema, as well as autocomplete and syntax-checking of your queries.

### Accessing the IDE 

**In GraphQL 3.x**, it can be accessed at `/dev/graphiql/`.

**In GraphQL 4.x**, it can be accessed at `/dev/graphql/ide`.

This is because GraphQL 4 has its own `DevelopmentAdmin` controller.

## Security

By default, the tool has the same restrictions as other development tools like `dev/build`:

 * In "dev" mode, it's available without authentication
 * In "test" and "live" mode, it requires ADMIN permissions
 * It's installed with `composer require --dev` by default. In most deployment contexts that'll mean it's not available on environments in "test" or "live" modes

 
 <img src="https://github.com/graphql/graphiql/raw/master/resources/graphiql.png">
 
 ## Configuration
 
 In most installations of SilverStripe, there are at least two GraphQL servers running -- one
 for the admin (`admin/graphql`) and one for the user space (`/graphql`). By default, only
 the `default` schema will show, but this is configurable.
 
 ### Showing multiple schemas
 
 **Important**: Due to a limitation in the GraphQL Playground library, showing multiple schemas
 will disable the persistence of IDE state across page refreshes.
 
 **Also important**: When changing the available schemas, be sure to clear the local storage in your browser, otherwise, you'll get inconsistent state.
 
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
If for some reason you're using a custom controller, you might get an error: "Could not find your default schema 'default'. You will need to add one to the SilverStripe\Control\Director.rules config setting."

To avoid this, you can explicitly map your graphql route to a schema in your Director config:

```
SilverStripe\Control\Director:
  rules:
    graphql:
      Controller: '%$MyCustomController'
      Schema: default
```

## Upgrading and maintaining the IDE
 
 The library running the IDE is [GraphQL Playground](https://github.com/graphql/graphql-playground).
 It is served from your local environment as an exposed resource. The
 setup is based on their "HTML Page" example [seen here](https://github.com/graphql/graphql-playground#as-html-page), which uses remote bundle files served from a CDN. This repository
 uses a manually created bundle file copied directly from the CDN.
 This may seem like a convoluted approach, but the main benefits are:
 
 * It allows offline use
 * It does not require setting up a build chain or installing NPM dependencies
 * There is no need for SRI protection
 
 To upgrade GraphQL Playground, refer to the example linked above and use their 
 CDN to download the latest distribution and drop it into this repository. Be sure
 to update the comment at the top of the `bundle.js` file to track the URL it was
 downloaded from.
 
