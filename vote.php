<?php
session_start();

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'public';

if ($role !== 'user') {
    header('Location: /');
    die();
}

require_once 'api/database.php';

$uuid = (isset($_GET['uuid']) ? $_GET['uuid'] : '');

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT uuid, name, open, choices FROM votesessions WHERE uuid = :uuid;');
$stmt->execute(['uuid' => $uuid]);

if ($stmt->rowCount() === 0) {
    header('Location: /');
    die();
}

$voteSession = $stmt->fetch();
$voteSession['choices'] = unserialize($voteSession['choices']);

require_once 'templates/header.php';

?>
<div class="container">
    <div class="row">
        <div class="col">
            <div class="page-title">
                <h3>Omröstning: <?php echo $voteSession['name']; ?></h3>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label for="choiceList">Val</label>
            <ol id="choiceList">
                <?php foreach ($voteSession['choices'] as $choice) : ?>
                <li><?php echo $choice ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="form-group">
                <label for="voteChoice">Ditt val</label>
                <select id="voteChoice" class="custom-select">
                    <option selected value="-1">Blank</option>
                    <?php for ($i = 0; $i < sizeof($voteSession['choices']); $i++) : ?>
                    <option value="<?php echo $i; ?>"><?php echo $voteSession['choices'][$i]; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button class="btn btn-primary" onclick="confirmVote()">Rösta</button>
        </div>
    </div>
</div>
<script>
    function confirmVote() {
        const confirmation = confirm('Vill du skicka in din röst? När du har röstat kan du inte ändra din röst!');

        if (confirmation) {
            submitVote();
        }
    }

    function submitVote() {
        const voteValue = document.getElementById('voteChoice').value;

        $.post('/api/vote', {
            votesession_uuid: '<?php echo $_GET['uuid'] ?>',
            value: voteValue
        }).done(() => {
            location.href = '/';
        }).fail((err) => {
            alert('Något gick fel vid röstningen, testa ladda om sidan');
        });
    }
</script>
<?php

require_once 'templates/footer.php';
