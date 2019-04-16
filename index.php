<?php
session_start();

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'public';

require_once 'templates/header.php';

?>
<?php if ($role === 'user') : ?>
<div class="container">
    <div class="row">
        <div class="col">
            <div class="page-title">
                <h3>Rösta</h3>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col" id="contentContainer">
        </div>
    </div>
</div>
<script>
    window.onload = () => {
        loadVoteSessions();
        setInterval(loadVoteSessions, 5000);
    };

    function loadVoteSessions() {
        $.get('/api/votesessions')
            .done((voteSessions) => {
                const contentContainer = document.getElementById('contentContainer');

                let newContent = '';

                if (voteSessions.length === 0) {
                    newContent += '<p>Inga omröstningar öppna just nu</p>';
                } else {
                    newContent += `<table class="table table-sm">
                                                   <thead>
                                                   <tr>
                                                       <th scope="col">Namn</th>
                                                       <th scope="col"></th>
                                                   </tr>
                                                   </thead>
                                                   <tbody>`;
                    voteSessions.forEach((voteSession) => {
                        newContent += `<tr>
                                                           <th scope="row">
                                                               ${voteSession.name}
                                                           </th>
                                                           <td>` +
                                                           (voteSession.voted ?
                                                               `<span class="float-right btn btn-sm btn-secondary disabled" title="Du har redan röstat">Rösta</span>` :
                                                               `<a href="/vote?uuid=${voteSession.uuid}" class="float-right btn btn-sm btn-primary">Rösta</a>`) +
                                                           `</td>
                                                      </tr>`;
                    });
                    newContent += `</tbody></table>`;
                }

                contentContainer.innerHTML = newContent;
            }).fail((err) => {
                alert('Error while getting available vote sessions');
            });
    }
</script>
<?php elseif ($role === 'public') : ?>
<div class="container">
    <div class="row">
        <div class="col">
            <h4>Du måste logga in för att kunna rösta</h4>
        </div>
    </div>
</div>
<?php endif; ?>
<?php

require_once 'templates/footer.php';
