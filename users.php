<?php

session_start();

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'public';

if ($role !== 'admin') {
    header('Location: /');
    die();
}

require_once 'templates/header.php';

?>

<!-- Create new user form -->
<form id="createUserForm" onsubmit="createUser(event)"></form>

<!-- User email toast -->
<div id="singleEmailSentToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary">
        <strong class="mr-auto"><i class="fas fa-envelope"></i> Email skickat</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="toast-body">
        Mail skickat till <span id="emailSentTarget"></span>
    </div>
</div>

<!-- Edit user modal -->
<div class="modal" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalTitle">Redigera användare</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="editUserUuid">UUID</label>
                    <input type="text" class="form-control" id="editUserUuid" readonly>
                </div>
                <div class="form-group">
                    <label for="editUserName">Namn</label>
                    <input type="text" class="form-control" id="editUserName" placeholder="Anders Andersson">
                </div>
                <div class="form-group">
                    <label for="editUserEmail">Email</label>
                    <input type="email" class="form-control" id="editUserEmail" placeholder="anders.andersson@gmail.com">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="updateUser()">Spara</button>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="page-title">
                <h3>Användare</h3>
                <div class="dropdown">
                    <button id="usersActionsButton" class="btn btn-sm btn-outline-dark" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-caret-down"></i>
                        Hantera användare
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="usersActionsButton">
                        <button class="dropdown-item text-danger" type="button" onclick="confirmDeleteAll()">Ta bort alla användare</button>
                    </div>
                </div>
            </div>
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">Namn</th>
                    <th scope="col">Email</th>
                    <th scope="col">UUID</th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tbody id="userTableBody">
                <tr id="user-new">
                    <th scope="row"><input id="newUserName" type="text" class="form-control" form="createUserForm" required></th>
                    <td><input id="newUserEmail" type="email" class="form-control" form="createUserForm" required></td>
                    <td></td>
                    <td>
                        <input type="submit" class="btn btn-sm btn-primary" value="Lägg till" form="createUserForm">
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.onload = () => {
        loadData();
    };

    function loadData() {
        $.get('/api/users')
            .done((users) => {
                const userTableBody = document.getElementById('userTableBody');
                users.forEach( (user) => {
                    userTableBody.innerHTML += userRowTemplate(user);
                });
            })
            .fail((err) => {
                alert('Error while loading users, are you signed in?');
            });
    }

    function userRowTemplate(user) {
        return `<tr id="user-${user.uuid}">
                    <th scope="row">${user.name}</th>
                    <td>${user.email}</td>
                    <td>${user.uuid}</td>
                    <td>
                        <button class="btn btn-sm" onclick="emailUser('${user.uuid}', '${user.name}', '${user.email}')">
                            <i class="fas fa-envelope"></i>
                        </button>
                        <button class="btn btn-sm" onclick="editUser('${user.uuid}', '${user.name}', '${user.email}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm" onclick="deleteUser('${user.uuid}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
    }

    function deleteUser(uuid) {
        $.ajax({
            url: `/api/users?uuid=${uuid}`,
            method: 'DELETE'
        }).done(() => {
            // Delete row from table
            document.getElementById('user-' + uuid).outerHTML = '';
        }).fail((err) => {
            alert('Error while deleting user, try reloading the page');
        });
    }

    function confirmDeleteAll() {
        const confirmation = confirm('Vill du verkligen ta bort alla användare?');

        if (confirmation) {
            deleteAllUsers();
        }
    }

    function deleteAllUsers() {
        $.ajax({
            url: `/api/users`,
            method: 'DELETE'
        }).done(() => {
            // Delete all users from table
            $('tr[id^="user-"]:not(:first-child)').remove();
        }).fail((err) => {
            alert('Error while deleting users, try reloading the page');
        });
    }

    function createUser(e) {
        e.preventDefault();

        const nameInput = document.getElementById('newUserName');
        const emailInput = document.getElementById('newUserEmail');

        const name = nameInput.value;
        const email = emailInput.value;

        $.post('/api/users', {name, email})
            .done((user) => {
                // Insert new row at the top
                $(userRowTemplate(user)).insertAfter($('#user-new'));

                // Clear user data
                nameInput.value = '';
                emailInput.value = '';

                // Focus name input
                nameInput.focus();
            }).fail((err) => {
                alert('Error while creating user');
            });
    }

    function updateUser() {
        const uuid = document.getElementById('editUserUuid').value;
        const name = document.getElementById('editUserName').value;
        const email = document.getElementById('editUserEmail').value;

        const user = {uuid, name, email};

        $.post('/api/users', user)
            .done(() => {
                // Update info
                document.getElementById('user-' + uuid).outerHTML = userRowTemplate(user);

                // Close modal
                $('#editUserModal').modal('hide');
            }).fail((err) => {
                alert('Error while saving user');
        });
    }

    function editUser(uuid, name, email) {
        document.getElementById('editUserUuid').value = uuid;
        document.getElementById('editUserName').value = name;
        document.getElementById('editUserEmail').value = email;

        $('#editUserModal').modal('show');
    }

    function emailUser(uuid, name, email) {
        $.post('/api/email', {uuid})
            .done(() => {
                document.getElementById('emailSentTarget').innerText = `${name} [${email}]`;

                $('#singleEmailSentToast').toast({delay: 2000}).toast('show');
            }).fail((err) => {
                alert('Error while sending single email');
        });
    }
</script>

<?php

require_once 'templates/footer.php';
