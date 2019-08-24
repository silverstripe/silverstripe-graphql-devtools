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
 
It can be accessed at `/dev/graphiql/`.

By default, the tool has the same restrictions as other development tools like `dev/build`:

 * In "dev" mode, it's available without authentication
 * In "test" and "live" mode, it requires ADMIN permissions
 * It's installed with `composer require --dev` by default. In most deployment contexts that'll mean it's not available on environments in "test" or "live" modes

 
 <img src="https://github.com/graphql/graphiql/raw/master/resources/graphiql.png">
 
 ## Setting the endpoint
 
 In most installations of SilverStripe, there are at least two GraphQL servers running -- one
 for the admin (`admin/graphql`) and one for the user space (`/graphql`). The IDE can only browse
 one schema at a time, so it must be configured with a route to use.
 
 By default, it will use the `/graphql` route. This can be changed in the `SilverStripe\GraphQLDevTools\GraphiQLController.default_route`
 setting. Otherwise, you can specify an endpoint per request using the `GET` variable `endpoint`, e.g.
 `/dev/graphiql?endpoint=admin/graphql`.
 
## Showing the schema

To see a text representation of your shema, you can use the `/dev/schemaprint?schema=mySchema` URL.
