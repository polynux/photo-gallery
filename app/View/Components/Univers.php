<?php

namespace App\View\Components;

use App\Models\Univers as UniversModel;
use App\Services\UniversLayoutService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Univers extends Component
{
    public function __construct() {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $univers = UniversModel::orderBy('position')->get();
        $resolved = app(UniversLayoutService::class)->resolve($univers);

        return view('components.univers', [
            'univers' => $univers,
            'layout' => $resolved,
        ]);
    }
}
