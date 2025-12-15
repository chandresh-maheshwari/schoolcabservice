<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Datatable extends Component
{

    public $tablevar;
    public $RouteParam;
 
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($tablevar)
    {

        if(isset($tablevar['RouteParam'])){
            $this->RouteParam = $tablevar['RouteParam'];
        }else{
            $this->RouteParam = "";
        }
        $this->tablevar = $tablevar; 
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.datatable');
    }
}
