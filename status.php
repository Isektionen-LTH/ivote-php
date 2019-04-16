<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>iVote</title>

    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/fontawesome.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav id="headNavbar" class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <a class="navbar-brand" href="/">iVote</a>
</nav>
<div class="container">
    <div class="row">
        <div class="col">
            <div class="page-title">
                <h3>Aktiva omröstningar</h3>
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
        loadData();
        setInterval(loadData, 2000);
    };

    function loadData() {
        $.get('/api/status')
            .done((status) => {
                const contentContainer = document.getElementById('contentContainer');

                let newContent = '';

                if (status.voteSessions.length === 0) {
                    newContent += '<p>Inga omröstningar öppna just nu</p>';
                } else {
                    newContent += `<table class="table table-sm">
                                                   <thead>
                                                   <tr>
                                                       <th scope="col">Namn</th>
                                                       <th scope="col">Antal röster</th>
                                                   </tr>
                                                   </thead>
                                                   <tbody>`;
                    status.voteSessions.forEach((voteSession) => {
                        newContent += `<tr>
                                                           <th scope="row">
                                                               ${voteSession.name}
                                                           </th>
                                                           <td>
                                                               ${voteSession.vote_count}/${status.userCount}
                                                           </td>
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
<script src="/js/jquery-3.3.1.min.js"></script>
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
</body>
</html
