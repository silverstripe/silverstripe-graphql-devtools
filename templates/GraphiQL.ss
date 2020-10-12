<!DOCTYPE html>
<html lang="en">
<head>
    <% base_tag %>
    <title>Silverstripe CMS GraphQL IDE</title>
    <link href="https://unpkg.com/graphiql/graphiql.min.css" rel="stylesheet" />
    <style type="text/css">
        #route {
            position: absolute;
            z-index: 100;
            right: 100px;
            top: 10px;
            padding: 5px 10px;
        }
    </style>
</head>
<body style="margin: 0;">
$RouteSwitcher.Field
<div id="graphiql" style="height: 100vh;"></div>
<script>
    var SECURITY_ID = '$SecurityID';
    var GRAPHQL_ROUTE = '$RouteSwitcher.Value';
</script>
<script crossorigin src="https://unpkg.com/react/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom/umd/react-dom.production.min.js"></script>
<script crossorigin src="https://unpkg.com/graphiql/graphiql.min.js"></script>
<script>
    var router = document.getElementById('route');
    router.addEventListener('change', function (event) {
        window.location.href = '/dev/graphql/ide?endpoint=' + encodeURI(event.target.value);
    });
    function graphQLFetcher(graphQLParams) {
        const baseURL = document.querySelector('base').href;
        const headers = {
            'Content-Type': 'application/json',
        };
        if (SECURITY_ID) {
            headers['X-CSRF-TOKEN'] = SECURITY_ID;
        }

        return fetch(`${baseURL}${GRAPHQL_ROUTE}/`, {
            method: 'post',
            headers,
            body: JSON.stringify(graphQLParams),
            credentials: 'same-origin'
        }).then(response => response.json());
    }
    ReactDOM.render(
        React.createElement(GraphiQL, {
            fetcher: graphQLFetcher,
            onToggleDocs(open) {
                router.style.right = open ? '370px' : '100px';
            }
        }),
        document.getElementById('graphiql'),
        function () {
            // Hack to extract the state out of the GraphQL component.
            if (document.querySelector('.docExplorerWrap')) {
                router.style.right = '370px';
            }
        }
    );
</script>
</body>
</html>
