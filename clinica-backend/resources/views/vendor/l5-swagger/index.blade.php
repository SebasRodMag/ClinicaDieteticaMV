<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>
    <!-- En lugar de l5_swagger_asset, USAMOS CDN para que cargue desde https-->
    {{-- --}}
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">

    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5/favicon-16x16.png" sizes="16x16" />

    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }
    </style>

    @if(config('l5-swagger.defaults.ui.display.dark_mode'))
        {{-- aquí dejas TODO tu CSS de dark mode tal cual lo tienes --}}
    @endif
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>
    <div id="swagger-ui"></div>


    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function () {
            const urls = [];

            @foreach($urlsToDocs as $title => $url)
                urls.push({ name: "{{ $title }}", url: "{{ $url }}" });
            @endforeach

            const ui = SwaggerUIBundle({
                dom_id: '#swagger-ui',
                urls: urls,
                "urls.primaryName": "{{ $documentationTitle }}",
                operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
                configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
                validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
                oauth2RedirectUrl: "{{ route('l5-swagger.' . $documentation . '.oauth2_callback', [], $useAbsolutePath) }}",

                requestInterceptor: function (request) {
                    request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                    return request;
                },

                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],

                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],

                layout: "StandaloneLayout",
                docExpansion: "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
                deepLinking: true,
                filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
                persistAuthorization: {!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}
            });

            window.ui = ui;
            @php
                $schemes = config('l5-swagger.defaults.securityDefinitions.securitySchemes') ?? [];
                $types = is_array($schemes) ? array_column($schemes, 'type') : [];
            @endphp
    
            @if(in_array('oauth2', $types, true))
                ui.initOAuth({
                    usePkceWithAuthorizationCodeGrant: {!! config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') ? 'true' : 'false' !!}
                    });
            @endif
        }
    </script>
</body>

</html>