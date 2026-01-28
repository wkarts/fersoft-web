<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Table extends Component
{
    public $title;
    public $newItemUrl;
    public $editUrl;
    public $deleteUrl;
    public $headers;
    public $fields;
    public $records;

    public function __construct($title, $newItemUrl, $editUrl, $deleteUrl, $headers, $fields, $records)
    {
        $this->title = $title;
        $this->newItemUrl = $newItemUrl;
        $this->editUrl = $editUrl;
        $this->deleteUrl = $deleteUrl;
        $this->headers = $headers;
        $this->fields = $fields;
        $this->records = $records;
    }

    public function render()
    {
        return view('components.table');
    }
}
