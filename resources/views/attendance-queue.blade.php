<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/cssua.min.js') }}"></script>
    <script>
        function adjustDate(element){
            // Localize date and time
            var eventDate = document.getElementById(element);
            var date = new Date(Date.parse(eventDate.textContent + "Z"));

            eventDate.textContent = date.toLocaleString();
        }
    </script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm fixed-top w-100">
            <div class="container">
                <a class="navbar-brand fw-bold" href="/">
                    HIMTI Registration
                </a>
            </div>
        </nav>

        <main>
            <div class="container">
                @component('components.status-badge')
                @endcomponent
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8 pt-3">
                        <input type="hidden" name="clientId" value="4b3be2a2-bf4e-4a35-87ed-f0de8e42dfdc">
                        @csrf
                        <div class="card mb-4">
                            <div class="card-body">
                                <h1>Check Out</h1>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header h5 bg-primary text-white">{{ __('Account Details') }}</div>
                            <div class="card-body">
                                <div class="alert alert-info" role="alert">
                                    <b>Please make sure that you have entered the below data correctly.</b> You will be notified if the check out process has been failed or successful.
                                </div>
                                <div class="form-group row">
                                    <label for="email" class="col-md-4 col-form-label text-md-right">Registered email address<b class="text-danger">*</b></label>
                                    <div class="col-md-6">
                                        <input id="email" type="email" class="form-control" name="email" required autocomplete="email" autofocus placeholder="Enter your registered email address">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="token" class="col-md-4 col-form-label text-md-right">Exit token<b class="text-danger">*</b></label>
                                    <div class="col-md-6">
                                        <input id="token" type="number" class="form-control" name="token" required autofocus placeholder="Enter the provided exit token">
                                    </div>
                                </div>
                                <div class="form-group row mb-0">
                                    <div class="col-md-6 offset-md-4">
                                        <button onClick="submitForm()" class="btn btn-primary mt-2">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4" id="responseContainer">
                            <div class="card-body">
                                <p id="responseText" class="m-0">Pleae click on the <b>Submit</b> button to continue.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function submitForm(){
            var formData = new FormData();
            var element = document.getElementById('responseText');

            formData.append("_token", "{{ csrf_token() }}");
            formData.append("clientId", document.querySelector('input[name="clientId"]').value);
            formData.append("email", document.querySelector('input[name="email"]').value);
            formData.append("token", document.querySelector('input[name="token"]').value);

            // Disable modal confirm button
            document.querySelector('input[name="email"]').disabled = true;
            document.querySelector('input[name="token"]').disabled = true;

            function checkOutRequest(){
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState != 4) return;
                    document.getElementById("responseContainer").style.display = "block";
                    if (xhr.status == 200) {
                        // Set up links
                        var response = JSON.parse(this.responseText)
                        if ("Notification" in window && Notification.permission == "granted") var notification = new Notification("Successfully recorded. We will email you further if the check out process has been successful.");
                        else if ("webkitNotifications" in window && window.webkitNotifications.checkPermission() == 0) window.webkitNotifications.createNotification(null, "Successfully recorded. We will email you further if the check out process has been successful.", "HIMTI Registration").show();

                        element.style.color = "#008000";
                        element.innerHTML = "<strong>Successfully recorded. We will email you further if the check out process has been successful.</strong>";
                    } else if (xhr.readyState == 4 && xhr.status == 503) {
                        // Delay Mode
                        var delay = 15000;

                        element.style.color = "#249ef2";
                        element.innerHTML = "<strong>The server is currently busy. Trying again in <span id='checkOutCountdown'></span></strong>";

                        var nextCheck = new Date();
                        nextCheck.setMilliseconds(nextCheck.getMilliseconds() + delay);

                        var timerId = countdown(nextCheck, function(ts) {
                            document.getElementById('checkOutCountdown').innerHTML = ts.toHTML("strong");
                        }, countdown.MINUTES|countdown.SECONDS);

                        trials++;
                        setTimeout(function (){
                            window.clearInterval(timerId);
                            checkOutRequest();
                        }, delay);
                    } else {
                        document.querySelector('input[name="email"]').disabled = false;
                        document.querySelector('input[name="token"]').disabled = false;

                        element.style.color = "#ff0000";
                        element.innerHTML = "<strong>Error:</strong> " + xhr.responseText;
                    }
                };
                xhr.open("POST", "/attendance", true);
                xhr.send(formData);
            }

            checkOutRequest();
        }
    </script>
</body>
</html>
