# Dev tools for silverstripe-graphql
Tools to help developers building new applications on SilverStripe’s GraphQL API

## Installation
```
$ composer require silverstripe/graphql-devtools
```

## Requirements

* [silverstripe/graphql](https://github.com/silverstripe/silverstripe-graphql)

## What it does

This module adds an implementation of [graphiql](https://github.com/graphql/graphiql), an in-browser IDE for GraphQL servers. It provides browseable documentation of your schema, as well as autocomplete and syntax-checking of your queries.
 
 This tool is available in **dev mode only**. It can be accessed at `/dev/graphiql/`.
 
 <img src="https://github.com/graphql/graphiql/raw/master/resources/graphiql.png">
 
 ## Setting the endpoint
 
 In most installations of SilverStripe, there are at least two GraphQL servers running -- one
 for the admin (`admin/graphql`) and one for the user space (`/graphql`). The IDE can only browse
 one schema at a time, so it must be configured with a route to use.
 
 By default, it will use the `/graphql` route. This can be changed in the `SilverStripe\GraphQLDevTools\GraphiQLController.default_route`
 setting. Otherwise, you can specify an endpoint per request using the `GET` variable `endpoint`, e.g.
 `/dev/graphiql?endpoint=admin/graphql`.
 