<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ImageUpload extends Component
{
    public $title;
    public $imageUrl;
    public $inputName;
    public $id;

    public function __construct($title, $imageUrl = '/imgs/no_image.png', $inputName = 'file', $id = 'kt_image_1')
    {
        $this->title = $title;
        $this->imageUrl = $imageUrl;
        $this->inputName = $inputName;
        $this->id = $id;
    }

    public function render()
    {
        return view('components.image-upload');
    }
}
