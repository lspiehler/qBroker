<!DOCTYPE html>
<html>
    <head>
        <title>qBroker Server Status</title>
        <script src="/include/js/common.js"></script>
        <script>
            window.onload = function() {
                let duoptions = {
                    path: '/api/status/disk-usage',
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
                httpRequest({options: duoptions}, function(err, resp) {
                    if(err) {
                        alert(err);
                    } else {
                        let du = document.getElementById('diskusage');
                        if(resp.statusCode == 200 && resp.body.result == "success") {
                            du.innerText = "Disk Usage:\r\n\r\n" + resp.body.message;
                        } else {
                            du.innerText = resp.body.message;
                        }
                    }
                });

                let options = {
                    path: '/api/status/list',
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
                httpRequest({options: options}, function(err, resp) {
                    if(err) {
                        alert(err);
                    } else {
                        let table = document.getElementById('checktable');
                        //console.log(resp.body.data);
                        let checkcells = [];
                        for(let i = 0; i < resp.body.data.checks.length; i++) {
                            let row = table.insertRow();
                            let cell1 = row.insertCell();
                            let cell2 = row.insertCell();
                            cell1.innerText = resp.body.data.checks[i];
                            checkcells.push(cell2);
                            //console.log(resp.body.data[i]);
                        }
                        for(let i = 0; i < resp.body.data.checks.length; i++) {
                            let options = {
                                path: '/api/status/' + resp.body.data.checks[i],
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            }
                            httpRequest({options: options}, function(err, status) {
                                if(err) {
                                    //alert(err);
                                    //console.log(err);
                                    checkcells[i].style.color = "red";
                                    checkcells[i].innerText = "" . err;
                                } else {
                                    if(status.statusCode == 200 && status.body.result == "success") {
                                        checkcells[i].style.color = "green";
                                        if(resp.body.data.checks[i] == 'config') {
                                            let config = document.getElementById('config');
                                            config.innerText = "Config:\r\n\r\n" + JSON.stringify(status.body.data, null, 2);
                                        }
                                        if(resp.body.data.checks[i] == 'dns') {
                                            let dns = document.getElementById('dns');
                                            dns.innerText = "DNS SRV Records:\r\n\r\n" + JSON.stringify(status.body.data, null, 2);
                                        }
                                    } else {
                                        checkcells[i].style.color = "red";
                                    }
                                    checkcells[i].innerText = status.body.message || status.body.data;
                                }
                            });
                        }
                    }
                });
            }
        </script>
    </head>
    <body>
        <table border=1 id="checktable">

        </table>
        <pre id="diskusage"></pre>
        <pre id="dns"></pre>
        <pre id="config"></pre>
    </body>
</html>