<?php

session_start();

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'public';

if ($role !== 'admin') {
    header('Location: /');
    die();
}

require_once 'templates/header.php';

?>

<!-- Create new vote session form -->
<form id="createVoteSessionForm" onsubmit="createVoteSession(event)"></form>

<!-- Edit user modal -->
<div class="modal" id="editVoteSessionModal" tabindex="-1" role="dialog" aria-labelledby="editVoteSessionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVoteSessionModalTitle">Redigera omröstning</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editVoteSessionUuid">
                <div class="form-group">
                    <label for="editVoteSessionName">Namn</label>
                    <input type="text" class="form-control" id="editVoteSessionName">
                </div>
                <div class="form-group">
                    <label for="editVoteSessionStatus">Status</label>
                    <input type="text" class="form-control" id="editVoteSessionStatus" readonly>
                </div>
                <div class="form-group">
                    <label for="editVoteSessionChoices">Alternativ</label>
                    <div class="row">
                        <div id="editVoteSessionChoicesContainer" class="col"></div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button id="addChoiceButton" class="btn btn-sm btn-outline-primary" onclick="addChoice()">
                                <i class="fas fa-plus"></i>
                                Lägg till ny
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="updateVoteSession()">Spara</button>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="page-title">
                <h3>Omröstningar</h3>
                <div class="dropdown">
                    <button id="votesessionsActionsButton" class="btn btn-sm btn-outline-dark" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-caret-down"></i>
                        Hantera omröstningar
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="votesessionsActionsButton">
                        <button class="dropdown-item text-danger" type="button" onclick="confirmDeleteAll()">Ta bort alla omröstningar</button>
                    </div>
                </div>
            </div>
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">Namn</th>
                    <th scope="col">Status</th>
                    <th scope="col">Röster</th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tbody id="voteSessionsTableBody">
                <tr id="voteSession-new">
                    <th scope="row"><input id="newVoteSessionName" type="text" class="form-control" form="createVoteSessionForm" required></th>
                    <td></td>
                    <td></td>
                    <td>
                        <input type="submit" class="btn btn-sm btn-primary" value="Lägg till" form="createVoteSessionForm">
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
        $.get('/api/votesessions')
            .done((voteSessions) => {
                const voteSessionsTableBody = document.getElementById('voteSessionsTableBody');
                voteSessions.forEach( (voteSession) => {
                    voteSessionsTableBody.innerHTML += voteSessionRowTemplate(voteSession);
                });
            })
            .fail((err) => {
                alert('Error while loading vote sesssions, are you signed in?');
            });
    }

    function voteSessionRowTemplate(voteSession) {
        return `<tr id="voteSession-${voteSession.uuid}">
                    <th scope="row">${voteSession.name}</th>
                    <td>
                        <i class="fas fa-circle ${voteSession.open ? 'text-success' : 'text-secondary'}"></i>
                        ${voteSession.open ? 'Öppen' : 'Stängd'}
                    </td>
                    <td>
                        ${voteSession.vote_count}
                    </td>
                    <td>` + (voteSession.open ?
                        `<button class="btn btn-sm btn-outline-danger" onclick="closeVoteSession('${voteSession.uuid}')">
                            Avsluta
                        </button>` :
                        `<button class="btn btn-sm btn-outline-success" onclick="openVoteSession('${voteSession.uuid}')">
                            Öppna
                        </button>`) +
                        `<button class="btn btn-sm"
                            ${voteSession.vote_count !== 0 ? 'disabled title="Man kan inte redigera omröstningar med röster"' : ''}
                            onclick="editVoteSession('${voteSession.uuid}', '${voteSession.name}', ${voteSession.open}, ${JSON.stringify(voteSession.choices).replace(/"/g, '\'')})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm" onclick="confirmDelete('${voteSession.uuid}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
    }

    function deleteVoteSession(uuid) {
        $.ajax({
            url: `/api/votesessions?uuid=${uuid}`,
            method: 'DELETE'
        }).done(() => {
            // Delete row from table
            document.getElementById('voteSession-' + uuid).outerHTML = '';
        }).fail((err) => {
            alert('Error while deleting user, try reloading the page');
        });
    }

    function confirmDeleteAll() {
        const confirmation = confirm('Vill du verkligen ta bort alla omröstningar och röster?');

        if (confirmation) {
            deleteAllVoteSessions();
        }
    }

    function confirmDelete(uuid) {
        const confirmation = confirm('Vill du verkligen ta bort den valda omröstningen och alla röster?');

        if (confirmation) {
            deleteVoteSession(uuid);
        }
    }

    function openVoteSession(uuid) {
        $.post('/api/openvotesession', {uuid})
            .done((voteSession) => {
                const voteSessionTarget = document.getElementById('voteSession-' + uuid);
                voteSessionTarget.outerHTML = voteSessionRowTemplate(voteSession);
            }).fail((err) => {
                alert('Error while opening vote session');
            });
    }

    function closeVoteSession(uuid) {
        $.post('/api/closevotesession', {uuid})
            .done((voteSession) => {
                const voteSessionTarget = document.getElementById('voteSession-' + uuid);
                voteSessionTarget.outerHTML = voteSessionRowTemplate(voteSession);
            }).fail((err) => {
                alert('Error while close vote session');
            });
    }

    function deleteAllVoteSessions() {
        $.ajax({
            url: `/api/votesessions`,
            method: 'DELETE'
        }).done(() => {
            // Delete all vote sessios from table
            $('tr[id^="voteSession-"]:not(:first-child)').remove();
        }).fail((err) => {
            alert('Error while deleting vote sessions, try reloading the page');
        });
    }

    function createVoteSession(e) {
        e.preventDefault();

        const nameInput = document.getElementById('newVoteSessionName');

        const name = nameInput.value;

        $.post('/api/votesessions', {name})
            .done((voteSession) => {
                // Insert new row at the top
                $(voteSessionRowTemplate(voteSession)).insertAfter($('#voteSession-new'));

                // Clear name data
                nameInput.value = '';

                // Focus name input
                nameInput.focus();
            }).fail((err) => {
                if (err.status === 409) {
                    alert('Det finns redan en omröstning med det namnet')
                } else  {
                    alert('Error while creating vote session');
                }
            });
    }

    function updateVoteSession() {
        const uuid = document.getElementById('editVoteSessionUuid').value;
        const name = document.getElementById('editVoteSessionName').value;
        const choiceElements = Array.from(document.querySelectorAll('input[name="choices[]"]'));

        const choices = choiceElements.map(choiceElement => choiceElement.value);

        const voteSession = {uuid, name, choices};

        $.post('/api/votesessions', voteSession)
            .done((voteSession) => {
                // Update info
                document.getElementById('voteSession-' + uuid).outerHTML = voteSessionRowTemplate(voteSession);

                // Close modal
                $('#editVoteSessionModal').modal('hide');
            }).fail((err) => {
                alert('Error while saving vote session');
        });
    }

    function choiceTemplate(choice) {
        return `<div class="row">
                    <div class="col">
                        <input class="form-control" type="text" name="choices[]" value="${choice || ''}">
                    </div>
                    <div>
                        <button class="btn text-danger" onclick="removeChoice(this)">
                            <i class="fas fa-minus-circle"></i>
                        </button>
                    </div>
                </div>`
    }

    function editVoteSession(uuid, name, status, choices) {
        document.getElementById('editVoteSessionUuid').value = uuid;
        document.getElementById('editVoteSessionName').value = name;
        document.getElementById('editVoteSessionStatus').value = status ? 'Öppen' : 'Stängd';
        const choicesContainer = document.getElementById('editVoteSessionChoicesContainer');
        choicesContainer.innerHTML = '';

        choices.forEach((choice) => {
           choicesContainer.innerHTML += choiceTemplate(choice);
        });

        $('#editVoteSessionModal').modal('show');
    }

    function removeChoice(caller) {
        caller.parentElement.parentElement.outerHTML = '';
    }

    function addChoice() {
        const choicesContainer = document.getElementById('editVoteSessionChoicesContainer');
        choicesContainer.innerHTML += choiceTemplate();
        // FIXME adding choice clears others, maybe use "append"
    }

</script>

<?php

require_once 'templates/footer.php';
