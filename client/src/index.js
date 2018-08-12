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

document.addEventListener('DOMContentLoaded', function () {
	render(
		<GraphiQL fetcher={graphQLFetcher} />,
		document.getElementById('graphiql')
	);
});
