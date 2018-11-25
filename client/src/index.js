/* global GRAPHQL_ROUTE, SECURITY_ID */
import React from 'react';
import {render} from 'react-dom';
import GraphiQL from 'graphiql';
import fetch from 'isomorphic-fetch';
import 'graphiql/graphiql.css';

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

function changeGraphQLEndpoint(event) {
    window.location.href = '/dev/graphiql?endpoint=' + encodeURI(event.target.value);
}

document.addEventListener('DOMContentLoaded', function () {
	render(
        <GraphiQL fetcher={graphQLFetcher}>
            <GraphiQL.Logo>SilverStripe Graph<i>i</i>QL</GraphiQL.Logo>
            {GRAPHQL_ROUTES.length > 1 && <GraphiQL.Toolbar>
                <select name="endpoint" onChange={changeGraphQLEndpoint} value={GRAPHQL_ROUTE}>
                    {GRAPHQL_ROUTES.map( (route) =>
                        <option key={route} value={route}>{route}</option>
                    )}
                </select>
            </GraphiQL.Toolbar>}
        </GraphiQL>,
		document.getElementById('graphiql')
	);
});
