<?php

namespace App\Http\Controllers;

abstract class PanelController extends Controller
{
    protected function panel(): string
    {
        return request()->routeIs('admin.*') ? 'admin' : 'peternak';
    }

    protected function routeName(string $name): string
    {
        return $this->panel().'.'.$name;
    }

    protected function viewName(string $view): string
    {
        return $view;
    }
}
