function httpRequest(params, callback) {
    //console.log(params);
    var request = new XMLHttpRequest();
    request.open(params.options.method, params.options.path, true);
    if(params.options.headers) {
        let headerkeys = Object.keys(params.options.headers);
        for(let i = 0; i <= headerkeys.length - 1; i++) {
            request.setRequestHeader(headerkeys[i], params.options.headers[headerkeys]);
        }
    }
    
    request.onload = function() {
        //if (request.status >= 200 && request.status < 301) {
        if(request.status == 401) {
            var resp = {
                id: params.id,
                options: params.options,
                statusCode: request.status,
                body: request.responseText
            }
            callback('401', resp);
        } else {
            try {
                let body = JSON.parse(request.responseText);
                var resp = {
                    statusCode: request.status,
                    body: body
                }
                callback(null, resp);
            } catch(e) {
                var resp = {
                    id: params.id,
                    options: params.options,
                    statusCode: request.status,
                    body: request.responseText
                }
                callback(e, resp);
            }
        }
    };

    request.onerror = function(e) {
        callback(e, null);
        return;
        // There was a connection error of some sort
    };

    if(params.options.method=='POST') {
        request.send(JSON.stringify(params.body));
    } else {
        request.send();
    }
}