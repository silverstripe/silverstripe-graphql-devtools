<!DOCTYPE html>
<html>

<head>
    <meta charset=utf-8/>
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, minimal-ui">
    <title>GraphQL IDE | Silverstripe CMS</title>
    <link rel="shortcut icon" href="$resourceURL('silverstripe/graphql-devtools: client/favicon.png')" />
    <% require javascript('silverstripe/graphql-devtools: client/bundle.js') %>
</head>

<body>
<div id="root">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Open Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            color: rgba(0,0,0,.8);
            line-height: 1.5;
            height: 100vh;
            letter-spacing: 0.53px;
            margin-right: -1px !important;
        }

        #root {
            height: 100%;
        }

        html, body, p, a, h1, h2, h3, h4, ul, pre, code {
            margin: 0;
            padding: 0;
            color: inherit;
        }

        a:active, a:focus, button:focus, input:focus {
            outline: none;
        }

        input, button, submit {
            border: none;
        }

        input, button, pre {
            font-family: 'Open Sans', sans-serif;
        }

        code {
            font-family: Consolas, monospace;
        }
        body {
            background-color: rgb(23, 42, 58);
            font-family: Open Sans, sans-serif;
            height: 90vh;
        }

        #root {
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading {
            font-size: 32px;
            font-weight: 200;
            color: rgba(255, 255, 255, .6);
            margin-left: 20px;
        }

        img {
            width: 78px;
            height: 78px;
        }

        .title {
            font-weight: 400;
        }
    </style>
    <img src="$resourceURL('silverstripe/graphql-devtools: client/logo.png')" alt=''>
    <div class="loading"> Loading
        <span class="title">GraphQL Playground</span>
    </div>
</div>
<script>window.addEventListener('load', function (event) {
    GraphQLPlayground.init(document.getElementById('root'), {
        headers: {
            'X-CSRF-TOKEN': '$SecurityID',
        },
        endpoint: '$Endpoint',
        settings: {
            'request.globalHeaders': {
                'X-CSRF-TOKEN': '$SecurityID'
            },
            'request.credentials': 'include',
        },
        <% if $TabsJSON %>
        tabs: $TabsJSON.RAW
        <% end_if %>
    })
})</script>
</body>
</html>
