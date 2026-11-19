<x-layouts.admin title="Inscripciones" header="Equipos inscritos" :breadcrumbs="['Torneos' => route('admin.tournaments.index'), $tournament->fullName() => route('admin.tournaments.show', $tournament), 'Inscripciones' => null]">
    <x-slot:subheader>{{ $tournament->fullName() }}</x-slot:subheader>
    <livewire:admin.tournament-registrations :tournament="$tournament" />
</x-layouts.admin>
