<?php
namespace Platform\Controller;

use Platform\Controller;
use Platform\Collection;
use Platform\Setup;
use Platform\Translation;

class TranslationController extends Controller {

    /**
     * @return void
     */
    public function listAll()
    {
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

        //render template
        $this->data('translations', $translations);
        $this->data('lang', $lang);
        $this->data('lang_name', $lang_name);
        $this->template_path = Setup::platform()->path;
        $this->render('admin/translation-list');
    }

}
