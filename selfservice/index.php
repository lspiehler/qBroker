<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(! $json = @file_get_contents(__DIR__ . '/../config.js'))
    throw new Exception ('/../config.js does not exist');
$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

$admin = false;
if(array_key_exists('OIDC_CLAIM_groups', $_SERVER)) {
    if(strtoupper($_SERVER['OIDC_CLAIM_groups'])==strtoupper($config['admin_group_name'])) {
        $admin = true;
    }
}

$computername = '';
if(array_key_exists('computername', $_GET)) {
    $computername = $_GET['computername'];
}

?>
<!--https://bootstrap-autocomplete.readthedocs.io/en/latest/-->
<!DOCTYPE html>
<html>
    <head>
        <title>LCMC Self-Service Printing</title>
        <script src="/include/js/common.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha384-xBuQ/xzmlsLoJpyjoggmTEz8OWUFM0/RC5BsqQBDX2v5cMvDHcMakNTNrHIW2I5f" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </head>
    <body>
        <div class="container">
            <div class="row align-items-center vh-100">
                <div class="col-xl-6 col-lg-6 col-md-8 col-sm-12 col-12 mx-auto text-center">
                    <img width="400px;" src="/include/img/LCMC_Health_Logo.jpg" />
                </div>
            </div>
            <div class="row align-items-center vh-100">
                <div class="col-xl-6 col-lg-6 col-md-8 col-sm-12 col-12 mx-auto text-center">
                    <h1>LCMC Self-Service Printing</h1>
                </div>
            </div>
            <div class="row align-items-center vh-100">
                <div class="col-xl-6 col-lg-6 col-md-8 col-sm-12 col-12 mx-auto">
                    <div class="card shadow border">
                        <div class="card-body d-flex flex-column align-items-center">
                            <form id="upform">
                                <div class="form-group">
                                    <label for="computername">Computer Name</label>
                                    <input id="computername" aria-describedby="computernameHelp" placeholder="Failed to find your computer name" class="form-control" readonly value="<?php echo $computername ?>" style="width: 400px;"/>
                                </div>
                                <label for="printerlist">Printers</label>
                                <div class="form-group">
                                    <select id="printerlist" aria-describedby="printerlistHelp" class="js-example-basic-multiple form-control" multiple="multiple" style="width: 400px;"></select>
                                    <small id="printerlistHelp" class="form-text text-muted">Search for additional printers to add to your device.</small>
                                </div>
                                <div class="form-group">
                                    <label for="defaultprinter">Default Printer</label>
                                    <select id="defaultprinter" aria-describedby="defaultprinterHelp" class="form-control" style="width: 400px;">
                                        <option value="0">No default</option>
                                    </select>
                                    <small id="defaultprinterHelp" class="form-text text-muted">Choose a printer to be set as default.</small>
                                </div>
                                <div class="form-group" style="text-align: center;">
                                    <input type="submit" id="updateprinters" class="btn btn-primary" value="Update My Printers"></input>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row align-items-center vh-100">
                <div class="col-xl-6 col-lg-6 col-md-8 col-sm-12 col-12 mx-auto text-center">
                    <small>Logged in as
                    <?php
                        echo $_SERVER['OIDC_CLAIM_upn'];
                        if($admin===true) {
                            echo " with admin privileges";
                        }
                    ?>
                    </small>
                </div>
            </div>
        </div>
    </body>
    <script>
        var initialized = false;

        var submitForm = function(e) {
            let computername = document.getElementById('computername');
            let updateprinters = document.getElementById('updateprinters');
            let defaultprinter = document.getElementById('defaultprinter');
            let queues = $('.js-example-basic-multiple').select2('data');
            if(queues.length < 1) {
                alert('At least one printer must be selected');
                e.preventDefault();
                return false;
            }
            let dp = false;
            if(defaultprinter.value != "0") {
                dp = defaultprinter.value;
            }
            if(dp == false) {
                let c = confirm('Click OK to continue without a default printer or Cancel to select one before continuing.');
                if(!c) {
                    e.preventDefault();
                    return false;
                }
            }

            let data = [{
                computername: computername.value.toUpperCase(),
                queues: [],
                default: dp
            }];
            //console.log(updateprinters);
            updateprinters.disabled = true;
            for(let i = 0; i < queues.length; i++) {
                data[0].queues.push(queues[i].text);
            }
            //console.log(data);
            $.ajax({
                type: "POST",
                contentType : 'application/json',
                url: '/api/update_mappings',
                data: JSON.stringify(data),
                success: function(data, status){
                    //console.log(status);
                    //alert("Data: " + JSON.stringify(data) + "\nStatus: " + status);
                    updateprinters.disabled = false;
                    window.location = 'printer://update';
                },
                error: function(request, status, error){
                    //console.log(request);
                    //console.log(status);
                    //console.log(error);
                    if(request.status==401) {
                        location.reload();
                    } else {
                        alert('Request error: ' + error);
                    }
                    //alert("Data: " + JSON.stringify(data) + "\nStatus: " + status);
                    //updateprinters.disabled = false;
                    //window.location = 'printer://update';
                }
            });
            e.preventDefault();
        }
        var removeDefaultOption = function(value) {
            //console.log(value);
            var defaultprinter = $('#defaultprinter');
            let option = defaultprinter.find('option[value="'+value+'"]');
            //console.log(option);
            if(option[0].selected) {
                //console.log("default removed");
                //console.log(defaultprinter.find('option').get(0));
                //defaultprinter.prop("selectedIndex", 0);
            }
            option.remove();
        }
        var addDefaultOption = function(value) {
            //console.log(value);
            var defaultoption = $('<option>', {
                value: value,
                text: value
            });

            //console.log(result.print_mappings.mapping[i].default);
            var defaultprinter = $('#defaultprinter');

            defaultprinter.append(defaultoption);

            /*defaultprinter.find('option').get(0).remove();

            defaultprinter.html(defaultprinter.find('option').sort(function (option1, option2) {
                //console.log($(option1))
                return $(option1).text() < $(option2).text() ? -1 : 1;
            }));

            defaultprinter.prepend("<option value='0'>No default</option>");*/
        }

        $(document).ready(function() {
            $('.js-example-basic-multiple').select2({
                ajax: {
                    url: '/api/queue_cache',
                    dataType: 'json',
                    // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
                    cache: true,
                    processResults: function (data) {
                        return data.data;
                    },
                },
                placeholder: 'Click to search for printers to add',
                //minimumInputLength: 1,
                templateResult: formatQueue,
                templateSelection: formatQueueSelection
                //don't open dropdown after unselecting
            }).on("select2:unselecting", function(e) {
                $(this).data('state', 'unselected');
            }).on("select2:open", function(e) {
                if ($(this).data('state') === 'unselected') {
                    $(this).removeData('state'); 
                    var self = $(this);
                    setTimeout(function() {
                        self.select2('close');
                    }, 1);
                }    
            }).on('select2:select', function (e) {
                if(initialized) {
                    //var data = e.params.data;
                    /*for(let i = 0; i < e.target.options.length; i++) {
                        console.log(e.target.options[i].value);
                    }*/
                    addDefaultOption(e.params.data.text);
                    //console.log($('.js-example-basic-multiple').select2('data'));
                }
            }).on('select2:unselect', function (e) {
                if(initialized) {
                    //var data = e.params.data;
                    /*for(let i = 0; i < e.target.options.length; i++) {
                        console.log(e.target.options[i].value);
                    }*/
                    removeDefaultOption(e.params.data.text);
                    //console.log($('.js-example-basic-multiple').select2('data'));
                }
            });

            var computername = document.getElementById('computername').value.trim();
            <?php

            if($admin===true) {
                echo <<<STRING
                cnelem = document.getElementById('computername');
                cnelem.removeAttribute('readonly');
                STRING;
            }

            ?>
            if(computername != "") {
                $.ajax({url: "/api/mappings/" + computername, success: function(result){
                    //let queues = [];
                    if(result.hasOwnProperty('print_mappings')) {
                        //console.log(result.print_mappings.mapping);
                        for(let i = 0; i < result.print_mappings.mapping.length; i++) {
                            //console.log(result.print_mappings.mapping[i].queue);

                            var data = {
                                id: result.print_mappings.mapping[i].queue,
                                text: result.print_mappings.mapping[i].queue
                            };

                            var newOption = new Option(data.text, data.id, true, true);

                            var defaultoption = $('<option>', {
                                value: result.print_mappings.mapping[i].queue,
                                text: result.print_mappings.mapping[i].queue
                            });

                            //console.log(result.print_mappings.mapping[i].default);
                            var defaultprinter = $('#defaultprinter');

                            if(result.print_mappings.mapping[i].default) {
                                defaultoption.attr("selected", "selected");
                            }

                            defaultprinter.append(defaultoption);

                            //console.log(newOption);
                            $('.js-example-basic-multiple').append(newOption);
                        }
                        $('.js-example-basic-multiple').trigger('change');
                    }
                    initialized = true;
                }});
            } else {
                <?php

                if($admin===true) {
                    echo <<<STRING
                    cnelem = document.getElementById('computername');
                    cnelem.placeholder = 'Enter a valid computername';
                    cnelem.removeAttribute('readonly');
                    STRING;
                } else {
                    echo <<<STRING
                    cnelem = document.getElementById('computername');
                    upelem = document.getElementById('updateprinters');
                    cnelem.value = 'Failed to determine computername';
                    cnelem.style = 'color: red;';
                    upelem.disabled = true;
                    $('.js-example-basic-multiple').prop('disabled', 'true');
                    STRING;
                }
                ?>
            }

            document.getElementById('upform').addEventListener('submit', function(e) {
                submitForm(e);
            });
        });

        function formatQueueSelection (queue) {
            return queue.text;
        }

        var formatQueue = function(queue) {
            if (queue.loading) {
                return queue.text;
            }

            //console.log(queue);

            var $container = $(
                "<div>" +
                    "<div>" +
                        "<h6 style='margin: 0;'>" + queue.text + "</h6>" +
                        "<span style='display: block; padding: 0px; margin: 0; font-size: 9px;'>IP: " + queue.ip + "</span>" +
                        "<span style='display: block; padding: 0px; margin: 0; font-size: 9px;'>Driver: " + queue.driver + "</span>" +
                        "<span style='display: block; padding: 0px; margin: 0; font-size: 9px;'>Location: " + queue.location + "</span>" +
                        "<span style='display: block; padding: 0px; margin: 0; font-size: 9px;'>Comment: " + queue.comment + "</span>" +
                    "</div>" +
                "</div>"
            );

            //$container.find(".card-title").text(queue.text);
            //$container.find(".card-text").text(queue.ip);

            return $container;
        }
    </script>
</html>