<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="/logo1.png">
        <title>Shama Rugby Foundation - API</title>
        @vite('resources/css/app.css')
    </head>
    <body class="antialiased bg-slate-100">
    <div class="my-5">
        <div class="flex justify-center items-center mb-3">
            <img alt="Shama logo" class="shadow-md" src="/shama-logo2.png" />
        </div>
        <h2 class="text-center font-bold text-3xl text-green-700">Shama API Endpoints:</h2>
    </div>
        <div class="container bg-white mx-auto h-full flex justify-center items-center shadow-md rounded-md mb-5">
            <ul class="flex space-x-4 flex-wrap p-5">
                <?php
                // Define your API endpoints here
                $endpoints = [
                    [
                        'route' => '/api/v1/auth-team-registration',
                        'controller' => 'AuthController',
                        'action' => 'POST',
                        'description' => 'This endpoint creates a new team player.',
                    ],
                    [
                        'route' => '/api/v1/auth-staff-registration',
                        'controller' => 'AuthController',
                        'action' => 'POST',
                        'description' => 'Create a new staff registration',
                    ],
                    [
                        'route' => '/api/v1/auth-login',
                        'controller' => 'AuthController',
                        'action' => 'POST',
                        'description' => 'User login',
                    ],
                    [
                        'route' => '/api/v1/counties',
                        'controller' => 'AddressController',
                        'action' => 'GET',
                        'description' => 'Gets all counties',
                    ],
                    [
                        'route' => '/api/v1/regions/{county_id}',
                        'controller' => 'AddressController',
                        'action' => 'GET',
                        'description' => 'Get all regions in a county',
                    ],
                    [
                        'route' => '/api/v1/streets/{region_id}',
                        'controller' => 'AddressController',
                        'action' => 'GET',
                        'description' => 'Get all streets in a region',
                    ],
                    [
                        'route' => '/api/v1/suspend-user-account/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Suspend user account',
                    ],
                    [
                        'route' => '/api/v1/approve-user-account/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Approve user account',
                    ],
                    [
                        'route' => '/api/v1/retrieve-account/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Retrieve user account',
                    ],
                    [
                        'route' => '/api/v1/delete-account/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Delete user account',
                    ],
                    [
                        'route' => '/api/v1/get-team-name/{user_id}',
                        'controller' => 'AuthController',
                        'action' => 'GET',
                        'description' => 'Get team name',
                    ],
                    [
                        'route' => '/api/v1/send-verification-code/{user_email}',
                        'controller' => 'NotificationsController',
                        'action' => 'POST',
                        'description' => 'Send password verification code',
                    ],
                    [
                        'route' => '/api/v1/check-verification-code/{user_email}',
                        'controller' => 'NotificationsController',
                        'action' => 'GET',
                        'description' => 'Check verification code',
                    ],
                    [
                        'route' => '/api/v1/update-user-password/{user_email}',
                        'controller' => 'NotificationsController',
                        'action' => 'POST',
                        'description' => 'Update user password',
                    ],
                    [
                        'route' => '/api/v1/send-delete-account-confirmation/{user_email}',
                        'controller' => 'NotificationsController',
                        'action' => 'POST',
                        'description' => 'Send delete account confirmation',
                    ],
                    [
                        'route' => '/api/v1/logout',
                        'controller' => 'AuthController',
                        'action' => 'POST',
                        'description' => 'Logout',
                    ],
                    [
                        'route' => '/api/v1/get-users-with-details',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get users with details',
                    ],
                    [
                        'route' => '/api/v1/update-user-account/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Update user account',
                    ],
                    [
                        'route' => '/api/v1/update-account-password/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Update account password',
                    ],
                    [
                        'route' => '/api/v1/upload-user-image/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Upload user profile image',
                    ],
                    [
                        'route' => '/api/v1/get-updated-user-information/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get updated user information',
                    ],
                    [
                        'route' => '/api/v1/get-user-count/{team_id}',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get logged-in users count',
                    ],
                    [
                        'route' => '/api/v1/get-permissions',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get all permissions',
                    ],
                    [
                        'route' => '/api/v1/add-user-permission/{user_id}/{role_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Grant user permission',
                    ],
                    [
                        'route' => '/api/v1/remove-user-permission/{user_id}/{role_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Remove user permission',
                    ],
                    [
                        'route' => '/api/v1/create-permission',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Create new permission',
                    ],
                    [
                        'route' => '/api/v1/users',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get users',
                    ],
                    [
                        'route' => '/api/v1/statistical-data',
                        'controller' => 'StatisticalDataController',
                        'action' => 'GET',
                        'description' => 'Get statistical data',
                    ],
                    [
                        'route' => '/api/v1/unverified-users-data',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get unverified users with details',
                    ],
                    [
                        'route' => '/api/v1/get-players-data',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get players data',
                    ],
                    [
                        'route' => '/api/v1/get-male-female-count',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get male and female count',
                    ],
                    [
                        'route' => '/api/v1/get-coaches-data',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get coaches data',
                    ],
                    [
                        'route' => '/api/v1/get-coaches-and-players',
                        'controller' => 'UsersController',
                        'action' => 'GET',
                        'description' => 'Get coaches and players data',
                    ],
                    [
                        'route' => '/api/v1/get-new-players',
                        'controller' => 'PlayersController',
                        'action' => 'GET',
                        'description' => 'Get all new players',
                    ],
                    [
                        'route' => '/api/v1/get-players',
                        'controller' => 'PlayersController',
                        'action' => 'GET',
                        'description' => 'Get all players',
                    ],
                    [
                        'route' => '/api/v1/get-graduated-players',
                        'controller' => 'PlayersController',
                        'action' => 'GET',
                        'description' => 'Get all graduated players',
                    ],
                    [
                        'route' => '/api/v1/get-team-players/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get all team players',
                    ],
                    [
                        'route' => '/api/v1/get-team-coaches/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get all team coaches',
                    ],
                    [
                        'route' => '/api/v1/create-team/{admin_id}',
                        'controller' => 'TeamController',
                        'action' => 'POST',
                        'description' => 'Create new team',
                    ],
                    [
                        'route' => '/api/v1/add-player-to-team/{admin_id}/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'PUT',
                        'description' => 'Add new team member',
                    ],
                    [
                        'route' => '/api/v1/add-multiple-team-member/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'PUT',
                        'description' => 'Add multiple team members',
                    ],
                    [
                        'route' => '/api/v1/update-team-account/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'POST',
                        'description' => 'Update team account details',
                    ],
                    [
                        'route' => '/api/v1/delete-team-account/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'DELETE',
                        'description' => 'Delete team account details (Incomplete)',
                    ],
                    [
                        'route' => '/api/v1/permanent-delete-team-account/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'DELETE',
                        'description' => 'Permanent delete team account details (Incomplete)',
                    ],
                    [
                        'route' => '/api/v1/get-account-details/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get team account details (Incomplete)',
                    ],
                    [
                        'route' => '/api/v1/get-team-members/{team_id}',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get team players (Incomplete)',
                    ],
                    [
                        'route' => '/api/v1/get-all-team-locations-data',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get all team location data (Incomplete)',
                    ],
                    [
                        'route' => '/api/v1/create-role-permission',
                        'controller' => 'PermissionsController',
                        'action' => 'POST',
                        'description' => 'Create new role permission',
                    ],
                    [
                        'route' => '/api/v1/get-roles-permissions',
                        'controller' => 'PermissionsController',
                        'action' => 'GET',
                        'description' => 'Get roles with permission',
                    ],
                    [
                        'route' => '/api/v1/search-users',
                        'controller' => 'SearchController',
                        'action' => 'GET',
                        'description' => 'Search users',
                    ],
                    [
                        'route' => '/api/v1/get-unapproved-members',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get unapproved members',
                    ],
                    [
                        'route' => '/api/v1/get-coaches',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get all coaches',
                    ],
                    [
                        'route' => '/api/v1/get-team-weekly-attendance',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get team weekly attendance',
                    ],
                    [
                        'route' => '/api/v1/get-graduated-team',
                        'controller' => 'TeamController',
                        'action' => 'GET',
                        'description' => 'Get graduated team members',
                    ],
                    [
                        'route' => '/api/v1/get-notifications',
                        'controller' => 'NotificationsController',
                        'action' => 'GET',
                        'description' => 'Get all notifications',
                    ],
                    [
                        'route' => '/api/v1/get-unread-notifications',
                        'controller' => 'NotificationsController',
                        'action' => 'GET',
                        'description' => 'Get unread notifications',
                    ],
                    [
                        'route' => '/api/v1/delete-notification',
                        'controller' => 'NotificationsController',
                        'action' => 'DELETE',
                        'description' => 'Delete notification (Incomplete)',
                    ],
                    [
                        'route' => '/api/v1/update-admin-account-details',
                        'controller' => 'UsersController',
                        'action' => 'PUT',
                        'description' => 'Update admin account details',
                    ],
                    [
                        'route' => '/api/v1/update-profile-image',
                        'controller' => 'UsersController',
                        'action' => 'PUT',
                        'description' => 'Update profile image',
                    ],
                    [
                        'route' => '/save-closure-reason/{user_id}',
                        'controller' => 'UsersController',
                        'action' => 'POST',
                        'description' => 'Save account closing reason and deletes account.',
                    ],
                ];

                // Loop through and display each endpoint
                foreach ($endpoints as $endpoint) {
                    echo "<li class='mb-5 rounded-lg p-3 ";

                    // Check the HTTP request method and apply different background colors
                    if ($endpoint['action'] == 'GET') {
                        echo 'bg-blue-100 border border-blue-500';
                    } elseif ($endpoint['action'] == 'POST') {
                        echo 'bg-green-100 border border-green-500';
                    } elseif ($endpoint['action'] == 'DELETE') {
                        echo 'bg-red-100 border border-red-500';
                    }elseif ($endpoint['action'] == 'PUT') {
                        echo 'bg-yellow-100 border border-yellow-500';
                    }

                    echo "'>";

                    echo "<strong>Endpoint:</strong> " . $endpoint['route'] . "<br>";
                    echo "<strong>Method:</strong> <span class='font-bold ";

                    // Check the HTTP request method and apply different text colors
                    if ($endpoint['action'] == 'GET') {
                        echo 'text-blue-500';
                    } elseif ($endpoint['action'] == 'POST') {
                        echo 'text-green-500';
                    } elseif ($endpoint['action'] == 'DELETE') {
                        echo 'text-red-500';
                    }elseif ($endpoint['action'] == 'PUT') {
                        echo 'text-yellow-500';
                    }

                    echo "'>" . $endpoint['action'] . "</span><br>";
                    echo "<strong>Description:</strong> " . $endpoint['description'];
                    echo "</li>";
                }
                ?>
            </ul>
        </div>

        <div class="text-center my-5">
            {{-- copyright --}}
            &copy; <span id="currentYear"></span> Shama Rugby Foundation. All rights reserved. Created by <a href="mailto:otienodennis29@gmail.com" class="font-bold text-blue-500">Dennis Otieno</a>.
        </div>

    <script>
        // Get the current year and set it in the 'currentYear' span
        const yearSpan = document.getElementById("currentYear");
        const currentYear = new Date().getFullYear();
        yearSpan.textContent = currentYear;
    </script>
    </body>
</html>
