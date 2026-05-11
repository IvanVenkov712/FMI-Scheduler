var App = window.App || {};

App.api = (function () {
    'use strict';

    function csrfToken() {
        var el = document.querySelector('input[name="_csrf_token"]');
        return el ? el.value : '';
    }

    function request(method, url, body, options) {
        options = options || {};
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        var fetchOpts = { method: method, headers: headers, credentials: 'same-origin' };

        if (body !== undefined && body !== null) {
            if (body instanceof FormData) {
                fetchOpts.body = body;
                if (csrfToken()) body.append('_csrf_token', csrfToken());
            } else {
                headers['Content-Type'] = 'application/json';
                headers['Accept'] = 'application/json';
                if (typeof body === 'object') body._csrf_token = csrfToken();
                fetchOpts.body = JSON.stringify(body);
            }
        }

        if (method === 'GET' || method === 'HEAD') {
            headers['Accept'] = 'application/json';
        }

        fetchOpts.headers = headers;

        return fetch(url, fetchOpts).then(function (response) {
            var contentType = response.headers.get('content-type') || '';
            var isJson = contentType.indexOf('application/json') !== -1;

            if (!response.ok) {
                if (isJson) {
                    return response.json().then(function (data) {
                        var err = new Error(data.message || 'Request failed');
                        err.status = response.status;
                        err.data = data;
                        throw err;
                    });
                }
                var err = new Error('Request failed with status ' + response.status);
                err.status = response.status;
                throw err;
            }

            return isJson ? response.json() : response.text();
        });
    }

    return {
        get: function (url) { return request('GET', url); },
        post: function (url, body) { return request('POST', url, body); },
        put: function (url, body) { return request('PUT', url, body); },
        del: function (url) { return request('DELETE', url, {}); },
        upload: function (url, formData) { return request('POST', url, formData); },
        csrfToken: csrfToken
    };
})();
