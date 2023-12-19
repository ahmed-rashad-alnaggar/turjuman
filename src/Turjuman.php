<?php

namespace Alnaggar\Turjuman;

use Illuminate\Support\Facades\Facade;

/**
 * Omitting the @method annotations since many IDEs now associate the class mentioned in the @see annotation,
 * offering a convenient means to access methods along with their corresponding comments.
 * 
 * If you are using Visual Studio Code, enhance your PHP development experience by utilizing this extension: https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode.
 */

/**
 * @package Alnaggar\Turjuman
 * 
 * @see \Alnaggar\Turjuman\Localizer
 */
class Turjuman extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'turjuman';
    }
}