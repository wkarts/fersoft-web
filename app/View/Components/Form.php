<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Form extends Component
{
    public $title;
    public $formAction;
    public $cancelUrl;
    public $fields;
    public $data;

    public function __construct($title, $formAction, $cancelUrl, $fields, $data = [])
    {
        $this->title = $title;
        $this->formAction = $formAction;
        $this->cancelUrl = $cancelUrl;
        $this->fields = $fields;
        $this->data = $data;
    }

    public function render()
    {
        return view('components.form');
    }
}
