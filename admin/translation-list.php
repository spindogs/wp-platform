<?php
use Platform\Translation;
use Platform\Security;
use Platform\Request;
use Platform\Beta\Collection;

//edit form
if (isset($_POST['translations'])) {
    foreach ($_POST['translations'] as $id => $values) {
        $translation = new Translation($id);
        $translation->map($values);
        $translation->update();
    }
}

//get translations
$collection = new Collection(Translation::class);
$translations = $collection->getAll();
$translations_by_uid = [];

foreach ($translations as $translation) {
    $translations_by_uid[$translation->uid][$translation->lang] = $translation;
}

$num_langs = 1;

foreach ($translations_by_uid as $key_langs) {
    $num_per_key = count($key_langs);
    $num_langs = max($num_langs, $num_per_key);
}

//header
require('admin-header.php');
?>

<div class="wrap">

    <h2>Translations</h2>

    <form action="<?= Request::getUri(); ?>" method="post">
        <table class="wp-list-table widefat">

            <thead>
                <tr>
                    <th>Key</th>
                    <?php for ($i = 0; $i <= $num_langs; $i++) { ?>
                        <th></th>
                    <?php } ?>
                    <th></th>
                </tr>
            </thead>

            <?php foreach ($translations_by_uid as $uid => $trans_langs) { ?>
                <tr>
                    <td><?= $uid; ?></td>
                    <?php $i = 0; ?>
                    <?php foreach ($trans_langs as $translation) { ?>
                        <td>
                            <input type="text" name="translations[<?= intval($translation->id); ?>][langs][<?= Security::escHTML($translation->lang); ?>]" value="<?= Security::escHTML($translation->value); ?>">
                        </td>
                        <?php $i++; ?>
                    <?php } ?>
                    <?php while ($i <= $num_langs) { ?>
                        <td></td>
                        <?php $i++; ?>
                    <?php } ?>
                    <td>
                        <button type="submit">Save</button>
                    </td>
                </tr>
            <?php } ?>

        </table>
    </form>
</div><!--wrap-->

<?php
require('admin-footer.php');
