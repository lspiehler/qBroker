<!DOCTYPE html>
<html>
    <head>
        <title>LCMC Self-Service Printing</title>
        <script src="/include/js/common.js"></script>
        <script>
            window.onload = function() {
                let options = {
                    path: '/api/mappings/<?php echo $_GET['hostname']; ?>/<?php echo $_GET['username']; ?>',
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
                httpRequest({options: options}, function(err, resp) {
                    if(err) {
                        alert(err);
                    } else {
                        console.log(resp);
                        let body = document.body;
                        let table = document.createElement("table");
                        let allprinters = [];
                        for(let i = 0; i < resp.body.print_mappings.mapping.length; i++) {
                            let row = table.insertRow(table.rows.length);
                            let cell = row.insertCell(0);
                            let a = document.createElement("a");
                            let queue = resp.body.print_mappings.mapping[i].queue + "@" + resp.body.print_mappings.mapping[i].server;
                            allprinters.push(queue);
                            a.href = "printer://" + queue;
                            a.innerText = resp.body.print_mappings.mapping[i].queue;
                            cell.appendChild(a);
                        }
                        body.appendChild(table);
                        let button = document.createElement('button');
                        button.innerText = "Map All Printers";
                        button.addEventListener('click', function(e) {
                            window.location = "printer://" + allprinters.join(";");
                        });
                        body.appendChild(button);
                        window.location = "printer://" + allprinters.join(";");
                    }
                });
            }
        </script>
    </head>
    <body>

    </body>
</html>