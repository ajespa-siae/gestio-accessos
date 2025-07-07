<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UserMenu extends Component
{
    public $darkMode;
    public $navigation;
    public $profile;
    public $profileUrl;
    public $profileImage;
    public $profileImageAlt;

    public function __construct($darkMode, $navigation = [], $profile = null, $profileUrl = null, $profileImage = null, $profileImageAlt = null)
    {
        $this->darkMode = $darkMode;
        $this->navigation = $navigation;
        $this->profile = $profile;
        $this->profileUrl = $profileUrl;
        $this->profileImage = $profileImage;
        $this->profileImageAlt = $profileImageAlt;
    }

    public function render()
    {
        return view('components.user-menu');
    }
}
