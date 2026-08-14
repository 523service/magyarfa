<?php

namespace App\Filament\Pages\Auth;

class Login extends \Filament\Auth\Pages\Login
{
    public function mount(): void
    {
        parent::mount();

        // Set default login credentials
        /*
        $this->form->fill([
            'email' => 'admin@magyartuzep.hu',
            'password' => 'demo.Filament@2021!',
            'remember' => true,
        ]);
        */
    }
}
