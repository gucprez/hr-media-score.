<x-layouts.admin title="Auditoría" header="Auditoría" :breadcrumbs="['Auditoría' => null]">
    <x-slot:subheader>Registro de cambios y accesos. Solo lectura.</x-slot:subheader>
    <livewire:admin.audit-log-table />
</x-layouts.admin>
