<?php
use Platform\Setup;
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

//get lang
$lang = Setup::getLang();
$lang_name = get_bloginfo('name');

//get translations
$collection = new Collection(Translation::class);
$collection->where('lang', $lang);
$translations = $collection->getAll();

//header
require('admin-header.php');
?>

<div class="wrap">

    <h2>Translations</h2>

    <form action="<?= Request::getUri(); ?>" method="post">
        <table class="wp-list-table widefat">

            <thead>
                <tr>
                    <th>
                        Key
                    </th>
                    <th style="width:50%">
                        Translation (<?= Security::escHTML($lang_name); ?>)
                    </th>
                    <th></th>
                </tr>
            </thead>

            <?php foreach ($translations as $translation) { ?>
                <tr>
                    <td>
                        <?= Security::escHTML($translation->uid); ?>
                    </td>
                    <td>
                        <input type="text" name="translations[<?= intval($translation->id); ?>][langs][<?= Security::escHTML($lang); ?>]" value="<?= Security::escHTML($translation->value); ?>" style="width:100%">
                    </td>
                    <td>
                        <button type="submit">Save</button>
                    </td>
                </tr>
            <? } ?>

        </table>
    </form>
</div><!--wrap-->

<?php
require('admin-footer.php');
