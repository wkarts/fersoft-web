<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ImageModal extends Component
{
    public $imageUrl;
    public $title;

    public function __construct($imageUrl = null, $title = 'Imagem Ampliada')
    {
        $this->imageUrl = $imageUrl;
        $this->title = $title;
    }

    public function render()
    {
        return view('components.image-modal');
    }
}
