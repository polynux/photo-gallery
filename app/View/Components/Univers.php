<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Univers extends Component
{
    protected $classes_templates = [
        '9' => ['big', 'wide', 'tall', '', '', 'wide', '', 'tall', ''],
        '10' => ['big', 'wide', 'tall', '', '', 'wide', '', 'tall', '', 'wide'],
        '11' => ['big', 'wide', 'tall', '', '', 'wide', '', 'tall', '', 'wide', ''],
        '12' => ['big', 'wide', 'tall', '', '', 'wide', '', 'tall', '', 'wide', '', 'wide'],
        '13' => ['big', 'wide', 'tall', '', '', 'wide', '', 'tall', '', 'big', '', 'wide', ''],
        'default' => ['big', 'wide', 'tall', '', '', 'wide', '', 'tall', '', 'wide', '', 'wide', '', 'wide'],
    ];
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.univers', [
            'univers' => \App\Models\Univers::all() ?? [],
            'classes' => $this->classes_templates[count(\App\Models\Univers::all())] ?? $this->classes_templates['default'],
        ]);
    }
}
