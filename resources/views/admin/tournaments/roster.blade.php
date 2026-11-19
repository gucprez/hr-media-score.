<x-layouts.admin :title="'Plantilla · '.$registration->displayName()" :breadcrumbs="['Torneos' => route('admin.tournaments.index'), $tournament->fullName() => route('admin.tournaments.show', $tournament), $registration->displayName() => null]">
    <livewire:admin.roster-manager :registration="$registration" />
</x-layouts.admin>
