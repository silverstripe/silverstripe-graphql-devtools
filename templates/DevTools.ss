<!DOCTYPE html>
<html>

<head>
    <meta charset=utf-8/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GraphQL IDE | Silverstripe CMS</title>
    <link rel="shortcut icon" href="$resourceURL('silverstripe/graphql-devtools: client/favicon.png')" />
    <link rel="stylesheet" href="$resourceURL('silverstripe/graphql-devtools: client/graphiql.min.css')" />
    <style>
        body {
            margin: 0;
        }

        #schema-selector {
            display: none;
            padding: 6px 16px;
            background: #1a1a2e;
            color: #e0e0e0;
            font-family: sans-serif;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        #schema-selector.visible {
            display: flex;
        }

        #schema-selector select {
            background: #2d2d2d;
            color: #e0e0e0;
            border: 1px solid #555;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

        #graphiql {
            height: 100vh;
        }

        body.has-selector #graphiql {
            height: calc(100vh - 37px);
        }
    </style>
</head>

<body>
    <div id="schema-selector">
        <label for="schema-select">Schema:</label>
        <select id="schema-select"></select>
    </div>
    <div id="graphiql">Loading...</div>

    <!-- GraphiQL standalone bundle (offline-capable, no build step required).
         Versions: React 18.3.1, GraphiQL 3.9.0
         To upgrade: download replacements from unpkg.com and overwrite the files in client/
           https://unpkg.com/react@{version}/umd/react.production.min.js
           https://unpkg.com/react-dom@{version}/umd/react-dom.production.min.js
           https://unpkg.com/graphiql@{version}/graphiql.min.js
           https://unpkg.com/graphiql@{version}/graphiql.min.css -->
    <script src="$resourceURL('silverstripe/graphql-devtools: client/react.production.min.js')"></script>
    <script src="$resourceURL('silverstripe/graphql-devtools: client/react-dom.production.min.js')"></script>
    <script src="$resourceURL('silverstripe/graphql-devtools: client/graphiql.min.js')"></script>
    <script>
        (function () {
            var endpoint = "$GraphiQLEndpoint.RAW";
            var tabs = $GraphiQLTabsJSON.RAW;
            var csrf = "$GraphiQLCSRF.JS";
            var graphiqlRoot = null;

            function makeFetcher(url) {
                return GraphiQL.createFetcher({
                    url: url,
                    headers: { 'X-CSRF-TOKEN': csrf }
                });
            }

            function renderGraphiQL(url) {
                if (!graphiqlRoot) {
                    graphiqlRoot = ReactDOM.createRoot(document.getElementById('graphiql'));
                }
                graphiqlRoot.render(React.createElement(GraphiQL, { fetcher: makeFetcher(url) }));
            }

            if (Array.isArray(tabs) && tabs.length > 0) {
                var selector = document.getElementById('schema-selector');
                var select = document.getElementById('schema-select');
                selector.classList.add('visible');
                document.body.classList.add('has-selector');
                tabs.forEach(function (tab) {
                    var option = document.createElement('option');
                    option.value = tab.endpoint;
                    option.textContent = tab.name;
                    select.appendChild(option);
                });
                select.addEventListener('change', function () {
                    renderGraphiQL(this.value);
                });
                renderGraphiQL(tabs[0].endpoint);
            } else if (endpoint) {
                renderGraphiQL(endpoint);
            }
        })();
    </script>
</body>
</html>
