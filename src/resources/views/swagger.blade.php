<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
</head>

<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '/swagger.json', // Ссылка на файл с описанием API
                dom_id: '#swagger-ui',
            });
        };
    </script>
</body>

</html>