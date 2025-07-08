<?php

namespace App\Filament\Operatiu\Livewire;

use App\Models\Notificacio;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificacionsMenu extends Component
{
    public $notificacions = [];
    public $noLlegides = 0;
    
    protected $listeners = [
        'marcarLlegida' => 'marcarNotificacioLlegida',
        'refreshNotificacions' => '$refresh',
    ];
    
    public function mount()
    {
        $this->carregarNotificacions();
    }
    
    public function carregarNotificacions()
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return;
        }
        
        $this->notificacions = Notificacio::perUsuari($userId)
            ->recents(30)
            ->ordenatPerData()
            ->limit(10)
            ->get();
            
        $this->noLlegides = Notificacio::perUsuari($userId)
            ->noLlegides()
            ->count();
    }
    
    public function marcarNotificacioLlegida($notificacioId)
    {
        $notificacio = Notificacio::find($notificacioId);
        
        if ($notificacio && $notificacio->user_id === Auth::id()) {
            $notificacio->marcarComLlegida();
            
            FilamentNotification::make()
                ->title('Notificació marcada com llegida')
                ->success()
                ->send();
                
            $this->carregarNotificacions();
        }
    }
    
    public function marcarTotesLlegides()
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return;
        }
        
        Notificacio::perUsuari($userId)
            ->noLlegides()
            ->each(function ($notificacio) {
                $notificacio->marcarComLlegida();
            });
            
        FilamentNotification::make()
            ->title('Totes les notificacions marcades com llegides')
            ->success()
            ->send();
            
        $this->carregarNotificacions();
    }
    
    public function render()
    {
        return view('filament.operatiu.livewire.notificacions-menu');
    }
}
