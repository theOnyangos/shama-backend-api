<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shama Rugby Foundation - Farewell Page</title>
    <link rel="icon" href="/logo1.png">
    @vite('resources/css/app.css')
</head>
<body class="antialiased bg-slate-100">
<div class="my-5">
    <div class="flex justify-center items-center mb-3">
        <img alt="Shama logo" class="shadow-md" src="/shama-logo2.png" />
    </div>
    <h2 class="text-center font-bold text-3xl text-green-700">Farewell {{ $name }}</h2>
    <div class="container max-w-6xl mx-auto h-full flex justify-center items-center my-5">
        <div class="mb-5 p-5 bg-white shadow-md rounded-md">
            <div class="md:w-[500px]">
                <img alt="Shama logo" class="" src="/delete.svg" />
            </div>
            <p class="block text-lg font-medium text-gray-700 mt-4">Before closing your account, please provide some details:</p>

            <!-- Farewell Message Form -->
            <!-- Account Closure Form -->
            <form action="{{ url('/api/v1/delete-account/'.$userId) }}" id="saveFarewellMessage">
                @csrf
                <label for="closure_reason" class="block text-lg font-medium text-gray-700 mt-4">Reason for Closing Account:</label>
                <textarea id="closure_reason" name="closure_reason" class="w-full border border-gray-300 rounded-md p-2 mt-2" rows="4" placeholder="Please explain why you are closing your account..."></textarea>
                <div class="error_message"></div>

                <label for="farewell_message" class="block text-lg font-medium text-gray-700 mt-4">Farewell Message (Optional):</label>
                <textarea id="farewell_message" name="farewell_message" class="w-full border border-gray-300 rounded-md p-2 mt-2" rows="4" placeholder="Type your farewell message here..."></textarea>

                <div class="requestMessage"></div>

                <div class="mt-4">
                    <button type="submit" class="account-close-button bg-red-700 shadow-md text-white rounded-md px-4 py-2 hover:bg-red-600 transition duration-300 ease-in-out font-bold">Close Account</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="text-center my-5">
    {{-- copyright --}}
    &copy; <span id="currentYear"></span> Shama Rugby Foundation. All rights reserved. Created by <a href="mailto:otienodennis29@gmail.com" class="font-bold text-blue-500">Dennis Otieno</a>.
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    // Get the current year and set it in the 'currentYear' span
    const yearSpan = document.getElementById("currentYear");
    const currentYear = new Date().getFullYear();
    yearSpan.textContent = currentYear;

    $(document).ready(function() {
        $("#saveFarewellMessage").on("submit", function(e) {
            e.preventDefault()
            const closureReason = $("#closure_reason").val();
            const farewellMessage =  $("#farewell_message").val();
            const userId = $("#user_id").val()
            const url = $(this).attr('action')
            const data = {
                closure_reason: closureReason,
                farewell_message: farewellMessage,
                user_id: userId
            }

            console.log(data)

            if (closureReason === "") {
                $(".error_message").html(`<div class="py-1 px-3 border border-red-500 rounded-md">
                    <p class="text-red-500 text-md">Please provide a closure reason to proceed with account closing.</p>
                </div>`)
            } else {
                $.ajax({
                    url: url,
                    method: "POST",
                    dataType: "JSON",
                    data: data,
                    beforeSend: function() {
                        $(".account-close-button").text(`Submitting please wait...`)
                    },
                    success: function(response) {
                        if (response.status === "success") {
                            $("#saveFarewellMessage")[0].reset()
                            $(".account-close-button").text(`Close Account`)
                            $(".requestMessage").html(` <div class="border border-green-500 rounded-md p-3 bg-green-200 my-3">
                                <p class="text-green-500">${response.message}</p>
                            </div>`)
                        }

                        if (response.status === "error") {
                            $(".account-close-button").text(`Close Account`)
                            $(".requestMessage").html(` <div class="border border-red-500 rounded-md p-3 bg-red-200 my-3">
                                <p class="text-red-500">${response.message}</p>
                            </div>`)
                        }
                    }
                })
            }
        })
    })
</script>
</body>
</html>
