<?php
use Platform\Setup;
use Platform\Translation;
use Platform\Request;
use Platform\Collection;
use Platform\Html;

//edit form
if (isset($_POST['translations'])) {
    foreach ($_POST['translations'] as $id => $values) {
        $translation = new Translation($id);
        $translation->map($values);
        $translation->update();
    }
}

//get lang
$lang = Setup::app()->lang;
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

    <form action="<?= Request::get(); ?>" method="post">
        <table class="wp-list-table widefat">

            <thead>
                <tr>
                    <th>
                        Key
                    </th>
                    <th style="width:50%">
                        Translation (<?= Html::entities($lang_name); ?>)
                    </th>
                    <th></th>
                </tr>
            </thead>

            <?php foreach ($translations as $translation) { ?>
                <tr>
                    <td>
                        <?= Html::entities($translation->uid); ?>
                    </td>
                    <td>
                        <input type="text" name="translations[<?= intval($translation->id); ?>][langs][<?= Html::entities($lang); ?>]" value="<?= Html::entities($translation->value); ?>" style="width:100%">
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
