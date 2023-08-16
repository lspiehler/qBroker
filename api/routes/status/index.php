<!DOCTYPE html>
<html>
    <head>
        <title>qBroker Server Status</title>
        <script src="/include/js/common.js"></script>
        <script>
            window.onload = function() {
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
                            httpRequest({options: options}, function(err, resp) {
                                if(err) {
                                    //alert(err);
                                    //console.log(err);
                                    checkcells[i].style.color = "red";
                                    checkcells[i].innerText = "" . err;
                                } else {
                                    //console.log(resp);
                                    if(resp.statusCode == 200 && resp.body.result == "success") {
                                        checkcells[i].style.color = "green";
                                    } else {
                                        checkcells[i].style.color = "red";
                                    }
                                    checkcells[i].innerText = resp.body.message || resp.body.data;
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
    </body>
</html>