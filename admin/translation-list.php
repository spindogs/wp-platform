<?php
use Platform\Request;
use Platform\Html;

require(ABSPATH.'wp-admin/admin-header.php');
?>

<div class="wrap">

    <h2>Translations</h2>

    <form method="post">
        <table class="wp-list-table widefat">

            <thead>
                <tr>
                    <th>
                        Key
                    </th>
                    <th style="width:50%">
                        Translation (<?= Html::entities($lang_name); ?>)
                    </th>
                    <th>
                    </th>
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
            <?php } ?>

        </table>
    </form>
</div><!--wrap-->

<?php
require(ABSPATH.'wp-admin/admin-footer.php');
