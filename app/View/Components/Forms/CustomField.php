<?php

namespace App\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomField extends Component
{

    public $fields;
    public $model;
    public $criterias;
    public $subCriterias;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($fields, $criterias = null, $subCriterias = null, $model = false, )
    {
        $this->fields = $fields;
        $this->criterias = $criterias;
        $this->subCriterias = $subCriterias;
        $this->model = $model;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('components.forms.custom-field');
    }

}
