<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GraphQL Voyager</title>
    <style>
        html,
        body,
        #voyager {
            height: 100%;
            margin: 0;
            overflow: hidden;
            width: 100%;
        }
    </style>
    <link rel="stylesheet" href="/vendor/graphql-voyager/voyager.css">
</head>
<body>
<div id="voyager">Loading...</div>
<script src="/vendor/graphql-voyager/voyager.standalone.js"></script>
<script>
    function introspectionProvider(query) {
        return fetch('/graphql', {
            method: 'post',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ query: query }),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        });
    }

    var introspection = introspectionProvider(GraphQLVoyager.voyagerIntrospectionQuery);

    GraphQLVoyager.renderVoyager(document.getElementById('voyager'), {
        introspection: introspection
    });
</script>
</body>
</html>
